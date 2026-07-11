<?php
namespace GDO\Lounge;

/**
 * Translate phpgdo module settings into The Lounge CLI configuration.
 */
final class LoungeConfig
{
    public function getJSONData(Module_Lounge $module): array
    {
        $url = parse_url($module->cfgURL());
        $webPort = isset($url['port']) ? (int) $url['port'] : 9000;

        return [
            'public' => true,
            'host' => '127.0.0.1',
            'port' => $webPort,
            'reverseProxy' => true,
            'lockNetwork' => true,
            'defaults' => [
                'name' => GDO_SITENAME,
                'host' => $module->cfgServer(),
                'port' => (int) $module->cfgPort(),
                'tls' => $module->cfgTLS(),
                'join' => $module->cfgChannel(),
            ],
        ];
    }
}
