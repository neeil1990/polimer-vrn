<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

/** @var \CBitrixComponentTemplate $this */

$this->addExternalCss($templateFolder . '/form.css');

Market\Ui\Assets::loadPlugins([
	'Ui.Table.CheckAll',
]);

$itemOrderIds = array_column($arResult['ITEMS'], 'ID', 'ORDER_ID');
$hasFewOrders = count($itemOrderIds) > 1;

include __DIR__ . '/partials/form-prolog.php';
?>
<p class="yamarket-shipment-print-form__intro pos--top"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_BOX_INTRO'); ?></p>
<div class="yamarket-shipment-print-form__box-header">
	<input class="adm-designed-checkbox js-plugin" type="checkbox" checked data-plugin="Ui.Table.CheckAll" data-table-element="#YAMARKET_PRINT_BOX_TABLE" id="YAMARKET_PRINT_BOX_ALL" />
	<label class="adm-designed-checkbox-label" for="YAMARKET_PRINT_BOX_ALL"></label>
	<label for="YAMARKET_PRINT_BOX_ALL">
		<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_SELECT_ALL'); ?>
	</label>
</div>
<div id="YAMARKET_PRINT_BOX_TABLE">
	<?php
	$boxIndex = 0;
	$currentOrderId = null;

	foreach ($arResult['ITEMS'] as $box)
	{
		if ($hasFewOrders && $currentOrderId !== $box['ORDER_ID'])
		{
			$currentOrderId = $box['ORDER_ID'];

			?>
			<div class="yamarket-shipment-print-form__heading">
				<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_BOX_ORDER', [ '#ORDER_ID#' => $box['ORDER_ID'] ]) ?: $box['ORDER_ID']; ?>
			</div>
			<?php
		}

		if ($box['VIRTUAL'])
		{
			?>
			<div class="yamarket-shipment-print-form__box has--warning">
				<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_BOX_VIRTUAL_WARNING'); ?>
			</div>
			<?php
		}
		else
		{
			?>
			<div class="yamarket-shipment-print-form__box">
				<input class="adm-designed-checkbox" type="checkbox" name="entity[]" value="<?= $box['ENTITY_ID']; ?>" checked id="YAMARKET_PRINT_BOX_<?= $boxIndex; ?>" />
				<label class="adm-designed-checkbox-label" for="YAMARKET_PRINT_BOX_<?= $boxIndex; ?>"></label>
				<label for="YAMARKET_PRINT_BOX_<?= $boxIndex; ?>">
					<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_BOX_NUMBER', [ '#NUMBER#' => $box['NUMBER'] ]); ?>
				</label>
			</div>
			<?php
		}

		++$boxIndex;
	}
	?>
</div>
<?php

include __DIR__ . '/partials/settings.php';
include __DIR__ . '/partials/form-epilog.php';
