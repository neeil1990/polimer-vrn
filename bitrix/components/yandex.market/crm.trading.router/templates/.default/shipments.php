<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

$APPLICATION->SetTitle(Loc::getMessage('YANDEX_MARKET_CRM_ROUTER_SHIPMENTS_TITLE'));
$APPLICATION->SetAdditionalCSS('/bitrix/panel/main/admin-public.css');

$controller = new Market\Ui\Trading\ShipmentList();
$controller->setServiceCode($arParams['SERVICE_CODE']);

try
{
	$controller->checkReadAccess();
	$controller->loadModules();

	$controller->show();
}
catch (Main\SystemException $exception)
{
	$controller->handleException($exception);
}