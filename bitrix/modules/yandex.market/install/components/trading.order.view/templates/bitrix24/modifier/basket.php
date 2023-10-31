<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market\Trading\Entity as TradingEntity;

/** @var $this \CBitrixComponentTemplate */

if (empty($arResult['BASKET'])) { return; }

$hasCis = isset($arResult['BASKET']['COLUMNS']['CIS']);
$hasDigital = isset($arResult['BASKET']['COLUMNS']['DIGITAL']);
$allowItemsEdit = isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::ITEM]);
$allowCisEdit = isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::CIS]);
$allowDigitalEdit = isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::DIGITAL]);
$allowEdit = ($allowItemsEdit || ($allowCisEdit && $hasCis) || ($allowDigitalEdit && $hasDigital));

$arResult['COLUMNS']['COMMON']['elements'][] = [
	'name' => 'BASKET_SECTION',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_TITLE') ?: 'BASKET',
	'type' => 'section',
	'data' => [
		'showButtonPanel' => false,
		'isChangeable' => false,
		'isRemovable' => false,
		'enableToggling' => $allowEdit,
	],
	'elements' => [
		[ 'name' => 'BASKET' ],
	],
];

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'BASKET',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_TITLE'),
	'type' => 'yamarket_basket',
	'editable' => $allowEdit,
	'isDragEnabled' => false,
	'enabledMenu' => false,
];

$arResult['EDITOR']['ENTITY_DATA']['BASKET'] = [
	'VALUE' => $arResult['BASKET'],
	'ACTIONS' => array_keys(array_filter([
		'item' => $allowItemsEdit,
		'cis' => $allowCisEdit,
		'digital' => $allowDigitalEdit,
	])),
];

$arResult['JS_MESSAGES']['Basket'] = [
	'HEADER_INDEX' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_HEADER_INDEX'),
	'HEADER_COUNT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_HEADER_COUNT'),
	'HEADER_PRICE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_HEADER_PRICE'),
	'HEADER_SUBSIDY' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_HEADER_SUBSIDY'),
	'ITEM_DELETE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DELETE'),
	'ITEM_RESTORE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_RESTORE'),
	'ITEM_UNIT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_UNIT'),
	'SUMMARY_WAIT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_SUMMARY_WAIT'),
	'SUMMARY_READY' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_SUMMARY_READY'),
	'ITEM_CIS_MODAL_TITLE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_MODAL_TITLE'),
	'ITEM_CIS_COPY' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_COPY'),
	'ITEM_CIS_HEAD_CIS' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_HEAD_CIS'),
	'ITEM_CIS_HEAD_UIN' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_HEAD_CIS'),
	'ITEM_CIS_HEAD_GTD' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_HEAD_GTD'),
	'ITEM_CIS_HEAD_RNPT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_HEAD_RNPT'),
	'ITEM_CIS_PLACEHOLDER_GTD' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_PLACEHOLDER_GTD'),
	'ITEM_CIS_PLACEHOLDER_RNPT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_PLACEHOLDER_RNPT'),
	'ITEM_CIS_FORMAT' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_FORMAT'),
	'ITEM_CIS_UIN' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_UIN'),
	'ITEM_CIS_CIS' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_CIS'),
	'ITEM_CIS_REQUIRED' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_REQUIRED'),
	'ITEM_DIGITAL_MODAL_TITLE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_MODAL_TITLE'),
	'ITEM_DIGITAL_GROUP' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_GROUP'),
	'ITEM_DIGITAL_ADDITIONAL' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_ADDITIONAL'),
	'ITEM_DIGITAL_CODE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_CODE'),
	'ITEM_DIGITAL_ACTIVATE_TILL' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_ACTIVATE_TILL'),
	'ITEM_DIGITAL_SLIP' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_SLIP'),
];