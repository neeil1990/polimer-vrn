<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;

if (empty($arResult['BASKET']['ITEMS'])) { return; }

Market\Ui\Assets::loadPlugins([
	'OrderView.Basket',
	'OrderView.BasketItem',
]);

if (isset($arResult['BASKET']['COLUMNS']['CIS']))
{
	Market\Ui\Assets::loadPlugins([
		'OrderView.BasketItemCisSummary',
		'OrderView.BasketItemCis',
	]);
}

if (isset($arResult['BASKET']['COLUMNS']['DIGITAL']))
{
	Market\Ui\Assets::loadPlugins([
		'OrderView.BasketItemDigitalSummary',
		'OrderView.BasketItemDigital',
	]);
}

Market\Ui\Assets::loadMessages([
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_MODAL_TITLE',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_REQUIRED',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_SUMMARY_EMPTY',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_SUMMARY_WAIT',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_SUMMARY_READY',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_REQUIRED',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_SUMMARY_WAIT',
	'T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_SUMMARY_READY',
]);

$allowItemsEdit = isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::ITEM]);
$columns = $arResult['BASKET']['COLUMNS'];
$columnsCount = count($arResult['BASKET']['COLUMNS']) + 1;
$baseInputName = 'YAMARKET_ORDER[BASKET]';

if ($allowItemsEdit)
{
	$columns['DELETE'] = Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_ACTION_DELETE');
	++$columnsCount;
}

if ($allowItemsEdit && !empty($arResult['ITEMS_CHANGE_REASON']))
{
	Market\Ui\Assets::loadMessages([
		'T_TRADING_ORDER_VIEW_BASKET_CONFIRM_MODAL_TITLE',
		'T_TRADING_ORDER_VIEW_BASKET_CONFIRM_ITEM_CHANGE',
	]);

	Market\Ui\Assets::loadPlugins([
		'OrderView.BasketConfirmSummary',
		'OrderView.BasketConfirmForm',
	]);

	?>
	<div class="js-yamarket-order__field" data-plugin="OrderView.BasketConfirmSummary" data-name="BASKET_CONFIRM">
		<div class="is--hidden js-yamarket-basket-confirm-summary__modal">
			<?php
			include __DIR__ . '/basket-confirm.php';
			?>
		</div>
	</div>
	<?php
}
?>
<h2 class="yamarket-section-title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_TITLE'); ?></h2>
<div class="yamarket-basket-wrapper adm-s-order-table-ddi js-yamarket-order__field" data-plugin="OrderView.Basket" data-name="BASKET">
	<table class="yamarket-basket-table adm-s-order-table-ddi-table adm-s-bus-ordertable-option">
		<thead>
			<tr>
				<td class="tal"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_INDEX'); ?></td>
				<?php
				foreach ($columns as $columnTitle)
				{
					?>
					<td class="tal"><?= $columnTitle; ?></td>
					<?php
				}
				?>
			</tr>
		</thead>
		<tbody>
			<tr></tr><?php // hack for bitrix css ?>
			<?php
			$itemIndex = 0;

			foreach ($arResult['BASKET']['ITEMS'] as $item)
			{
				$itemInputName = sprintf($baseInputName . '[%s]', $itemIndex);

				?>
				<tr class="bdb-line yamarket-basket-item js-yamarket-basket-item" data-plugin="OrderView.BasketItem" data-id="<?= $item['ID']; ?>">
					<td class="tal">
						<input class="js-yamarket-basket-item__data" type="hidden" name="<?= $itemInputName . '[ID]' ?>" value="<?= htmlspecialcharsbx($item['ID']) ?>" data-name="ID" />
						<?= $item['INDEX']; ?>
					</td>
					<?php
					foreach ($columns as $column => $columnTitle)
					{
						$columnValue = isset($item[$column]) ? $item[$column] : null;
						$columnFormattedKey = $column . '_FORMATTED';

						if (isset($item[$columnFormattedKey]))
						{
							$columnFormatted = $item[$columnFormattedKey];
						}
						else if ($columnValue !== null)
						{
							$columnFormatted = $columnValue;
						}
						else
						{
							$columnFormatted = '&mdash;';
						}

						switch ($column)
						{
							case 'CIS':
								include __DIR__ . '/basket-column-cis.php';
							break;

							case 'DIGITAL':
								include __DIR__ . '/basket-column-digital.php';
							break;

							case 'COUNT':
								?>
								<td class="tal for--<?= Market\Data\TextString::toLower($column); ?>">
									<?php
									if ($allowItemsEdit)
									{
										?>
										<input class="js-yamarket-basket-item__data" type="hidden" name="<?= $itemInputName . '[INITIAL_COUNT]' ?>" value="<?= (float)$columnValue ?>" data-name="INITIAL_COUNT" />
										<input
											class="adm-input yamarket-basket-item__count js-yamarket-basket-item__data"
											type="number"
											name="<?= $itemInputName . '[COUNT]' ?>"
											value="<?= (float)$columnValue ?>"
											min="1"
											max="<?= (float)$columnValue ?>"
											step="1"
											data-name="COUNT"
										/>
										<?php
									}
									else
									{
										echo sprintf(
											'%s %s',
											(float)$columnValue,
											Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_MEASURE')
										);
									}
									?>
								</td>
								<?php
							break;

							case 'DELETE':
								?>
								<td class="tal for--<?= Market\Data\TextString::toLower($column); ?>">
									<label>
										<input class="adm-designed-checkbox js-yamarket-basket-item__data" type="checkbox" name="<?= $itemInputName . '[DELETE]'; ?>" value="Y" data-name="DELETE">
										<span class="adm-designed-checkbox-label"></span>
									</label>
								</td>
								<?php
							break;

							case 'BOX_COUNT':
								?>
								<td class="tal for--<?= Market\Data\TextString::toLower($column); ?>">
									<?= (float)$columnValue; ?>
									<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_MEASURE'); ?>
								</td>
								<?php
							break;

							case 'SUBSIDY':
								$hasPromos = !empty($item['PROMOS']);

								?>
								<td class="tal for--<?= Market\Data\TextString::toLower($column); ?>">
									<?php
									if ($columnValue !== null || !$hasPromos)
									{
										echo $columnFormatted;
									}

									if ($hasPromos)
									{
										foreach ($item['PROMOS'] as $promo)
										{
											echo sprintf('<div>%s</div>', $promo);
										}
									}
									?>
								</td>
								<?php
							break;

							default:
								?>
								<td class="tal for--<?= Market\Data\TextString::toLower($column); ?> js-yamarket-basket-item__data" data-name="<?= $column ?>"><?= $columnFormatted; ?></td>
								<?php
							break;
						}
					}
					?>
				</tr>
				<?php

				++$itemIndex;
			}
			?>
		</tbody>
		<?php
		if (!empty($arResult['BASKET']['SUMMARY']))
		{
			?>
			<tfoot>
				<tr>
					<td class="yamarket-basket-summary js-yamarket-order__area" data-type="basketSummary" colspan="<?= $columnsCount; ?>">
						<?php
						$isFirstSummaryItem = true;

						foreach ($arResult['BASKET']['SUMMARY'] as $summaryItem)
						{
							echo $isFirstSummaryItem ? '' : '<br />';
							echo $summaryItem['NAME'] . ': ' . $summaryItem['VALUE'];

							$isFirstSummaryItem = false;
						}
						?>
					</td>
				</tr>
			</tfoot>
			<?php
		}
		?>
	</table>
</div>