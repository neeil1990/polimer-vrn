<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

/** @var CMain $APPLICATION */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$isPopup = (isset($_REQUEST['popup']) && $_REQUEST['popup'] === 'Y');

if ($isPopup)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_LOG_REQUIRE_MODULE')
    ]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_LOG_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$request = Main\Context::getCurrent()->getRequest();

	$APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', [
		'GRID_ID' => 'YANDEX_MARKET_ADMIN_LOG',
		'PROVIDER_TYPE' => 'ExportLog',
		'DATA_CLASS_NAME' => Market\Logger\Table::class,
        'SERVICE' => $request->get('service'),
        'TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_LOG_PAGE_TITLE'),
		'LIST_FIELDS' => [
			'TIMESTAMP_X',
			'LEVEL',
			'MESSAGE',
			'ENTITY_TYPE',
			'SETUP',
			'OFFER_ID',
			'GIFT_ID',
			'PROMO_ID',
			'COLLECTION_ID',
			'TRACE',
		],
		'DEFAULT_LIST_FIELDS' => [
			'TIMESTAMP_X',
			'LEVEL',
			'MESSAGE',
			'ENTITY_TYPE',
			'SETUP',
			'OFFER_ID',
			'TRACE',
		],
		'CONTEXT_MENU_EXCEL' => 'Y',
		'ROW_ACTIONS' => [
			'DELETE' => [
				'ICON' => 'delete',
				'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_LOG_ROW_ACTION_DELETE'),
				'CONFIRM' => 'Y',
				'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_LOG_ROW_ACTION_DELETE_CONFIRM')
			]
		],
		'GROUP_ACTIONS' => [
			'delete' => Loc::getMessage('YANDEX_MARKET_ADMIN_LOG_ROW_ACTION_DELETE')
		],
		'ALLOW_BATCH' => 'Y'
	]);

	Market\Ui\Checker\Announcement::show();
}

if ($isPopup)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}