<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;

class BooleanType
{
	use Concerns\HasCompatibleExtends;

	const VALUE_Y = '1';
	const VALUE_N = '0';

	public static function getCommonExtends()
	{
		return Main\UserField\Types\BooleanType::class;
	}

	public static function getCompatibleExtends()
	{
		return \CUserTypeBoolean::class;
	}

	public static function GetUserTypeDescription()
	{
		return static::callParent('GetUserTypeDescription');
	}

	public static function OnBeforeSave($arUserField, $value)
	{
		return static::callParent('OnBeforeSave', [$arUserField, $value]);
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterHTML', [$arUserField, $arHtmlControl]);
	}

	public static function GetFilterData($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterData', [$arUserField, $arHtmlControl]);
	}

	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		$icon = '';
		$display = static::callParent('GetAdminListViewHTML', [$userField, $htmlControl]);

		if (isset($userField['SETTINGS']['USE_ICON']) && $userField['SETTINGS']['USE_ICON'] === 'Y')
		{
			$icon = static::renderIcon($userField, $htmlControl);
		}

		return $icon . $display;
	}

	public static function GetEditFormHTML($userField, $htmlControl)
	{
		$value = (string)$htmlControl['VALUE'] !== ''
			? (int)$htmlControl['VALUE']
			: (int)$userField['SETTINGS']['DEFAULT_VALUE'];
		$isChecked = ($value > 0);
		$isDisabled = ($userField['EDIT_IN_LIST'] !== 'Y');

		$result = sprintf('<input class="is--persistent" type="hidden" value="0" name="%s" />', $htmlControl['NAME']);
		$result .= '<label>';
		$result .= sprintf(
			'<input class="adm-designed-checkbox" type="checkbox" value="1" name="%s" %s />',
			$htmlControl['NAME'],
			($isChecked ? ' checked' : '') . ($isDisabled ? ' disabled="disabled"' : '')
		);
		$result .= '<span class="adm-designed-checkbox-label"></span>';
		$result .= '</label>';

		return $result;
	}

	protected static function renderIcon($userField, $htmlControl)
	{
		$value = (string)Helper\Value::asSingle($userField, $htmlControl);
		$color = $value === static::VALUE_Y ? 'green': 'grey';

		return sprintf(
			'<img class="b-log-icon" src="/bitrix/images/yandex.market/%s.gif" width="14" height="14" alt="" />',
			$color
		);
	}

	/** @noinspection PhpUnused */
	public static function ymExportValue(array $userField, $value, array $row = null)
	{
		$value = (string)$value !== '' ? (int)$value : (int)$userField['SETTINGS']['DEFAULT_VALUE'];

		return $value > 0;
	}
}