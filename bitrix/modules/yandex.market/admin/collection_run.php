<?php

use Bitrix\Main;
use Yandex\Market;

const BX_SESSION_ID_CHANGE = false;
const NOT_CHECK_FILE_PERMISSIONS = true;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $USER;
global $APPLICATION;

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException('Module yandex.market is required');
	}

	$controller = new Market\Ui\Export\Collection\RunForm();

	$controller->setTitle();

	if ($controller->hasRequest())
	{
		$controller->processRequest();
	}

	$controller->checkReadAccess();

	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

	$controller->preload();
	$controller->show();
}
catch (Main\AccessDeniedException $exception)
{
	$APPLICATION->AuthForm('');
}
catch (Main\SystemException $exception)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';