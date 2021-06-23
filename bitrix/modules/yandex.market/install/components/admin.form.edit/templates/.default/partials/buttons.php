<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

foreach ($arResult['BUTTONS'] as $button)
{
	$behavior = isset($button['BEHAVIOR']) ? $button['BEHAVIOR'] : null;
	$buttonName = isset($button['NAME']) ? $button['NAME'] : null;
	$buttonAttributes = isset($button['ATTRIBUTES']) ? $button['ATTRIBUTES'] : [];

	switch ($behavior)
	{
		case 'previous':
			if ($arResult['STEP'] === 0)
			{
				$buttonName = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_CANCEL');
				$buttonAttributes += [
					'name' => 'cancel',
					'value' => 'Y',
				];
			}
			else
			{
				$buttonName = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_PREV_STEP');
				$buttonAttributes += [
					'name' => 'stepAction',
					'value' => 'previous',
				];
			}
		break;

		case 'next':
			if ($arResult['STEP_FINAL'])
			{
				$buttonName = $arParams['BTN_SAVE'] ?: Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_SAVE');
				$buttonAttributes += [
					'class' => 'adm-btn adm-btn-save ' . ($arParams['ALLOW_SAVE'] ? '' : 'adm-btn-disabled'),
					'name' => 'save',
					'value' => 'Y',
					'disabled' => !$arParams['ALLOW_SAVE'],
				];
			}
			else
			{
				$buttonName = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_NEXT_STEP');
				$buttonAttributes += [
					'class' => 'adm-btn adm-btn-save',
					'name' => 'stepAction',
					'value' => 'next',
				];
			}
		break;

		case 'save':
			$buttonName = $arParams['BTN_SAVE'] ?: Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_SAVE');
			$buttonAttributes += [
				'class' => 'adm-btn adm-btn-save ' . ($arParams['ALLOW_SAVE'] ? '' : 'adm-btn-disabled'),
				'name' => 'save',
				'value' => 'Y',
				'disabled' => !$arParams['ALLOW_SAVE'],
			];
		break;

		case 'apply':
			$buttonName = $arParams['BTN_APPLY'] ?: Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_APPLY');
			$buttonAttributes += [
				'class' => 'adm-btn ' . ($arParams['ALLOW_SAVE'] ? '' : 'adm-btn-disabled'),
				'name' => 'apply',
				'value' => 'Y',
				'disabled' => !$arParams['ALLOW_SAVE'],
			];
		break;

		case 'reset':
			$buttonName = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_BTN_RESET');
			$buttonAttributes += [
				'class' => 'adm-btn',
				'type' => 'reset',
			];
		break;
	}

	$buttonAttributes += [
		'class' => 'adm-btn',
		'type' => 'submit',
	];
	$buttonAttributesString = Market\Ui\UserField\Helper\Attributes::stringify($buttonAttributes);

	?>
	<button <?= $buttonAttributesString; ?>><?= $buttonName; ?></button>
	<?php
}