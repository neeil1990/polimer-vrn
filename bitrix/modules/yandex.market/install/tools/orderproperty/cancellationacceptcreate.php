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

	$controller = new Market\Ui\Trading\CancellationAcceptCreator();

	$controller->checkWriteAccess();
	$controller->loadModules();

	$option = $controller->processRequest();

	$response = [
		'status' => 'ok',
		'option' => $option,
	];
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

Market\Utils\HttpResponse::sendJson($response);
