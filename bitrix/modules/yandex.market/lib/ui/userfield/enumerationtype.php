<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class EnumerationType
{
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends()
	{
		return Main\UserField\Types\EnumType::class;
	}

	/** @noinspection PhpDeprecationInspection */
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
		if (is_callable($arUserField['VALUES']))
		{
			$function = $arUserField['VALUES'];
			$values = $function();
		}
		else
		{
			$values = $arUserField['VALUES'];
		}

		if (!is_array($values)) { $values = []; }

		$result = new \CDBResult();
		$result->InitFromArray($values);

		return $result;
	}

	/** @noinspection PhpUnused */
	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterHTML', [$arUserField, $arHtmlControl]);
	}

	/** @noinspection PhpUnused */
	public static function GetFilterData($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterData', [$arUserField, $arHtmlControl]);
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$enum = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'getList'], $arUserField);
		$settings = static::makeSelectViewSettings($arUserField);
		$attributes = Helper\Attributes::extractFromSettings($arUserField['SETTINGS']);
		$attributes += static::makeSelectViewAttributes($arUserField);
		$attributes['name'] = $arHtmlControl['NAME'];
		$selected = Helper\Value::asSingle($arUserField, $arHtmlControl);

		if (isset($arUserField['SETTINGS']['DISPLAY']) && $arUserField['SETTINGS']['DISPLAY'] === 'CHECKBOX')
		{
			$result = View\Radio::getControl($enum, $selected, $attributes, $settings);
		}
		else
		{
			if (!isset($attributes['size']) || $attributes['size'] <= 1)
			{
				$arHtmlControl['VALIGN'] = 'middle';
			}

			$result = View\Select::getControl($enum, $selected, $attributes, $settings);
		}

		return $result;
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		$attributes = Helper\Attributes::extractFromSettings($userField['SETTINGS']);
		$attributes += static::makeSelectViewAttributes($userField);
		$settings = static::makeSelectViewSettings($userField);
		$enum = call_user_func([ $userField['USER_TYPE']['CLASS_NAME'], 'getList'], $userField);
		$selected = Helper\Value::asMultiple($userField, $htmlControl);

		if (isset($userField['SETTINGS']['DISPLAY']) && $userField['SETTINGS']['DISPLAY'] === 'CHECKBOX')
		{
			$attributes['name'] = preg_replace('/\[]$/', '', $userField['FIELD_NAME']);
			$attributes['name'] .= '[]';

			$result = View\Checkbox::getControl($enum, $selected, $attributes, $settings);
		}
		else
		{
			$enum = Helper\Enum::toArray($enum);

			$result = View\Collection::render(
				$userField['FIELD_NAME'],
				$selected,
				static function($name, $value) use ($enum, $attributes, $settings) {
					$attributes['name'] = $name;

					return View\Select::getControl($enum, $value, $attributes, $settings);
				},
				Fieldset\Helper::makeChildAttributes($userField)
			);
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

		if (isset($userField['SETTINGS']['ONCHANGE']))
		{
			$attributes['onchange'] = $userField['SETTINGS']['ONCHANGE'];
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

		if (!empty($arHtmlControl['VALUE']))
		{
			$value = $arHtmlControl['VALUE'];
			$query = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'getList' ], $arUserField);
			$enum = $query ? Helper\Enum::toArray($query) : [];
			$enumMap = array_column($enum, 'VALUE', 'ID');

			if (isset($enumMap[$value]))
			{
				$result = $enumMap[$value];
			}
			else if (
				isset($arUserField['SETTINGS']['DESCRIPTION_FIELD'])
				&& !empty($arUserField['ROW'][$arUserField['SETTINGS']['DESCRIPTION_FIELD']])
			)
			{
				$result = $arUserField['ROW'][$arUserField['SETTINGS']['DESCRIPTION_FIELD']];
			}
			else
			{
				$result = sprintf('[%s]', $value);
			}

			$result = Market\Utils::htmlEscape($result);
		}

		return $result;
	}

	public static function GetAdminListViewHTMLMulty($arUserField, $arHtmlControl)
	{
		$result = '&nbsp;';

		if (!empty($arHtmlControl['VALUE']))
		{
			$query = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'getList' ], $arUserField);
			$enum = $query ? Helper\Enum::toArray($query) : [];
			$enumMap = array_column($enum, 'VALUE', 'ID');
			$displayValues = [];

			foreach ((array)$arHtmlControl['VALUE'] as $value)
			{
				if (isset($enumMap[$value]))
				{
					$displayValues[] = $enumMap[$value];
				}
				else
				{
					$displayValues[] = sprintf('[%s]', $value);
				}
			}

			$result = implode(' / ', $displayValues);
			$result = Market\Utils::htmlEscape($result);
		}

		return $result;
	}

	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function ymExportValue(array $userField, $value, array $row = null)
	{
		if ((string)$value === '')
		{
			if (!isset($userField['SETTINGS']['CAPTION_NO_VALUE'])) { return null; }

			return $userField['SETTINGS']['CAPTION_NO_VALUE'];
		}

		$query = call_user_func([ $userField['USER_TYPE']['CLASS_NAME'], 'getList' ], $userField);
		$enum = $query ? Helper\Enum::toArray($query) : [];
		$enumMap = array_column($enum, 'VALUE', 'ID');

		if (!isset($enumMap[$value])) { return $value; }

		$idMarker = sprintf('[%s]', $value);
		$display = $enumMap[$value];

		if (Market\Data\TextString::getPosition($display, $idMarker) === 0)
		{
			return $display;
		}

		return $idMarker . ' ' . $display;
	}
}