<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

$skip = [];

if (!empty($arResult['ORDER']['FAKE']))
{
	$skip['fake'] = true;

	?>
	<div class="yamarket-label type--danger"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_LABEL_FAKE') ?></div>
	<?php
}

?>
<h3 class="yamarket-properties-title pos--top"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_PROPERTY_TITLE') ?></h3>
<div class="js-yamarket-order__area" data-type="orderProperties">
	<?php
	foreach ($arResult['PROPERTIES'] as $property)
	{
		if (isset($skip[$property['ID']])) { continue; }

		?>
		<div class="yamarket-property">
			<div class="yamarket-property__title"><?= $property['NAME']; ?></div>
			<div class="yamarket-property__value">
				<?= htmlspecialcharsbx($property['VALUE'], ENT_COMPAT, false); ?>
				<?php
				if (isset($property['ACTIVITY_ACTION']))
				{
					include __DIR__ . '/property-activity.php';
				}
				?>
			</div>
		</div>
		<?php
	}
	?>
</div>
