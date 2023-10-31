<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market\Ui;
use Yandex\Market\Ui\UserField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web;

/** @var array $property */

// link

$activityAction = $property['ACTIVITY_ACTION'];
$activityTitle = ($activityAction['TEXT'] !== $property['NAME'] ? $activityAction['TEXT'] : Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_PROPERTY_ACTIVITY_APPLY'));
$activityAttributes = [];

if (isset($activityAction['MENU']))
{
	$menuItems = array_map(static function(array $item) {
		return [
			'TEXT' => $item['TEXT'],
			'ACTION' => $item['METHOD'],
		];
	}, $activityAction['MENU']);

	$activityAttributes['onclick'] = sprintf(
		'BX.adminShowMenu(this, %s, %s); return false;',
		Web\Json::encode($menuItems),
		Web\Json::encode([
			'active_class' => 'is--active',
		])
	);
}
else
{
	$activityAttributes['onclick'] = rtrim($activityAction['METHOD'], ';') . '; return false;';
}

echo sprintf(
	'<a href="#" %s>%s</a>',
	UserField\Helper\Attributes::stringify($activityAttributes),
	$activityTitle
);

// controller init

if (!isset($arResult['ACTIVITY_INIT']))
{
	Ui\Assets::loadPlugins([
		'lib.dialog',
		'Ui.ModalForm',
		'OrderView.Activity',
	]);

	$activityParameters = [
		'url' =>  Ui\Admin\Path::getModuleUrl('trading_order_activity', [
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
	<?php

	$arResult['ACTIVITY_INIT'] = true;
}