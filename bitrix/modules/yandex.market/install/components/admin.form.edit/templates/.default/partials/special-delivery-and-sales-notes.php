<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $component \Yandex\Market\Components\AdminFormEdit */

Loc::loadMessages(__FILE__);

$deliveryFieldValue = $deliveryField ? $component->getFieldValue($deliveryField) : null;

?>
<div class="b-form-pill-group js-delivery-summary-group <?= $APPLICATION->ShowProperty($deliveryField['FIELD_NAME'] . '_IS_FILL_CLASS_NAME'); ?>">
	<?
	if ($deliveryField && !empty($arResult['FORMAT_DATA']['SUPPORT_DELIVERY_OPTIONS']))
	{
		$APPLICATION->IncludeComponent('yandex.market:admin.form.field', 'delivery', [
			'INPUT_NAME' => $deliveryField['FIELD_NAME'],
			'HAS_ERROR' => $component->hasFieldError($deliveryField),
			'MULTIPLE' => 'Y',
			'VALUE' => $deliveryFieldValue,
			'GROUP_OUTSIDE' => 'Y'
		]);
	}

	if ($salesNotesField)
	{
		$isGlobal = ($salesNotesField['FIELD_NAME'] === 'SALES_NOTES');

		$APPLICATION->IncludeComponent('yandex.market:admin.form.field', 'salesnotes', [
			'INPUT_NAME' => $salesNotesField['FIELD_NAME'],
			'MULTIPLE' => 'N',
			'VALUE' => $component->getFieldValue($salesNotesField),
			'SALES_NOTES_TIP' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_INTERFACE_FORM_SALES_NOTES_' . ($isGlobal ? 'GLOBAL' : 'IBLOCK'))
		]);
	}
	?>
</div>
