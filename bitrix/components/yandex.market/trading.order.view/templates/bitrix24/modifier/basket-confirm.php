<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Yandex\Market\Trading\Entity as TradingEntity;

if (
	empty($arResult['BASKET'])
	|| empty($arResult['ITEMS_CHANGE_REASON'])
	|| !isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::ITEM])
) { return; }

Extension::load([
	'ui.layout-form',
	'ui.alert',
	'ui.dialogs.messagebox',
]);

foreach ($arResult['COLUMNS']['COMMON']['elements'] as &$configItem)
{
	if ($configItem['name'] !== 'BASKET_SECTION') { continue; }

	$configItem['elements'][] = [ 'name' => 'BASKET_CONFIRM' ];
}
unset($configItem);

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'BASKET_CONFIRM',
	'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_TITLE'),
	'type' => 'yamarket_basket_confirm',
	'editable' => true,
];

$arResult['EDITOR']['ENTITY_DATA']['BASKET_CONFIRM'] = [
	'ITEMS_CHANGE_REASON' => $arResult['ITEMS_CHANGE_REASON'],
];

$arResult['JS_MESSAGES']['BasketConfirm'] = [
	'MODAL_TITLE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_MODAL_TITLE'),
	'FORM_INTRO' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_FORM_INTRO'),
	'REASON' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_REASON'),
	'PRODUCTS' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_PRODUCTS'),
	'ITEM_CHANGE' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_ITEM_CHANGE'),
];