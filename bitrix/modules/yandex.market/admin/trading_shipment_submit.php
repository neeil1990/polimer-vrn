<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Main\Localization\Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_TRADING_SHIPMENT_SUBMIT_REQUIRE_MODULE'));
	}

	$controller = new Market\Ui\Trading\ShipmentSubmit();

	$controller->checkReadAccess();
	$controller->loadModules();
	$isAllowDisplay = true;

	if ($controller->hasRequest())
	{
		$controller->checkSession();
		$controller->checkWriteAccess();

		$response = $controller->processRequest();
	}
	else
	{
		throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_TRADING_SHIPMENT_SUBMIT_HASNT_REQUEST'));
	}
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

echo Main\Web\Json::encode($response);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';