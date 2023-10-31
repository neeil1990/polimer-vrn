<?php

use Yandex\Market;
use Bitrix\Main;

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
	$clientId = (string)$httpRequest->getPost('CLIENT_ID');
	$scope = (string)$httpRequest->getPost('SCOPE');

	$enum = Market\Ui\UserField\TokenType::getVariants($clientId, $scope);

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
