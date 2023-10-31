<?php
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Wbs24\Ozonexport;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!$USER->IsAdmin()) return;
if (!Loader::includeModule('wbs24.ozonexport')) return;

$request = Application::getInstance()->getContext()->getRequest();
$action = $request->getQuery("ACTION");
$profileId = $request->getQuery("PROFILE_ID");

if ($action == 'clean' && $profileId) {
    $ozon = new Ozonexport();
    $param = [
        'offersLogOn' => true,
        'profileId' => $profileId,
    ];
    $ozonOffersLog = $ozon->getOffersLogObject($param);
    $ozonOffersLog->clearOffersLog();
    echo 'success';
}
