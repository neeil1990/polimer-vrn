<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

/** @var \CBitrixComponentTemplate $this */

Market\Ui\Assets::loadPlugin('print', 'css');

$this->addExternalCss($templateFolder . '/css/common.css');
$this->addExternalCss($templateFolder . '/css/' . $arResult['SIZE'] . '.css');

$browserDetectPath = Market\Ui\Assets::getPluginPath('lib.browserdetect');
$assets = Main\Page\Asset::getInstance();

$assets->addString('<script src="' . $browserDetectPath . '" data-skip-moving="true"></script>', false, 'PRINT');

?><div class="yamarket-box-label-grid"><?php

foreach ($arResult['ITEMS'] as $item)
{
	?><div class="yamarket-box-label"><?php
		?><div class="yamarket-box-label__contents">
			<?php
			if ($arResult['SERVICE_LOGO_URL'])
			{
				?>
				<img class="yamarket-box-label__logo" src="<?= $arResult['SERVICE_LOGO_URL']; ?>" alt="" />
				<?php
			}
			?>
			<div class="yamarket-box-label__row">
				<div class="yamarket-box-label__cell">
					<span class="yamarket-box-label__label"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_BOX_LABEL_DELIVERY_SERVICE'); ?></span>
					<span class="yamarket-box-label__value"><?= $item['DELIVERY_SERVICE_NAME']; ?></span>
				</div>
				<div class="yamarket-box-label__cell layout--strict">
					<img class="yamarket-box-label__barcode" src="<?= $item['ORDER_ID_BARCODE']; ?>" alt="" />
					<span class="yamarket-box-label__barcode-type"><?= $item['ORDER_ID']; ?></span>
				</div>
			</div>
			<div class="yamarket-box-label__row">
				<div class="yamarket-box-label__cell contents--center">
					<img class="yamarket-box-label__barcode" src="<?= $item['FULFILMENT_ID_BARCODE']; ?>" alt="" />
					<span class="yamarket-box-label__barcode-type"><?= $item['FULFILMENT_ID']; ?></span>
				</div>
			</div>
			<div class="yamarket-box-label__row for--params">
				<div class="yamarket-box-label__param">
					<span class="yamarket-box-label__label"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_BOX_LABEL_BOX_COUNT'); ?></span>
					<span class="yamarket-box-label__value"><?= $item['PLACE']; ?></span>
				</div>
				<div class="yamarket-box-label__param">
					<span class="yamarket-box-label__label"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_BOX_LABEL_BOX_WEIGHT'); ?></span>
					<span class="yamarket-box-label__value"><?= $item['WEIGHT']; ?></span>
				</div>
				<div class="yamarket-box-label__param layout--expand">
					<span class="yamarket-box-label__label"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_BOX_LABEL_DELIVERY_SERVICE_ID'); ?></span>
					<span class="yamarket-box-label__value"><?= $item['DELIVERY_SERVICE_ID']; ?></span>
				</div>
			</div>
			<div class="yamarket-box-label__row">
				<div class="yamarket-box-label__cell">
					<span class="yamarket-box-label__label"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_BOX_LABEL_SELLER'); ?></span>
					<span class="yamarket-box-label__value"><?= $arParams['COMPANY_LEGAL_NAME']; ?></span>
				</div>
				<div class="yamarket-box-label__cell layout--strict">
					<img class="yamarket-box-label__barcode" src="<?= $item['ORDER_NUM_BARCODE']; ?>" alt="" />
					<span class="yamarket-box-label__barcode-type"><?= $item['ORDER_NUM']; ?></span>
				</div>
			</div>
			<div class="yamarket-box-label__footer">
				<?php
				if ($arParams['COMPANY_LOGO'])
				{
					?>
					<img class="yamarket-box-label__company-logo" src="<?= $arParams['COMPANY_LOGO']['SRC']; ?>" alt="" />
					<?
				}
				else
				{
					echo $arParams['COMPANY_NAME'];
				}
				?>
			</div>
		</div><?php
	?></div><?php
}

?></div><?php
