<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */
/** @var $isActiveTab bool */

global $USER_FIELD_MANAGER;

Loc::loadMessages(__FILE__);

$specialFieldIndex = 0;

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);
	$fieldValue = $field ? $component->getFieldValue($field) : null;

	if (!empty($fieldValue) && is_array($fieldValue))
	{
		?>
		<tr>
			<td class="b-form-section-holder" colspan="2">
                <div class="b-form-section">
                    <span class="b-heading level--2"><?= $component->getFieldTitle($field); ?></span>
                    <?
                    $promoProductIndex = 0;

                    foreach ($fieldValue as $promoProduct)
                    {
                        $promoProductInputName = $field['FIELD_NAME'] . '[' . $promoProductIndex . ']';
                        $promoProductFilter = isset($promoProduct['FILTER']) ? $promoProduct['FILTER'] : null;
                        $promoProductExportGift = isset($promoProduct['EXPORT_GIFT']) ? $promoProduct['EXPORT_GIFT'] : null;
                        $hasExportGiftFlag = (Market\Data\TextString::getPosition($field['FIELD_NAME'], 'GIFT') !== false);

                        ?>
                        <h3 class="b-heading level--3 <?= $promoProductIndex > 0 ? 'spacing--2x1' : 'pos--top'; ?>">
                            <?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PROMO_PRODUCT_IBLOCK_SECTION', [
                                '#IBLOCK_NAME#' => !empty($promoProduct['CONTEXT']['IBLOCK_NAME']) ? '&laquo;' . $promoProduct['CONTEXT']['IBLOCK_NAME'] . '&raquo;' : '#' . $promoProduct['IBLOCK_ID']
                            ]); ?>
                        </h3>
                        <input type="hidden" name="<?= $promoProductInputName . '[ID]'; ?>" value="<?= isset($promoProduct['ID']) ? $promoProduct['ID'] : ''; ?>" />
                        <input type="hidden" name="<?= $promoProductInputName . '[IBLOCK_ID]'; ?>" value="<?= $promoProduct['IBLOCK_ID'] ?>" />
                        <div class="b-form-panel">
                            <div class="b-form-panel__section fill--primary b-compensate compensate--1x1">
                                <?
                                $APPLICATION->IncludeComponent('yandex.market:admin.form.field', 'filter', [
                                    'INPUT_NAME' => $promoProductInputName . '[FILTER]',
                                    'MULTIPLE' => 'Y',
                                    'VALUE' => $promoProductFilter,
                                    'CONTEXT' => $promoProduct['CONTEXT'],
                                    'FILTER_BASE_NAME' => $field['FIELD_NAME'],
                                    'REFRESH_COUNT_ON_LOAD' => $isActiveTab,
                                    'LANG_ADD_BUTTON' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_PROMO_PRODUCT_ADD_BUTTON'),
                                    'LANG_ADD_BUTTON_TOOLTIP' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_PROMO_PRODUCT_ADD_BUTTON_TOOLTIP'),
                                    'EXPORT_ADD_BUTTON' => $promoProductInputName . 'FILTER_ADD',
									'ALLOW_NAME' => 'Y'
                                ]);

                                if ($hasExportGiftFlag)
                                {
                                    $userField = [
                                        'USER_TYPE' =>
                                            [ 'CLASS_NAME' => 'Yandex\Market\Ui\UserField\BooleanType' ]
                                            + $USER_FIELD_MANAGER->GetUserType('boolean'),
                                        'FIELD_NAME' => $promoProductInputName . '[EXPORT_GIFT]',
                                        'LIST_COLUMN_LABEL' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_PROMO_PRODUCT_EXPORT_GIFT_LABEL'),
                                        'MANDATORY' => 'N',
                                        'MULTIPLE' => 'N',
                                        'EDIT_IN_LIST' => 'Y',
                                        'SETTINGS' => [ 'DEFAULT_VALUE' => '1' ]
                                    ];

                                    ?>
                                    <div class="spacing--1x1">
                                        <?= $component->getFieldHtml($userField, $promoProductExportGift); ?>
                                        <label for="<?= $userField['FIELD_NAME']; ?>">
                                            <?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_SPECIAL_PROMO_PRODUCT_EXPORT_GIFT_LABEL'); ?>
                                        </label>
                                    </div>
                                    <?
                                }

                                $APPLICATION->ShowViewContent($promoProductInputName . 'FILTER_ADD');
                                ?>
                            </div>
                        </div>
                        <?

                        $promoProductIndex++;
                    }

					if ($field['FIELD_NAME'] === 'PROMO_PRODUCT')
					{
						?>
						<div class="b-admin-message-list">
							<?
							echo BeginNote();
							echo Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PROMO_PRODUCT_COLLISION');
							echo EndNote();
							?>
						</div>
						<?
					}
                    ?>
                </div>
			</td>
		</tr>
		<?

        $specialFieldIndex++;
	}
}