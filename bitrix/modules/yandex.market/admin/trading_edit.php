<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('YANDEX_MARKET_TRADING_EDIT_TITLE'));

$controller = null;
$state = null;

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		$message = Loc::getMessage('YANDEX_MARKET_TRADING_EDIT_REQUIRE_MODULE');
		throw new Main\SystemException($message);
	}

	$controller = new Market\Ui\Trading\SetupEdit();

	$controller->setTitle();
	$controller->checkReadAccess();
	$controller->loadModules();

	$state = $controller->resolveState();

	if ($controller->hasRequest() && !$controller->isRequestHandledByView($state))
	{
		$controller->checkSession();
		$controller->checkWriteAccess();
		$controller->processRequest();
	}
}
catch (Main\SystemException $exception)
{
	if ($controller !== null)
	{
		$controller->handleException($exception);
	}
	else
	{
		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage(),
		]);
	}
}

if ($controller !== null && $state !== null)
{
	$controller->setStateTitle($state);
	$controller->show($state);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
