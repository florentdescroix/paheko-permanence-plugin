<?php

namespace Paheko\Plugin\Perm;

use Paheko\Config;

class Organization
{
    static function config()
    {
        $config = Config::getInstance();
        $out = $config->asArray();
        foreach ($config->files as $key => $value) {
            $out['files'][$key] = $config->fileURL($key);
        }

        return $out;
    }
}
