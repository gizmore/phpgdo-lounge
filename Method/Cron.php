<?php
namespace GDO\Lounge\Method;

use GDO\Cronjob\MethodCronjob;
use GDO\Lounge\LoungeConfig;
use GDO\Lounge\Module_Lounge;
use RuntimeException;

/**
 * Keep the bundled The Lounge process alive and restart it when its
 * phpgdo configuration changes.
 */
final class Cron extends MethodCronjob
{
    private const PID_FILE = 'thelounge.pid';
    private const LOG_FILE = 'thelounge.log';

    public function run(): void
    {
        $module = Module_Lounge::instance();
        $config = new LoungeConfig();
        $data = $config->getJSONData($module);
        $hash = md5(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $running = $this->isRunning($module);
        if ($running && ($hash !== $module->cfgConfigHash()))
        {
            $this->stopLounge($module);
            $running = false;
        }

        if (!$running)
        {
            $this->startLounge($module, $data);
            $module->saveConfigVar('lounge_config_hash', $hash);
        }
    }

    private function isRunning(Module_Lounge $module): bool
    {
        $pid = $this->readPID($module);
        if (!$pid)
        {
            return false;
        }

        if (function_exists('posix_kill') && !@posix_kill($pid, 0))
        {
            @unlink($this->pidFile($module));
            return false;
        }

        $cmdline = "/proc/{$pid}/cmdline";
        if (is_readable($cmdline))
        {
            $command = str_replace("\0", ' ', (string) @file_get_contents($cmdline));
            if (!str_contains($command, $module->filePath('thelounge/index.js')))
            {
                @unlink($this->pidFile($module));
                return false;
            }
        }

        return true;
    }

    private function stopLounge(Module_Lounge $module): void
    {
        $pid = $this->readPID($module);
        if (!$pid)
        {
            return;
        }

        if (function_exists('posix_kill'))
        {
            @posix_kill($pid, 15);
            for ($i = 0; $i < 20 && @posix_kill($pid, 0); $i++)
            {
                usleep(100000);
            }
            if (@posix_kill($pid, 0))
            {
                @posix_kill($pid, 9);
            }
        }
        else
        {
            exec('kill ' . escapeshellarg((string) $pid) . ' 2>/dev/null');
        }

        @unlink($this->pidFile($module));
    }

    private function startLounge(Module_Lounge $module, array $config): void
    {
        $home = getenv('HOME');
        $dir = $home . '/.thelounge';
        if (!is_dir($dir))
        {
            mkdir($dir, 0700, true);
        }
        file_put_contents(
            $dir . '/config.js',
            '"use strict";'."\n\n".'module.exports = '.json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).";\n"
        );

        $index = $module->filePath('thelounge/index.js');
        if (!is_file($index))
        {
            throw new RuntimeException("The Lounge entrypoint is missing: {$index}");
        }

        $args = ['node', $index, 'start'];
        $command = implode(' ', array_map('escapeshellarg', $args));
        $log = $module->storagePath(self::LOG_FILE);
        $pidFile = $this->pidFile($module);
        $shell = sprintf(
            'HOME=%s nohup %s >> %s 2>&1 < /dev/null & echo $! > %s',
            escapeshellarg($home),
            $command,
            escapeshellarg($log),
            escapeshellarg($pidFile),
        );

        exec($shell, $output, $status);
        if ($status !== 0 || !$this->readPID($module))
        {
            throw new RuntimeException('Could not start The Lounge. See ' . $log);
        }
    }

    private function configValue(mixed $value): string
    {
        if (is_bool($value))
        {
            return $value ? 'true' : 'false';
        }
        if (is_array($value))
        {
            return '[' . implode(',', $value) . ']';
        }
        return (string) $value;
    }

    private function readPID(Module_Lounge $module): ?int
    {
        $file = $this->pidFile($module);
        if (!is_readable($file))
        {
            return null;
        }
        $pid = (int) trim((string) file_get_contents($file));
        return $pid > 1 ? $pid : null;
    }

    private function pidFile(Module_Lounge $module): string
    {
        return $module->storagePath(self::PID_FILE);
    }
}
