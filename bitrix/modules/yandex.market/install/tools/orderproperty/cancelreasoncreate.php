<?php

use Bitrix\Main;
use Yandex\Market;

define('BX_SECURITY_SESSION_READONLY', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

session_write_close();

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException('require module yandex.market');
	}

	$controller = new Market\Ui\Trading\CancelReasonCreator();

	$controller->checkWriteAccess();
	$controller->loadModules();

	$option = $controller->processRequest();

	echo Main\Web\Json::encode([
		'status' => 'ok',
		'option' => $option,
	]);
}
catch (Main\SystemException $exception)
{
	echo Main\Web\Json::encode([
		'status' => 'error',
		'message' => $exception->getMessage()
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';
