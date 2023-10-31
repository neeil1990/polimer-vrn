<?php

use Bitrix\Main;
use Yandex\Market;

$controller = new Market\Ui\Trading\OrderAdmin();
$controller->setServiceCode($arParams['SERVICE_CODE']);

try
{
	$controller->loadModules();
	$controller->checkReadAccess();

	$controller->show();
}
catch (Main\SystemException $exception)
{
	$controller->handleException($exception);
}