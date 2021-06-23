<?php

namespace Yandex\Market\Data;

use Bitrix\Main;
use Yandex\Market;

class Weight
{
	use Market\Reference\Concerns\HasLang;

	const UNIT_GRAM = 'g';
	const UNIT_KILOGRAM = 'kg';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getUnitList()
	{
		return [
			static::UNIT_GRAM,
			static::UNIT_KILOGRAM,
		];
	}

	public static function getBitrixUnit()
	{
		return static::UNIT_GRAM;
	}

	public static function getServiceUnit()
	{
		return static::UNIT_KILOGRAM;
	}

	public static function getUnitTitle($unit, $version = '')
	{
		$suffix = '';

		if ((string)$version !== '')
		{
			$suffix = '_' . TextString::toUpper($version);
		}

		return static::getLang('DATA_WEIGHT_UNIT_' . TextString::toUpper($unit) . $suffix, null, $unit);
	}

	public static function format($value, $precision = 4)
	{
		return Number::format($value, $precision);
	}

	public static function convertUnit($weight, $fromUnit, $toUnit)
	{
		$fromRatio = static::getRatio($fromUnit);
		$toRatio = static::getRatio($toUnit);

		return $weight * ($fromRatio / $toRatio);
	}

	protected static function getRatio($unit)
	{
		switch ($unit)
		{
			case static::UNIT_GRAM:
				$result = 1;
			break;

			case static::UNIT_KILOGRAM:
				$result = 1000;
			break;

			default:
				throw new Main\ArgumentException('unknown unit');
			break;
		}

		return $result;
	}
}