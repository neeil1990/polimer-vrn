<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $component \CBitrixComponent */

include __DIR__ . '/partials/messages.php';
include __DIR__ . '/partials/activity.php';

?>
<div class="yamarket-editor-wrapper" id="YAMARKET_ORDER_VIEW">
	<span class="yamarket-editor-wrapper__title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_TITLE', [
		'#EXTERNAL_ID#' => $arResult['ORDER_EXTERNAL_ID'],
	]) ?></span>
	<button class="ui-btn ui-btn-link ui-btn-xs yamarket-editor-wrapper__refresh" type="button" onclick="BX.UI.EntityEditor.items['yamarket_order_tab'].reload()"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_REFRESH') ?></button>
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:ui.form',
		'',
		$arResult['EDITOR'] + [
			'GUID' => 'yamarket_order_tab',
			'CONFIG_ID' => 'yamarket_order_tab_' . $arParams['SETUP_ID'],
			'ENTITY_TYPE_ID' => null,
			'ENTITY_TYPE_NAME' => 'yamarket_order',
			'ENTITY_ID' => $arResult['ORDER']['ID'],
			'INITIAL_MODE' => 'view',
			'ENABLE_SECTION_EDIT' => false,
			'ENABLE_SECTION_CREATION' => false,
			'ENABLE_USER_FIELD_CREATION' => false,
			'ENABLE_SECTION_DRAG_DROP' => false,
			'ENABLE_FIELD_DRAG_DROP' => false,
			'ENABLE_FIELDS_CONTEXT_MENU' => false,
			'FORCE_DEFAULT_CONFIG' => true,
			'ENABLE_AJAX_FORM' => true,
			'READ_ONLY' => false,
			'SERVICE_URL' => '/bitrix/components/yandex.market/trading.order.view/ajax.php',
			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $component->getName(),
				'ACTION_NAME' => 'save',
				'SIGNED_PARAMETERS' => $component->getSignedParameters(),
				'RELOAD_ACTION_NAME' => 'reload',
			],
			'CONTEXT_ID' => 'yamarket_order_' . $arResult['ORDER']['ID'],
			'CONTEXT' => [
				'INTERNAL_ID' => $arResult['ORDER_INTERNAL_ID'],
				'ID' => $arResult['ORDER_EXTERNAL_ID'],
				'SETUP_ID' => $arResult['SETUP_ID'],
				'ACCOUNT_NUMBER' => $arResult['ORDER_ACCOUNT_NUMBER'],
			],
		],
		$component,
		[ 'HIDE_ICONS' => 'Y' ]
	);
	?>
</div>
