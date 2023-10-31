<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

if (empty($arResult['PRINT_DOCUMENTS'])) { return; }

Market\Ui\Assets::loadPlugins([
	'lib.dialog',
	'lib.printdialog',
]);

Market\Ui\Assets::loadMessages([
	'PRINT_DIALOG_SUBMIT',
	'PRINT_DIALOG_WINDOW_BLOCKED',
]);

$arResult['COLUMNS']['COMMON']['elements'][] = [
	'name' => 'PRINT_SECTION',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_PRINT_TITLE') ?: 'PRINT',
	'type' => 'section',
	'data' => [
		'showButtonPanel' => false,
		'isChangeable' => false,
		'isRemovable' => false,
	],
	'elements' => [
		[ 'name' => 'PRINT' ],
	],
];

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'PRINT',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_PRINT_TITLE'),
	'type' => 'yamarket_print',
	'editable' => false,
];

$arResult['EDITOR']['ENTITY_DATA']['PRINT'] = [
	'ITEMS' => $arResult['PRINT_DOCUMENTS'],
	'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_order_print', [
		'view' => 'dialog',
		'setup' => $arResult['SETUP_ID'],
		'id' => $arResult['ORDER_EXTERNAL_ID'],
	]),
];

$arResult['JS_MESSAGES']['Print'] = [
	'INTRO' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_PRINT_INTRO'),
];