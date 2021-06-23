<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class EnumerationType
{
	use Concerns\HasMultipleRow;
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends()
	{
		return Main\UserField\Types\EnumType::class;
	}

	public static function getCompatibleExtends()
	{
		return \CUserTypeEnum::class;
	}

	public static function getUserTypeDescription()
	{
		$result = static::callParent('getUserTypeDescription');

		if (!empty($result['USE_FIELD_COMPONENT']))
		{
			$result['USE_FIELD_COMPONENT'] = false;
		}

		return $result;
	}

	public static function GetList($arUserField)
	{
		$values = (array)$arUserField['VALUES'];

		$result = new \CDBResult();
		$result->InitFromArray($values);

		return $result;
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterHTML', [$arUserField, $arHtmlControl]);
	}

	public static function GetFilterData($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterData', [$arUserField, $arHtmlControl]);
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$attributes = Helper\Attributes::extractFromSettings($arUserField['SETTINGS']);

		if (isset($arUserField['SETTINGS']['DISPLAY']) && $arUserField['SETTINGS']['DISPLAY'] === 'CHECKBOX')
		{
			$arUserField['MANDATORY'] = 'Y'; // hide no value for all variants

			$result = static::callParent('GetEditFormHTML', [$arUserField, $arHtmlControl]);
			$result = Helper\Attributes::insert($result, $attributes);
		}
		else
		{
			$settings = static::makeSelectViewSettings($arUserField);
			$attributes += static::makeSelectViewAttributes($arUserField);
			$attributes['name'] = $arHtmlControl['NAME'];

			// value

			if ($arUserField['ENTITY_VALUE_ID'] < 1 && (string)$arUserField['SETTINGS']['DEFAULT_VALUE'] !== '')
			{
				$arHtmlControl['VALUE'] = $arUserField['SETTINGS']['DEFAULT_VALUE'];
			}
			else if ($arHtmlControl['VALUE'] === '' && array_key_exists('VALUE', $arUserField) && $arUserField['VALUE'] === null)
			{
				$arHtmlControl['VALUE'] =
					isset($arUserField['SETTINGS']['DEFAULT_VALUE'])
						? $arUserField['SETTINGS']['DEFAULT_VALUE']
						: null;
			}

			// view

			if (!isset($attributes['size']) || $attributes['size'] <= 1)
			{
				$arHtmlControl['VALIGN'] = 'middle';
			}

			$enum = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'getList'], $arUserField);

			$result = View\Select::getControl($enum, $arHtmlControl['VALUE'], $attributes, $settings);
		}

		return $result;
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		$attributes = Helper\Attributes::extractFromSettings($userField['SETTINGS']);

		if (isset($userField['SETTINGS']['DISPLAY']) && $userField['SETTINGS']['DISPLAY'] === 'CHECKBOX')
		{
			$userField['MANDATORY'] = 'Y'; // hide no value for all variants

			if (class_exists(Main\UserField\Types\EnumType::class))
			{
				$htmlControl['NAME'] = preg_replace('/\[\]$/', '', $htmlControl['NAME']);
			}

			$result = static::callParent('GetEditFormHTMLMulty', [$userField, $htmlControl]);
			$result = Helper\Attributes::insert($result, $attributes);
		}
		else
		{
			$attributes += static::makeSelectViewAttributes($userField);
			$settings = static::makeSelectViewSettings($userField);
			$enum = call_user_func([ $userField['USER_TYPE']['CLASS_NAME'], 'getList'], $userField);
			$enum = Helper\Enum::toArray($enum);
			$values = Helper\Value::asMultiple($userField, $htmlControl);
			$valueIndex = 0;

			if (empty($values)) { $values[] = ''; }

			$result = sprintf('<table id="%s">', static::makeFieldHtmlId($userField, 'table'));

			foreach ($values as $value)
			{
				$attributes['name'] = $userField['FIELD_NAME'] . '[' . $valueIndex . ']';

				$result .= '<tr><td>';
				$result .= View\Select::getControl($enum, $value, $attributes, $settings);
				$result .= '</td></tr>';

				++$valueIndex;
			}

			$result .= '<tr><td style="padding-top: 6px;">';
			$result .= static::getMultipleAddButton($userField);
			$result .= '</td></tr>';
			$result .= '</table>';
			$result .= static::getMultipleAutoSaveScript($userField);
		}

		return $result;
	}

	protected static function makeSelectViewAttributes($userField)
	{
		$attributes = [
			'disabled' => $userField['EDIT_IN_LIST'] !== 'Y',
			'data-multiple' => $userField['MULTIPLE'] !== 'N',
		];

		if ($userField['SETTINGS']['LIST_HEIGHT'] > 1)
		{
			$attributes['size'] = $userField['SETTINGS']['LIST_HEIGHT'];
		}

		return $attributes;
	}

	protected static function makeSelectViewSettings($userField)
	{
		$settings = (array)$userField['SETTINGS'];

		if (!isset($settings['ALLOW_NO_VALUE']) && $userField['MANDATORY'] !== 'Y')
		{
			$settings['ALLOW_NO_VALUE'] = 'Y';
		}

		return $settings;
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = '&nbsp;';
		$isFoundResult = false;

		if (!empty($arHtmlControl['VALUE']))
		{
			$query = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'getlist' ], $arUserField);

			if ($query)
			{
				while ($option = $query->Fetch())
				{
					if ($option['ID'] == $arHtmlControl['VALUE'])
					{
						$isFoundResult = true;
						$result = Market\Utils::htmlEscape($option['VALUE']);
						break;
					}
				}
			}

			if (!$isFoundResult)
			{
				$result = '[' . Market\Utils::htmlEscape($arHtmlControl['VALUE']) . ']';
			}
		}

		return $result;
	}

	public static function GetAdminListViewHTMLMulty($arUserField, $arHtmlControl)
	{
		$result = '';

		if (!empty($arHtmlControl['VALUE']))
		{
			$query = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'getlist' ], $arUserField);
			$valueList = (array)$arHtmlControl['VALUE'];
			$valueMap = array_flip($valueList);

			if ($query)
			{
				while ($option = $query->Fetch())
				{
					if (isset($valueMap[$option['ID']]))
					{
						$result .= ($result !== '' ? ' / ' : '') . Market\Utils::htmlEscape($option['VALUE']);
					}
				}
			}
		}

		if ($result === '')
		{
			$result = '&nbsp;';
		}

		return $result;
	}
}