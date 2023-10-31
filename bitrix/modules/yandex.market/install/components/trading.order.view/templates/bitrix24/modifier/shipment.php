<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market\Trading\Entity as TradingEntity;

/** @var $this \CBitrixComponentTemplate */

if (empty($arResult['SHIPMENT'])) { return; }

$allowEdit = isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::BOX]);

$arResult['COLUMNS']['COMMON']['elements'][] = [
	'name' => 'SHIPMENT_SECTION',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_TITLE') ?: 'SHIPMENT',
	'type' => 'section',
	'data' => [
		'showButtonPanel' => false,
		'isChangeable' => false,
		'isRemovable' => false,
		'enableToggling' => $allowEdit,
	],
	'elements' => [
		[ 'name' => 'SHIPMENT' ],
	],
];

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'SHIPMENT',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_TITLE'),
	'type' => 'yamarket_shipment',
	'editable' => $allowEdit,
	'isDragEnabled' => false,
	'enabledMenu' => false,
];

$arResult['EDITOR']['ENTITY_DATA']['SHIPMENT'] = [
	'VALUE' => $arResult['SHIPMENT'],
	'FULFILMENT_BASE' => $arResult['ORDER_EXTERNAL_ID'],
	'USE_DIMENSIONS' => (CUserOptions::GetOption('yamarket_order_view', 'use_dimensions', 'N', $USER->GetID()) === 'Y'),
];
$arResult['EDITOR']['ENTITY_DATA']['SHIPMENT'] += array_intersect_key($arResult, [
	'BOX_PROPERTIES' => true,
	'BOX_DIMENSIONS' => true,
]);

$arResult['JS_MESSAGES']['Shipment'] = [
	'SHIPMENT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT'),
	'USE_DIMENSIONS' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_USE_DIMENSIONS'),
	'BOX' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX'),
	'BOX_ADD' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX_ADD'),
	'BOX_DELETE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX_DELETE'),
];