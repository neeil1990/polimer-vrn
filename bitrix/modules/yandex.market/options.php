<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		$message = Loc::getMessage('YANDEX_MARKET_OPTIONS_REQUIRE_MODULE');
		throw new Main\SystemException($message);
	}

	$controller = new Market\Ui\Options();

	$controller->checkReadAccess();
	$controller->loadModules();

	$controller->show();
}
catch (Main\SystemException $exception)
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
}