<?php
/**
 * Created: 10.04.2021, 14:41
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

class mainSettings
{
    /**
     * Get /bitrix or /local directory
     * @return string
     */
    public static function getInstallDir()
    {
        $bitrixDir = 'bitrix';
        $localDir = 'local';
        $modulesDir = 'modules';
        // get and set install path and dir
        $installPath = '/' . $bitrixDir . '/' . $modulesDir . '/';
        $installDir = $bitrixDir;
        $docRoot = \Bitrix\Main\Application::getInstance()->getContext()->getServer()->getDocumentRoot();
        $module_path = str_replace("\\", "/", dirname(__FILE__));
        if (strpos($module_path, $installPath) === false) {
            $localPath = '/' . $localDir . '/' . $modulesDir . '/';
            if (is_dir($docRoot . $localPath)) {
                $installDir = $localDir;
            }
        }
        return $installDir;
    }
    /**
     * Get Module ID
     * @return string
     */
    public static function getModuleID()
    {
        return basename(pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname']);
    }
}
