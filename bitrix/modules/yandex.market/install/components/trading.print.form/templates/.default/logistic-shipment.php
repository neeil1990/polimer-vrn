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
	<p class="yamarket-shipment-print-form__intro pos--top"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_PRINT_FORM_LOGISTIC_SHIPMENT_TITLE'); ?></p>
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

include __DIR__ . '/partials/settings.php';
include __DIR__ . '/partials/form-epilog.php';
