<?php
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Wbs24\Sbermmexport;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!Loader::includeModule('wbs24.sbermmexport')) return;

$request = Application::getInstance()->getContext()->getRequest();
$action = $request->getQuery("ACTION");

if ($action == 'getManualCallWarning') {
    Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/wbs24.sbermmexport/load/sbermm_setup.php');
    echo "<div class='adm-info-message-wrap' align='left'><div class='adm-info-message'>"
        .Loc::getMessage("WBS24.SBERMMEXPORT.MANUAL_CALL_NOTE")
    ."</div></div>";
}
