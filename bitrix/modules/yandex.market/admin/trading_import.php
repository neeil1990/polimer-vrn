<?php

/** @noinspection PhpIncludeInspection */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

/** @var CMain $APPLICATION */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('YANDEX_MARKET_TRADING_IMPORT_TITLE'));

$controller = null;

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		$message = Loc::getMessage('YANDEX_MARKET_TRADING_IMPORT_REQUIRE_MODULE');
		throw new Main\SystemException($message);
	}

	$request = Main\Context::getCurrent()->getRequest();
	$service = trim($request->get('service'));
	$primary = $request->get('id') ?: null;
	$gridId = 'YANDEX_MARKET_ADMIN_TRADING_LIST';
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];

	if ($service !== '')
	{
		$gridId .= str_repeat('_' . Market\Data\TextString::toUpper($service), 2); // bug inside trading_list.php
		$baseQuery['service'] = $service;
	}

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'FORM_ID' => 'YANDEX_MARKET_ADMIN_TRADING_IMPORT',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'LIST_URL' => Market\Ui\Admin\Path::getModuleUrl('trading_list', $baseQuery),
		'GRID_ID' => $gridId,
		'PROVIDER_TYPE' => 'TradingImport',
		'SERVICE' => $service,
		'PRIMARY' => $primary,
		'USE_METRIKA' => 'Y',
		'NOTIFY_UNSAVED' => 'N',
		'DISABLE_REQUIRED_HIGHLIGHT' => 'Y',
		'CONTEXT_MENU' => [
			[
				'ICON' => 'btn_list',
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('trading_list', $baseQuery),
				'TEXT' => Market\Config::getLang('TRADING_IMPORT_CONTEXT_MENU_LIST')
			]
		],
		'TABS' => [
			[ 'name' => Market\Config::getLang('TRADING_IMPORT_TAB_COMMON') ],
		],
		'BTN_SAVE' => Market\Config::getLang('TRADING_IMPORT_BTN_SAVE'),
		'BUTTONS' => [
			[ 'BEHAVIOR' => 'save' ],
		],
	]);
}
catch (Main\SystemException $exception)
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
