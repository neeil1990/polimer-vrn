<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Main\Localization\Loc::loadMessages(__FILE__);

$controller = null;
$isAllowDisplay = false;

$APPLICATION->SetTitle(Loc::getMessage('YANDEX_MARKET_ADMIN_BUYER_PROFILE_EDIT'));

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_ADMIN_MODULE_NOT_INSTALLED'));
	}

	$controller = new Market\Ui\Trading\BuyerProfileEdit();

	$controller->checkWriteAccess();
	$controller->loadModules();

	$controller->show();
}
catch (Main\SystemException $exception)
{
	$message = new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage()
	]);

	echo $message->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';