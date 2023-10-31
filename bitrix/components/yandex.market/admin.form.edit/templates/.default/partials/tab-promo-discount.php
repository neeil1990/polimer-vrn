<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

Loc::loadMessages(__FILE__);

/** @var $component Yandex\Market\Components\AdminFormEdit */

// promo type message

$promoTypeMessage = null;

if (!empty($arResult['ITEM']['PROMO_TYPE']))
{
	$promoTypeLangKey = str_replace(
		' ',
		'_',
		Market\Data\TextString::toUpper($arResult['ITEM']['PROMO_TYPE'])
	);
	$promoTypeLangTitle = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PROMO_TYPE_' . $promoTypeLangKey);
	$promoTypeLangDescription = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PROMO_TYPE_' . $promoTypeLangKey . '_DESCRIPTION');

	if ($promoTypeLangTitle)
	{
		$promoTypeMessage = new CAdminMessage([
			'TYPE' => 'OK',
			'MESSAGE' => $promoTypeLangTitle,
			'DETAILS' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PROMO_TYPE_' . $promoTypeLangKey . '_DESCRIPTION'),
			'HTML' => true
		]);
	}
}

// split to product fields

$productKeys = [ 'PROMO_PRODUCT', 'PROMO_GIFT' ];
$foundProducts = [];
$hasProducts = false;

foreach ($productKeys as $productKey)
{
    $productIndex = array_search($productKey, $fields);

    if ($productIndex !== false)
    {
        $foundProducts[] = $productKey;

        array_splice($fields, $productIndex, 1);
    }
}

if (empty($foundProducts))
{
    include __DIR__ . '/tab-default.php';

    if ($promoTypeMessage !== null)
	{
		?>
		<tr>
			<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
			<td class="adm-detail-content-cell-r" width="60%">
				<?= $promoTypeMessage->Show(); ?>
			</td>
		</tr>
		<?
	}
}
else
{
    ?>
    <tr>
        <td class="b-form-section-holder" colspan="2">
            <div class="b-form-section fill--primary position--top">
                <table class="adm-detail-content-table edit-table" width="100%">
                    <?
                    include __DIR__ . '/tab-default.php';

					if ($promoTypeMessage !== null)
					{
						?>
						<tr>
							<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
							<td class="adm-detail-content-cell-r" width="60%">
								<?= $promoTypeMessage->Show(); ?>
							</td>
						</tr>
						<?
					}
                    ?>
                </table>
            </div>
        </td>
    </tr>
    <?php

    $specialFields = $foundProducts;

    include __DIR__ . '/special-promo-product.php';
}