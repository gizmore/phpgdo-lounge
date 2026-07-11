<?php
namespace GDO\Lounge\Method;

use GDO\Cronjob\MethodCronjob;
use GDO\Lounge\LoungeConfig;
use GDO\Lounge\Module_Lounge;

final class Cron extends MethodCronjob
{

    public function run(): void
    {
        $module = Module_Lounge::instance();
        $config = new LoungeConfig();
        $data = $config->getJSONData($module);
        $data = json_encode($data);
        if(md5($data) != $module->cfgConfigHash())
        {
            # kill lounge
        }
        # start launch (fails when already bound port)
    }
}