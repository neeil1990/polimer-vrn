<?php
/**
 * Created: 10.06.2021, 15:15
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader,
    Bitrix\Main\Config\Option,
    Bitrix\Main\Application;

//  Loads language messages for specified file in a lazy way
Loc::loadMessages(__FILE__);
// get module variables
$moduleID = basename(pathinfo(dirname(__FILE__))['dirname']);
$moduleCode = strtoupper(str_replace('.', '_', $moduleID));
$moduleInstallDir = basename(pathinfo(pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname'])['dirname']);
$flagIncludeModule = false;
$flagActiveModule = false;
// server & site variables
$request = Application::getInstance()->getContext()->getRequest();
$docRoot = Application::getInstance()->getContext()->getServer()->getDocumentRoot();

// check include & active module
$moduleStatus = Loader::includeSharewareModule($moduleID);
if ($moduleStatus == Loader::MODULE_DEMO || $moduleStatus == Loader::MODULE_INSTALLED) {
    $flagIncludeModule = true;

    $activeModule = Option::get($moduleID, 'active_module', 'N');
    if($activeModule == 'Y')
    {
        $flagActiveModule = true;
    }
}

$readLog = false;
$logFileName = '';
global $APPLICATION;

if ($flagIncludeModule && $flagActiveModule) {
    // check module right
    if ($APPLICATION->GetGroupRight($moduleID) < 'R')
        $APPLICATION->AuthForm(Loc::getMessage($moduleCode.'_LOG_VIEW_ACCESS_DENIED'));
    // get request params
    if (!empty($request->getQuery('read_log'))) {
        $readLog = true;
    }
    $requestFile = $request->getQuery('file');
    if (!empty($requestFile)) {
        $logFileName = htmlspecialcharsbx($requestFile);
    }
    // check params
    if($readLog && !empty($logFileName))
    {
        $mailLogsPath = $docRoot . '/' . $moduleInstallDir . '/modules/' . $moduleID . '/logs';
        // show logs
        if (Bitrix\Main\IO\Directory::isDirectoryExists($mailLogsPath)) {
            if(Bitrix\Main\IO\File::isFileExists($mailLogsPath.'/'.$logFileName))
            {
                $str = htmlspecialcharsEx(file_get_contents($mailLogsPath.'/'.$logFileName));
                echo '<pre>'.$str.'</pre>';
                exit;
            }
            else
            {
                echo Loc::getMessage($moduleCode.'_LOG_VIEW_EMPTY_FILE');
            }
        }
    }
    else
    {
        echo Loc::getMessage($moduleCode.'_LOG_VIEW_FILE_PARAMS');
    }
}
else
{
    // display errors
    if (!$flagIncludeModule) {
        if($moduleStatus == Loader::MODULE_DEMO_EXPIRED) {
            echo Loc::getMessage($moduleCode . '_LOG_VIEW_MODULE_ERROR_DEMO_EXPIRED_TEXT');
        }
        if($moduleStatus == Loader::MODULE_NOT_FOUND) {
            echo Loc::getMessage($moduleCode . '_LOG_VIEW_MODULE_ERROR_NOT_FOUND_TEXT');
        }
    }
    else {
        if (!$flagActiveModule) {
            echo Loc::getMessage($moduleCode . '_LOG_VIEW_MODULE_ACTIVE_ERROR_TEXT');
        }
    }
}