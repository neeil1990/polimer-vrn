<?php
/**
 * Created: 22.10.2021, 12:43
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/** @global CMain $APPLICATION */
// define constants
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);
// get doc_root
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../..');
// check prolog
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
}
else
{
    die('ERROR bitrix core path!');
}
// use classes
use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc;

//  Loads language messages for specified file in a lazy way
Loc::loadMessages(__FILE__);
// params
$arResponse = [
    'status' => 'failure'
];
$moduleID = basename(pathinfo(dirname(__FILE__))['dirname']);
$moduleCode = strtoupper(str_replace(".", "_", $moduleID));
$moduleStatus = Loader::includeSharewareModule($moduleID);
// check module license
if ($moduleStatus == Loader::MODULE_DEMO || $moduleStatus == Loader::MODULE_INSTALLED) {
    // check module rights
    global $APPLICATION, $USER;
    $moduleWriteAccess = true;
    $MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleID);
    if ($MODULE_RIGHT < 'W') {
        $moduleWriteAccess = false;
    }

    $context = Bitrix\Main\Application::getInstance()->getContext();
    $request = $context->getRequest();

    $action = '';
    $params = [];

    $action = $request->getPost('action');
    $params = $request->getPost('params');

    $resultOperation = [];

    switch ($action) {
        case 'deleteAllLogs':
            if($moduleWriteAccess) {
                if (method_exists('s34web\mailSMTPB24\logOperations', 'deleteAllLogs')) {
                    $resultOperation = s34web\mailSMTPB24\logOperations::deleteAllLogs();
                }
                else
                {
                    $arResponse['error'] = Loc::getMessage($moduleCode . '_CLASS_LOGOPERATIONS_ERROR_TEXT');
                }
            }
            else
            {
                $arResponse['error'] = Loc::getMessage($moduleCode . '_ERROR_MODULE_ACCESS');
            }
            break;
        case 'deleteLog':
            if($moduleWriteAccess) {
                if (method_exists('s34web\mailSMTPB24\logOperations', 'deleteLog')) {
                    $resultOperation = s34web\mailSMTPB24\logOperations::deleteLog($params);
                }
                else
                {
                    $arResponse['error'] = Loc::getMessage($moduleCode . '_CLASS_LOGOPERATIONS_ERROR_TEXT');
                }
            }
            else
            {
                $arResponse['error'] = Loc::getMessage($moduleCode . '_ERROR_MODULE_ACCESS');
            }
            break;
        case 'checkSMTPConnection':
            if($USER->IsAuthorized()) {
                if (method_exists('s34web\mailSMTPB24\mailConfigOperations', 'checkSMTPConnect')) {
                    $resultOperation = s34web\mailSMTPB24\mailConfigOperations::checkSMTPConnect($params);
                }
                else
                {
                    $arResponse['error'] = Loc::getMessage($moduleCode . '_CLASS_MAILCONFIGOPERATIONS_ERROR_TEXT');
                }
            }
            else
            {
                $arResponse['error'] = Loc::getMessage($moduleCode . '_ERROR_AUTHORIZATION');
            }
            break;
        default:
            $arResponse['error'] = Loc::getMessage($moduleCode . '_ERROR_ACTION');
            break;
    }

    if(!empty($resultOperation))
    {
        if(!empty($resultOperation['status']))
        {
            $arResponse['status'] = $resultOperation['status'];
        }
        if(!empty($resultOperation['error']))
        {
            $arResponse['error'] = $resultOperation['error'];
        }
    }
} else {
    if($moduleStatus == Loader::MODULE_DEMO_EXPIRED) {
        $arResponse['error'] = Loc::getMessage($moduleCode . '_ERROR_MODULE_DEMO_EXPIRED');
    }
    if($moduleStatus == Loader::MODULE_NOT_FOUND) {
        $arResponse['error'] = Loc::getMessage($moduleCode . '_ERROR_MODULE_NOT_FOUND');
    }
}
// return result
echo Bitrix\Main\Web\Json::encode($arResponse);
CMain::finalActions();