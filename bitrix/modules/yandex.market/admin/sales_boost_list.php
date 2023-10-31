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
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$APPLICATION->IncludeComponent(
		'yandex.market:admin.grid.list',
		'',
		[
			'GRID_ID' => 'YANDEX_MARKET_ADMIN_SALES_BOOST_LIST',
			'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
			'PROVIDER_TYPE' => 'SalesBoost',
			'MODEL_CLASS_NAME' => Market\SalesBoost\Setup\Model::class,
			'EDIT_URL' => '/bitrix/admin/yamarket_sales_boost_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
			'ADD_URL' => '/bitrix/admin/yamarket_sales_boost_edit.php?lang=' . LANGUAGE_ID,
			'EXPORT_URL' => '/bitrix/admin/yamarket_sales_boost_run.php?lang=' . LANGUAGE_ID,
			'TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_PAGE_TITLE'),
			'NAV_TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_NAV_TITLE'),
			'LIST_FIELDS' => [
				'ID',
				'NAME',
				'ACTIVE',
				'SORT',
				'BUSINESS',
				'START_DATE',
				'FINISH_DATE',
			],
			'CONTEXT_MENU' => [
				[
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_BUTTON_ADD'),
					'LINK' => 'yamarket_sales_boost_edit.php?lang=' . LANG,
					'ICON' => 'btn_new'
				]
			],
			'ROW_ACTIONS' => [
				'RUN' => [
					'URL' => '/bitrix/admin/yamarket_sales_boost_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'unpack',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_RUN')
				],
				'EDIT' => [
					'URL' => '/bitrix/admin/yamarket_sales_boost_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_EDIT'),
					'DEFAULT' => true
				],
				'ACTIVATE' => [
					'ACTION' => 'activate',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_ACTIVATE')
				],
				'DEACTIVATE' => [
					'ACTION' => 'deactivate',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_DEACTIVATE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_DEACTIVATE_CONFIRM')
				],
				'COPY' => [
					'URL' => '/bitrix/admin/yamarket_sales_boost_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#&copy=Y',
					'ICON' => 'copy',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_COPY')
				],
				'DELETE' => [
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_DELETE_CONFIRM')
				]
			],
			'GROUP_ACTIONS' => [
				'activate' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_ACTIVATE'),
				'deactivate' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_DEACTIVATE'),
				'delete' => Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_ROW_ACTION_DELETE')
			]
		]
	);

	echo BeginNote('style="max-width: 600px;"');
	echo Loc::getMessage('YANDEX_MARKET_ADMIN_SALES_BOOST_LIST_NOTE');
	echo EndNote();

	Market\Ui\Checker\Announcement::show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';