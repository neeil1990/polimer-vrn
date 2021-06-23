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
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_TRADING_ADD_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isReadAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_TRADING_ADD_ACCESS_DENIED')
	]);
}
else
{
	$request = Main\Context::getCurrent()->getRequest();
	$service = trim($request->get('service'));
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];
	$primary = !empty($_GET['id']) ? $_GET['id'] : null;

	if ($service !== '')
	{
		$baseQuery['service'] = $service;
	}

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('TRADING_ADD_TITLE'),
		'TITLE_ADD' => Market\Config::getLang('TRADING_ADD_TITLE_ADD'),
		'BTN_SAVE' => $primary !== null ? Market\Config::getLang('TRADING_ADD_BTN_SAVE') : Market\Config::getLang('TRADING_ADD_BTN_ADD'),
		'FORM_ID' => 'YANDEX_MARKET_ADMIN_TRADING_ADD',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'LIST_URL' => Market\Ui\Admin\Path::getModuleUrl('trading_list', $baseQuery),
		'SAVE_URL' => $primary === null ? Market\Ui\Admin\Path::getModuleUrl('trading_edit', $baseQuery) . '&id=#ID#' : null,
		'PROVIDER_TYPE' => 'TradingSetup',
		'MODEL_CLASS_NAME' => Market\Trading\Setup\Model::class,
		'PRIMARY' => $primary,
		'SERVICE' => $service,
		'USE_METRIKA' => 'Y',
		'CONTEXT_MENU' => [
			[
				'ICON' => 'btn_list',
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('trading_list', $baseQuery),
				'TEXT' => Market\Config::getLang('TRADING_ADD_CONTEXT_MENU_LIST')
			]
		],
		'TABS' => [
			[
				'name' => Market\Config::getLang('TRADING_ADD_TAB_COMMON'),
				'fields' => [
					'TRADING_SERVICE',
					'TRADING_BEHAVIOR',
					'SITE_ID',
					'NAME',
					'CODE',
				]
			],
		],
		'BUTTONS' => [
			[ 'BEHAVIOR' => 'save' ],
		],
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
