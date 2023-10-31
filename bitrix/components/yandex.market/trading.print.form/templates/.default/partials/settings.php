<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var \Yandex\Market\Components\TradingPrintForm $component */
/** @var array $arResult */

foreach ($arResult['SETTINGS'] as $setting)
{
	if ($setting['TYPE'] === 'boolean')
	{
		?>
		<div class="yamarket-shipment-print-setting">
			<?= $component->getSettingHtml($setting); ?>
			<label for="<?= $setting['FIELD_NAME']; ?>"><?= $setting['NAME']; ?></label>
		</div>
		<?php
	}
	else
	{
		?>
		<div class="yamarket-shipment-print-setting">
			<label class="yamarket-shipment-print-setting__label" for="<?= $setting['FIELD_NAME']; ?>"><?= $setting['NAME']; ?></label>
			<?= $component->getSettingHtml($setting); ?>
		</div>
		<?php
	}
}