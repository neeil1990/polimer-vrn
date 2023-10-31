<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isReadAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$APPLICATION->IncludeComponent(
		'yandex.market:admin.grid.list',
		'',
		[
			'GRID_ID' => 'YANDEX_MARKET_ADMIN_CONFIRMATION_LIST',
			'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
			'PROVIDER_TYPE' => 'Data',
			'DATA_CLASS_NAME' => Market\Confirmation\Setup\Table::class,
			'SERVICE' => $service,
			'EDIT_URL' => Market\Ui\Admin\Path::getModuleUrl('confirmation_edit', [ 'lang' => LANGUAGE_ID ]) . '&id=#ID#',
			'ADD_URL' => Market\Ui\Admin\Path::getModuleUrl('confirmation_edit', [ 'lang' => LANGUAGE_ID ]),
			'TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_PAGE_TITLE'),
			'NAV_TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_NAV_TITLE'),
			'LIST_FIELDS' => [
				'ID',
				'DOMAIN',
				'BEHAVIOR',
				'CONTENTS',
			],
			'DEFAULT_FILTER_FIELDS' => [
				'DOMAIN',
			],
			'CONTEXT_MENU' => [
				[
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_BUTTON_ADD'),
					'LINK' => Market\Ui\Admin\Path::getModuleUrl('confirmation_edit', [ 'lang' => LANGUAGE_ID ]),
					'ICON' => 'btn_new'
				]
			],
			'ROW_ACTIONS' => [
				'EDIT' => [
					'URL' => Market\Ui\Admin\Path::getModuleUrl('confirmation_edit', [ 'lang' => LANGUAGE_ID ]) . '&id=#ID#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_ROW_ACTION_EDIT'),
					'DEFAULT' => true
				],
				'DELETE' => [
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_ROW_ACTION_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_ROW_ACTION_DELETE_CONFIRM')
				]
			],
			'GROUP_ACTIONS' => [
				'delete' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_ROW_ACTION_DELETE')
			]
		]
	);

	echo BeginNote('style="max-width: 600px;"');
	echo Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_LIST_NOTE');
	echo EndNote();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';