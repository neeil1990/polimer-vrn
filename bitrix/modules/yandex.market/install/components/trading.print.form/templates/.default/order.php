<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

/** @var \CBitrixComponentTemplate $this */

$this->addExternalCss($templateFolder . '/form.css');

include __DIR__ . '/partials/form-prolog.php';

if (!empty($arResult['ITEMS']))
{
	?>
	<p class="yamarket-shipment-print-form__intro pos--top"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_TITLE'); ?></p>
	<div class="yamarket-shipment-print-form__field">
		<?php
		foreach ($arResult['ITEMS'] as $item)
		{
			?>
			<div class="yamarket-shipment-print-form__box">
				<input type="hidden" name="entity[]" value="<?= $item['ENTITY_ID']; ?>" />
				<input class="adm-designed-checkbox" type="checkbox" checked disabled />
				<label class="adm-designed-checkbox-label"></label>
				<label><?= $item['ID']; ?></label>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

if ($arResult['LOAD_MORE'])
{
	$APPLICATION->RestartBuffer();
}

if (!empty($arResult['ADDITIONAL_ITEMS']) || !empty($arResult['ADDITIONAL_NEXT_PAGE']))
{
	Market\Ui\Assets::loadPlugins([
		'Ui.Table.CheckAll',
		'Ui.Table.RowLabel',
	]);

	$groupSuffix = (int)$arResult['ADDITIONAL_PAGE'] > 1 ? '_' . $arResult['ADDITIONAL_PAGE'] : '';
	$activeGroup = null;
	$isGroupStarted = false;

	if ($arResult['ADDITIONAL_PAGE'] > 1)
	{
		?>
		<p class="yamarket-shipment-print-page-num">
			<span class="yamarket-shipment-print-page-num__contents"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_PAGE_NUM', [
				'#PAGE#' => $arResult['ADDITIONAL_PAGE'],
			]); ?></span>
		</p>
		<?php
	}
	else
	{
		?>
		<p class="yamarket-shipment-print-form__intro"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_ADDITIONAL'); ?></p>
		<p class="yamarket-shipment-print-form__description"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_ADDITIONAL_DESCRIPTION'); ?></p>
		<?php
	}

	foreach ($arResult['ADDITIONAL_ITEMS'] as $item)
	{
		if ($item['GROUP'] !== $activeGroup || !$isGroupStarted)
		{
			$activeGroup = $item['GROUP'];
			$groupTitle = ($activeGroup === 'DEFAULT')
				? Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_LABEL_STATUS_DEFAULT')
				: $item['SUBSTATUS_LANG'];

			if ($isGroupStarted)
			{
				?>
					</tbody>
				</table>
				<?php
			}

			?>
			<div class="yamarket-shipment-print-orders-header">
				<input
					class="adm-designed-checkbox js-plugin"
					type="checkbox"
					data-plugin="Ui.Table.CheckAll"
					data-table-element="#yamarket_PRINT_ORDERS_<?= $activeGroup . $groupSuffix; ?>"
					id="yamarket_PRINT_ORDER_<?= $activeGroup . $groupSuffix; ?>"
				/><?php
				?><label class="adm-designed-checkbox-label" for="yamarket_PRINT_ORDER_<?= $activeGroup . $groupSuffix; ?>"></label><?php
				?><label class="yamarket-shipment-print-orders__gap" for="yamarket_PRINT_ORDER_<?= $activeGroup . $groupSuffix; ?>"><?php
					?><strong><?= $groupTitle; ?></strong><?php
				?></label>
			</div>
			<table class="yamarket-shipment-print-orders" id="yamarket_PRINT_ORDERS_<?= $activeGroup . $groupSuffix; ?>">
				<thead>
					<tr>
						<td>&nbsp;</td>
						<td><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_HEADER_SERVICE_ORDER', [ '#SERVICE_NAME#' => $arResult['SERVICE_NAME_SHORT'] ]); ?></td>
						<td><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_HEADER_BITRIX_ORDER'); ?></td>
						<td class="tar"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_HEADER_DATE_CREATE'); ?></td>
						<td class="tar"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_HEADER_PRICE'); ?></td>
					</tr>
				</thead>
				<tbody>
			<?php

			$isGroupStarted = true;
		}

		?>
		<tr class="yamarket-shipment-print-order js-plugin-click" data-plugin="Ui.Table.RowLabel">
			<td>
				<input class="adm-designed-checkbox" type="checkbox" name="entity[]" value="<?= $item['ENTITY_ID']; ?>" id="yamarket_PRINT_ORDER_<?= $item['ID']; ?>" />
				<label class="adm-designed-checkbox-label" for="yamarket_PRINT_ORDER_<?= $item['ID']; ?>"></label>
			</td>
			<td><?= $item['ID']; ?></td>
			<td><?= $item['ACCOUNT_NUMBER'] ?: '&mdash;'; ?></td>
			<td class="tar"><?= $item['DATE_CREATE']; ?></td>
			<td class="tar"><?=
				$item['TOTAL'] !== null
					? Market\Data\Currency::format($item['TOTAL'], $item['CURRENCY'], false)
					: '&mdash;';
			?></td>
		</tr>
		<?php
	}

	if ($isGroupStarted)
	{
		?>
			</tbody>
		</table>
		<?php
	}

	if (!empty($arResult['ADDITIONAL_NEXT_PAGE']))
	{
		Market\Ui\Assets::loadPlugin('Ui.Table.LoadMore');

		?>
		<button
			class="adm-btn yamarket-btn yamarket-shipment-print-pager js-plugin-click"
			type="button"
			data-plugin="Ui.Table.LoadMore"
			data-url="<?= htmlspecialcharsbx($arResult['ADDITIONAL_NEXT_PAGE_URL']); ?>"
		><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_ORDER_SHOW_MORE'); ?></button>
		<?php
	}
}

if ($arResult['LOAD_MORE'])
{
	die();
}

include __DIR__ . '/partials/form-epilog.php';
