<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */

$this->addExternalJs('/bitrix/js/yandex.market/ui/input/checktoggle.js');

$isPromoTypeGiftWithPurchase = false;

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
	    // make gift iblock id required, if visible

	    if ($specialFieldKey === 'PROMO_GIFT_IBLOCK_ID')
        {
            $field['MANDATORY'] = 'Y';
        }

		$isPromoTypeField = ($specialFieldKey === 'PROMO_TYPE');

		if ($isPromoTypeField)
		{
			$isPromoTypeGiftWithPurchase = ($component->getFieldValue($field) === Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE);
		}

		if ($isPromoTypeField)
		{
			?>
			<tr class="js-plugin" data-plugin="Ui.Input.CheckToggle" data-input-element="select" data-target-element=".js-promo-type-target" data-value="<?= Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE; ?>">
			<?
		}
		else
		{
			?>
			<tr class="<?= $isPromoTypeGiftWithPurchase ? '' : 'is--hidden'; ?> js-promo-type-target">
			<?
		}
		?>
			<td class="adm-detail-content-cell-l" width="40%" align="right" valign="middle">
				<?
				include __DIR__ . '/field-title.php';
				?>
			</td>
			<td class="adm-detail-content-cell-r" width="60%"><?= $component->getFieldHtml($field); ?></td>
		</tr>
		<?
	}
}
