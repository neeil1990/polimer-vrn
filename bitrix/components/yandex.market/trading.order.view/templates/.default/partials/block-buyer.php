<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

?>
<h3 class="yamarket-properties-title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BUYER_TITLE') ?></h3>
<div class="js-yamarket-order__area" data-type="buyerProperties">
	<?php
	foreach ($arResult['BUYER'] as $property)
	{
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
