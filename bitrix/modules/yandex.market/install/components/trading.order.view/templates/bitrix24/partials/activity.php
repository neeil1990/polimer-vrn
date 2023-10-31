<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web;

if (!$arResult['HAS_ACTIVITY']) { return; }

Ui\Assets::loadPluginCore();
Ui\Assets::loadPlugins([
	'lib.dialog',
	'Ui.ModalForm',
	'OrderView.Activity',
]);

$activityParameters = [
	'url' => Ui\Admin\Path::getModuleUrl('trading_order_activity', [
		'view' => 'dialog',
		'setup' => $arResult['SETUP_ID'],
		'alone' => 'Y',
		'id' => $arResult['ORDER_EXTERNAL_ID'],
	]),
	'lang' => [
		'ACTIVITY_SUBMIT' => Loc::getMessage('YANDEX_MARKET_ACTIVITY_DIALOG_SUBMIT'),
		'ACTIVITY_ERROR' => Loc::getMessage('YANDEX_MARKET_ACTIVITY_DIALOG_ERROR'),
	],
];

?>
<script>
	(function() {
		const OrderView = BX.namespace('YandexMarket.OrderView');

		OrderView.activity = new OrderView.Activity('#YAMARKET_ORDER_VIEW', <?= Web\Json::encode($activityParameters) ?>);
	})();
</script>