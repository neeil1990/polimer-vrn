<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

Market\Ui\Assets::loadPlugin('print', 'css');

$tableRowIndex = 0;
$pageIndex = 0;

foreach ($arResult['PAGES'] as $page)
{
	$pageNumber = $pageIndex + 1;
	$isFirstPage = ($pageIndex === 0);
	$isLastPage = ($pageIndex + 1 === $arResult['PAGES_COUNT']);

	?>
	<div class="yamarket-act-page">
		<div class="yamarket-act-page__header">
			<div class="yamarket-act-page__number"><?= $pageNumber . '&nbsp;/&nbsp;' . $arResult['PAGES_TOTAL']; ?></div>
			<div class="yamarket-act-page__title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_TITLE', [
				'#NUMBER#' => $arResult['DOCUMENT_NUMBER'],
			]); ?></div>
		</div>
		<?php
		if ($isFirstPage)
		{
			?>
			<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_DATE', [ '#YEAR#' => date('Y') ]); ?></p>
			<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_CLIENT'); ?>: <?= $arParams['CLIENT_NAME']; ?></p>
			<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_EXECUTOR'); ?>: ________________________________</p>
			<p class="yamarket-act__intro"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_INTRO'); ?></p>
			<?php
		}
		?>
		<table class="yamarket-act__table">
			<colgroup>
				<col style="width:8.319467554076539%">
				<col style="width:21.630615640599%">
				<col style="width:21.630615640599%">
				<col style="width:17.3044925124792%">
				<col style="width:14.559068219633941%">
				<col style="width:16.55574043261231%">
			</colgroup>
			<thead>
			<tr>
				<th><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_NUMBER'); ?></th>
				<th><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_CLIENT_ORDER_NUMBER'); ?></th>
				<th><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_EXECUTOR_ORDER_NUMBER'); ?></th>
				<th><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_DECLARED_PRICE'); ?></th>
				<th><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_WEIGHT'); ?></th>
				<th><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_CAPACITY'); ?></th>
			</tr>
			</thead>
			<tbody>
				<?php
				foreach ($page['ITEMS'] as $tableRow)
				{
					?>
					<tr class="row doc-c-table__tr">
						<td><?= $tableRowIndex + 1; ?></td>
						<td><?= $tableRow['ACCOUNT_NUMBER']; ?></td>
						<td><?= $tableRow['ID']; ?></td>
						<td><?= $tableRow['TOTAL'] !== null ? Market\Data\Price::format($tableRow['TOTAL']) : '&mdash;'; ?></td>
						<td><?= $tableRow['WEIGHT'] !== null ? Market\Data\Weight::format($tableRow['WEIGHT']) : '&mdash;'; ?></td>
						<td><?= $tableRow['CAPACITY'] !== null ? Market\Data\Number::format($tableRow['CAPACITY']) : '&mdash;'; ?></td>
					</tr>
					<?

					++$tableRowIndex;
				}

				if ($isLastPage)
				{
					?>
					<tr>
						<td></td>
						<td></td>
						<td><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_TABLE_TOTAL'); ?></td>
						<td><?= Market\Data\Price::format($arResult['TOTAL']['TOTAL']); ?></td>
						<td><?= Market\Data\Weight::format($arResult['TOTAL']['WEIGHT']); ?></td>
						<td><?= Market\Data\Number::format($arResult['TOTAL']['CAPACITY']); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
		if ($isLastPage)
		{
			if ($arResult['PAGES_COUNT'] < $arResult['PAGES_TOTAL'])
			{
				?>
				</div>
				<div class="yamarket-act-page">
					<div class="yamarket-act-page__header">
						<div class="yamarket-act-page__number"><?= ($pageNumber + 1) . '&nbsp;/&nbsp;' . $arResult['PAGES_TOTAL']; ?></div>
						<div class="yamarket-act-page__title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_TITLE', [
							'#NUMBER#' => $arResult['DOCUMENT_NUMBER'],
						]); ?></div>
					</div>
				<?php
			}

			?>
			<div class="yamarket-act__captions"><?php
				?><div class="yamarket-act__caption">
					<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_CLIENT_SIGN_TITLE'); ?></p>
					<p class="yamarket-act__holder">&nbsp;</p>
					<p class="yamarket-act__holder">&nbsp;</p>
					<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_CLIENT_SIGN_PROPS'); ?></p>
					<p>&nbsp;</p>
					<div class="yamarket-act-sign">
						<span class="yamarket-act-sign__origin">&nbsp;</span>
						<span class="yamarket-act-sign__details">(<span class="yamarket-act-sign__details-holder">&nbsp;</span>)</span>
					</div>
					<div class="yamarket-act-sign-caption">
						<span class="yamarket-act-sign-caption__origin"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_SIGN_ORIGIN'); ?></span>
						<span class="yamarket-act-sign-caption__details"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_SIGN_DETAILS'); ?></span>
					</div>
					<p><strong><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_PLACE_PRINTING'); ?></strong></p>
				</div><?php
				?><div class="yamarket-act__caption">
					<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_EXECUTOR_SIGN_TITLE'); ?></p>
					<p class="yamarket-act__holder">&nbsp;</p>
					<p class="yamarket-act__holder">&nbsp;</p>
					<p><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_EXECUTOR_SIGN_PROPS'); ?></p>
					<p>&nbsp;</p>
					<div class="yamarket-act-sign">
						<span class="yamarket-act-sign__origin">&nbsp;</span>
						<span class="yamarket-act-sign__details">(<span class="yamarket-act-sign__details-holder">&nbsp;</span>)</span>
					</div>
					<div class="yamarket-act-sign-caption">
						<span class="yamarket-act-sign-caption__origin"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_SIGN_ORIGIN'); ?></span>
						<span class="yamarket-act-sign-caption__details"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_SIGN_DETAILS'); ?></span>
					</div>
					<p><strong><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_DELIVERY_ACT_PLACE_PRINTING'); ?></strong></p>
				</div><?php
			?></div>
			<?php
		}
		?>
	</div>
	<?php

	++$pageIndex;
}