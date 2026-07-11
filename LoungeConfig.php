<?php
namespace GDO\Lounge;

use GDO\Util\FileUtil;

final class LoungeConfig
{
    public function getJSONData(Module_Lounge $module): array
    {
        $data = json_decode(FileUtil::getContents($module->filePath('thelounge/defaults/config.js')));
        return $data;
    }
}
