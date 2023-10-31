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

	if (!Market\Ui\Access::isReadAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$httpRequest = Main\Context::getCurrent()->getRequest();

	if (!$httpRequest->isPost())
	{
		throw new Main\NotSupportedException('only post request supported');
	}

	$httpRequest->addFilter(new Main\Web\PostDecodeFilter());

	$query = $httpRequest->getPost('q');
	$formData = $httpRequest->getPostList()->toArray();
	$variants = Market\Ui\UserField\ExportParamType::suggestVariants($query, $formData);

	$response = [
		'status' => 'ok',
		'enum' => $variants,
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
