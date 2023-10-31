<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $component Yandex\Market\Components\AdminFormEdit */
/** @var $specialFields array */
/** @var $isActiveTab bool */
/** @global $APPLICATION */

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
					<span class="b-heading level--2"><?= $component->getFieldTitle($field) ?></span>
					<?php
					$productIndex = 0;

					foreach ($fieldValue as $product)
					{
						$productInputName = $field['FIELD_NAME'] . '[' . $productIndex . ']';
						$productFilter = isset($product['FILTER']) ? $product['FILTER'] : null;

						?>
						<h3 class="b-heading level--3 <?= $productIndex > 0 ? 'spacing--2x1' : 'pos--top' ?>">
							<?= Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PRODUCT_FILTER_IBLOCK_SECTION', [
								'#IBLOCK_NAME#' => !empty($product['CONTEXT']['IBLOCK_NAME']) ? '&laquo;' . $product['CONTEXT']['IBLOCK_NAME'] . '&raquo;' : '#' . $product['IBLOCK_ID']
							]) ?>
						</h3>
						<input type="hidden" name="<?= $productInputName . '[ID]' ?>" value="<?= isset($product['ID']) ? $product['ID'] : '' ?>" />
						<input type="hidden" name="<?= $productInputName . '[IBLOCK_ID]' ?>" value="<?= $product['IBLOCK_ID'] ?>" />
						<div class="b-form-panel">
							<div class="b-form-panel__section fill--primary b-compensate compensate--1x1">
								<?php
								$APPLICATION->IncludeComponent('yandex.market:admin.form.field', 'filter', [
									'INPUT_NAME' => $productInputName . '[FILTER]',
									'MULTIPLE' => 'Y',
									'VALUE' => $productFilter,
									'CONTEXT' => $product['CONTEXT'],
									'FILTER_BASE_NAME' => $field['FIELD_NAME'],
									'REFRESH_COUNT_ON_LOAD' => $isActiveTab,
									'LANG_ADD_BUTTON' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_PRODUCT_FILTER_ADD_BUTTON'),
									'EXPORT_ADD_BUTTON' => $productInputName . 'FILTER_ADD',
									'ALLOW_NAME' => 'Y'
								]);

								$APPLICATION->ShowViewContent($productInputName . 'FILTER_ADD');
								?>
							</div>
						</div>
						<?php

						$productIndex++;
					}
					?>
				</div>
			</td>
		</tr>
		<?php

		$specialFieldIndex++;
	}
}