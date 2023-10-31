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
	$primary = $request->get('id') ?: null;
	$useCopy = ($request->get('copy') === 'Y');
	$isNew = ($primary === null || $useCopy);

	if ($service !== '')
	{
		$baseQuery['service'] = $service;
	}

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('TRADING_ADD_TITLE'),
		'TITLE_ADD' => Market\Config::getLang('TRADING_ADD_TITLE_ADD'),
		'BTN_SAVE' => $isNew ? Market\Config::getLang('TRADING_ADD_BTN_ADD') : Market\Config::getLang('TRADING_ADD_BTN_SAVE'),
		'FORM_ID' => 'YANDEX_MARKET_ADMIN_TRADING_ADD',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'LIST_URL' => Market\Ui\Admin\Path::getModuleUrl('trading_list', $baseQuery),
		'SAVE_URL' => $isNew ? Market\Ui\Admin\Path::getModuleUrl('trading_edit', $baseQuery) . '&id=#ID#' : null,
		'PROVIDER_TYPE' => 'TradingSetup',
		'MODEL_CLASS_NAME' => Market\Trading\Setup\Model::class,
		'PRIMARY' => $primary,
		'COPY' => $useCopy,
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
