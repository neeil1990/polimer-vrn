<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$APPLICATION->IncludeComponent(
		'yandex.market:admin.grid.list',
		'',
		[
			'GRID_ID' => 'YANDEX_MARKET_ADMIN_COLLECTION_LIST',
			'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
			'PROVIDER_TYPE' => 'Collection',
			'MODEL_CLASS_NAME' => Market\Export\Collection\Model::class,
			'EDIT_URL' => '/bitrix/admin/yamarket_collection_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
			'ADD_URL' => '/bitrix/admin/yamarket_collection_edit.php?lang=' . LANGUAGE_ID,
			'EXPORT_URL' => '/bitrix/admin/yamarket_collection_run.php?lang=' . LANGUAGE_ID,
			'TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_PAGE_TITLE'),
			'NAV_TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_NAV_TITLE'),
			'LIST_FIELDS' => [
				'ID',
				'NAME',
				'ACTIVE',
				'TYPE',
				'SETUP',
				'START_DATE',
				'FINISH_DATE',
			],
			'DEFAULT_LIST_FIELDS' => [
				'ID',
				'NAME',
				'ACTIVE',
				'TYPE',
				'SETUP',
				'START_DATE',
				'FINISH_DATE',
			],
			'CONTEXT_MENU' => [
				[
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_BUTTON_ADD'),
					'LINK' => 'yamarket_collection_edit.php?lang=' . LANG,
					'ICON' => 'btn_new'
				]
			],
			'ROW_ACTIONS' => [
				'RUN' => [
					'URL' => '/bitrix/admin/yamarket_collection_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'unpack',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_RUN')
				],
				'EXPORT_RESULT' => [
					'URL' => '/bitrix/admin/yamarket_collection_result.php?lang=' . LANGUAGE_ID . '&find_collection_id=#ID#&set_filter=Y&apply_filter=Y',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_EXPORT_RESULT')
				],
				'EDIT' => [
					'URL' => '/bitrix/admin/yamarket_collection_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_EDIT'),
					'DEFAULT' => true
				],
				'ACTIVATE' => [
					'ACTION' => 'activate',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_ACTIVATE')
				],
				'DEACTIVATE' => [
					'ACTION' => 'deactivate',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_DEACTIVATE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_DEACTIVATE_CONFIRM')
				],
				'COPY' => [
					'URL' => '/bitrix/admin/yamarket_collection_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#&copy=Y',
					'ICON' => 'copy',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_COPY')
				],
				'DELETE' => [
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_DELETE_CONFIRM')
				]
			],
			'GROUP_ACTIONS' => [
				'activate' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_ACTIVATE'),
				'deactivate' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_DEACTIVATE'),
				'delete' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ROW_ACTION_DELETE')
			]
		]
	);

	Market\Ui\Checker\Announcement::show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';