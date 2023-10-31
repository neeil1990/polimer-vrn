<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class NumberType
{
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends()
	{
		return Main\UserField\Types\IntegerType::class;
	}

	public static function getCompatibleExtends()
	{
		return \CUserTypeInteger::class;
	}

	public static function getUserTypeDescription()
	{
		return array_diff_key(static::callParent('getUserTypeDescription'), [
			'USE_FIELD_COMPONENT' => true,
		]);
	}

	public static function checkFields($arUserField, $value)
	{
		return static::callParent('checkFields', [$arUserField, $value]);
	}

	public static function GetAdminListViewHtml($userField, $htmlControl)
	{
		$value = Helper\Value::asSingle($userField, $htmlControl);
		$unit = static::getUnit($userField);

		if ($value === '' || !is_numeric($value)) { return ''; }

		$result = $value;

		if ($unit)
		{
			$unitFormatted = is_array($unit) ? Market\Utils::sklon($value, $unit) : $unit;

			$result .= '&nbsp;' . $unitFormatted;
		}

		return $result;
	}

	public static function GetEditFormHtml($userField, $htmlControl)
	{
		$value = Helper\Value::asSingle($userField, $htmlControl);
		$unit = static::getUnit($userField);

		$result = View\Number::getControl($value, [
			'name' => $htmlControl['NAME'],
		] + array_filter([
			'size' => isset($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : null,
		]));

		if ($result !== '' && $unit)
		{
			$result .=
				'&nbsp;'
				. (is_array($unit) ? end($unit) : $unit);
		}

		$htmlControl['VALIGN'] = 'middle';

		return $result;
	}

	protected static function getUnit($userField)
	{
		return isset($userField['SETTINGS']['UNIT']) ? $userField['SETTINGS']['UNIT'] : null;
	}
}