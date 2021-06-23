<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

Market\Ui\Library::loadConditional('jquery');

Market\Ui\Assets::loadPluginCore();
Market\Ui\Assets::loadFieldsCore();
Market\Ui\Assets::loadPlugins([
	'OrderView.Order',
]);

$blocks = [
	'PROPERTIES',
	'BASKET',
	'SHIPMENT',
];

?>
<div class="js-yamarket-order js-plugin" data-plugin="OrderView.Order" data-base-name="YAMARKET_ORDER">
	<?php
	foreach ($blocks as $block)
	{
		if (empty($arResult[$block])) { continue; }

		include __DIR__ . '/partials/block-' . Market\Data\TextString::toLower($block) . '.php';
	}

	include __DIR__ . '/partials/actions.php';
	?>
</div>
