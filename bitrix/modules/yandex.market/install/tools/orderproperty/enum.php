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
	$personTypeId = (int)$httpRequest->getPost('PERSON_TYPE_ID');

	$enum = Market\Ui\UserField\OrderPropertyType::getVariants($personTypeId);

	echo Main\Web\Json::encode([
		'status' => 'ok',
		'enum' => $enum
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
