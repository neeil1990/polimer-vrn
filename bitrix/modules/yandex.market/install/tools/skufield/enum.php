<?php

use Bitrix\Main;
use Yandex\Market;

define('BX_SECURITY_SESSION_READONLY', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException('require module yandex.market');
	}

	if (!Market\Ui\Access::isReadAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$httpRequest = Main\Context::getCurrent()->getRequest();
	$personTypeId = (int)$httpRequest->getPost('IBLOCK_ID');

	$enum = Market\Ui\UserField\SkuFieldType::getFieldEnum($personTypeId);

	$response = [
		'status' => 'ok',
		'enum' => $enum
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
