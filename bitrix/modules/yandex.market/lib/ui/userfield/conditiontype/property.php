<?php

namespace Yandex\Market\Ui\UserField\ConditionType;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Property
{
	const USER_TYPE = 'ym_condition_type';

	public static function GetUserTypeDescription()
	{
		$langKey = static::getLangKey();

		return [
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => static::USER_TYPE,
			'DESCRIPTION' => Market\Config::getLang($langKey . 'DESCRIPTION'),
			'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
			'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
			'GetAdminFilterHTML' => [__CLASS__, 'GetAdminFilterHTML'],
			'GetUIFilterProperty' => [__CLASS__, 'GetUIFilterProperty'],
			'AddFilterFields' => [__CLASS__, 'AddFilterFields'],
		];
	}

	public static function GetPropertyFieldHtml($property, $value, $controlName)
	{
		$langKey = static::getLangKey();
		$result = '<select name="'.$controlName['VALUE'].'">';

		if ($property['IS_REQUIRED'] !== 'Y')
		{
			$result .= '<option value="">' . Market\Config::getLang($langKey . 'VALUE_EMPTY') . '</option>';
		}

		$result .= static::GetOptionsHtml([$value['VALUE']]);
		$result .= '</select>';

		if ($property['WITH_DESCRIPTION'] === 'Y')
		{
			$result .=
				'&nbsp;<span>'
				. Market\Config::getLang($langKey . 'VALUE_DESCRIPTION') . ':&nbsp;'
				. '<input type="text" name="'.htmlspecialcharsex($controlName['DESCRIPTION']).'" value="'.htmlspecialcharsex($value['DESCRIPTION']).'" size="30">'
 				. '</span>';
		}

		return  $result;
	}

	public static function GetAdminListViewHTML($property, $value, $controlName)
	{
		$propertyValueList = (array)$value['VALUE'];
		$displayValueList = [];

		foreach ($propertyValueList as $propertyValue)
		{
			if ($propertyValue === '')
			{
				// nothing
			}
			else if (in_array($propertyValue, static::GetOptions(), true))
			{
				$displayValueList[] = static::GetOptionLabel($propertyValue);
			}
			else
			{
				$displayValueList[] = $propertyValue;
			}
		}

		return implode(' / ', $displayValueList);
	}

	public static function GetAdminFilterHTML($property, $controlName)
	{
		$langKey = static::getLangKey();
		$filterRequest = static::GetFilterRequestValue($controlName);
		$result = '<select name="'.$controlName['VALUE'].'[]">';
		$result .= '<option value="">' . Market\Config::getLang($langKey . 'VALUE_ANY') . '</option>';
		$result .= static::GetOptionsHtml($filterRequest);
		$result .= '</select>';

		return  $result;
	}

	public static function GetUIFilterProperty($property, $controlName, &$fields)
	{
		$options = static::GetOptions();
		$fields['type'] = 'list';
		$fields['items'] = [];

		foreach ($options as $option)
		{
			$fields['items'][$option] = static::GetOptionLabel($option);
		}
	}

	public static function AddFilterFields($property, $controlName, &$listFilter, &$hasFilter)
	{
		$hasFilter = false;
		$filterRequest = static::GetFilterRequestValue($controlName);

		if (!empty($filterRequest))
		{
			$hasFilter = true;
			$listFilter['=PROPERTY_' . $property['ID']] = $filterRequest;
		}
	}

	protected static function GetFilterRequestValue($controlName)
	{
		$requestValueList = [];

		if (isset($_REQUEST[$controlName['VALUE']]) && (is_array($_REQUEST[$controlName['VALUE']]) || (int)$_REQUEST[$controlName['VALUE']] > 0))
		{
			$requestValueList = (array)$_REQUEST[$controlName['VALUE']];
		}
		else if (isset($GLOBALS[$controlName['VALUE']]) && (is_array($GLOBALS[$controlName['VALUE']]) || (int)$GLOBALS[$controlName['VALUE']] > 0))
		{
			$requestValueList = (array)$GLOBALS[$controlName['VALUE']];
		}

		return $requestValueList;
	}

	public static function GetOptions()
	{
		return [
			Market\Type\ConditionType::TYPE_LIKE_NEW,
			Market\Type\ConditionType::TYPE_USED
		];
	}

	public static function GetOptionLabel($option)
	{
		$langKey = static::getLangKey();

		return Market\Config::getLang(
			$langKey
			. 'OPTION_'
			. Market\Data\TextString::toUpper($option)
		);
	}

	protected static function GetOptionsHtml($selectedValues)
	{
		$options = static::getOptions();
		$result = '';

		foreach ($options as $option)
		{
			$isSelected = in_array($option, $selectedValues, true);

			$result .=
				'<option value="' . $option . '"' . ($isSelected ? ' selected' : '') . '>'
				. static::GetOptionLabel($option)
				. '</option>';
		}

		return $result;
	}

	protected static function getLangKey()
	{
		return 'UI_USERFIELD_CONDITION_TYPE_PROPERTY_';
	}
}