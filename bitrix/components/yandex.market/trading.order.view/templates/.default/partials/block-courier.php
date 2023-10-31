<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

?>
<h3 class="yamarket-properties-title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_COURIER_TITLE') ?></h3>
<div class="js-yamarket-order__area" data-type="courierProperties">
	<?php
	foreach ($arResult['COURIER'] as $property)
	{
		?>
		<div class="yamarket-property">
			<div class="yamarket-property__title"><?= $property['NAME'] ?></div>
			<div class="yamarket-property__value"><?= htmlspecialcharsbx($property['VALUE'], ENT_COMPAT, false) ?></div>
		</div>
		<?php
	}
	?>
</div>
