<?php

use Bitrix\Main;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException('require module yandex.market');
	}

	if (!Market\Ui\Access::isWriteAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$httpRequest = Main\Context::getCurrent()->getRequest();
	$httpRequestData = $httpRequest->getPostList()->toArray();

	$processor = new Market\Api\OAuth2\AccessToken\ExchangeCode();
	$processResult = $processor->run($httpRequestData);

	if (!$processResult->isSuccess())
	{
		$errorMessage = implode('<br />', $processResult->getErrorMessages());
		throw new Main\SystemException($errorMessage);
	}

	$processData = $processResult->getData();
	$responseToken = [
		'ID' => $processData['ID'],
		'VALUE' => $processData['USER_LOGIN'] ?: $processData['USER_ID']
	];

	$response = [
		'status' => 'ok',
		'token' => $responseToken
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
