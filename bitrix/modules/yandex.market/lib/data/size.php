<?php

namespace Yandex\Market\Data;

use Bitrix\Main;
use Yandex\Market;

class Size
{
	use Market\Reference\Concerns\HasLang;

	const UNIT_MILLIMETER = 'mm';
	const UNIT_CENTIMETER = 'cm';
	const UNIT_DECIMETER = 'dm';
	const UNIT_METER = 'm';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getUnitList()
	{
		return [
			static::UNIT_MILLIMETER,
			static::UNIT_CENTIMETER,
			static::UNIT_DECIMETER,
			static::UNIT_METER,
		];
	}

	public static function getUnitTitle($unit, $version = '')
	{
		$suffix = '';

		if ((string)$version !== '')
		{
			$suffix = '_' . TextString::toUpper($version);
		}

		return static::getLang('DATA_SIZE_UNIT_' . TextString::toUpper($unit) . $suffix, null, $unit);
	}

	public static function splitGlue($value)
	{
		$result = null;

		if (preg_match('/^(\d+(?:\.\d+)?)(?:[^0-9.]+(\d+(?:\.\d+)?))?(?:[^0-9.]+(\d+(?:\.\d+)?))?$/', $value, $matches))
		{
			$result = [
				'LENGTH' => (float)$matches[1],
				'WIDTH' => isset($matches[2]) ? (float)$matches[2] : null,
				'HEIGHT' => isset($matches[3]) ? (float)$matches[3] : null,
			];
		}

		return $result;
	}

	public static function convertUnit($size, $fromUnit, $toUnit)
	{
		$fromRatio = static::getRatio($fromUnit);
		$toRatio = static::getRatio($toUnit);

		return $size * ($fromRatio / $toRatio);
	}

	public static function getBitrixUnit()
	{
		return static::UNIT_MILLIMETER;
	}

	public static function getServiceUnit()
	{
		return static::UNIT_CENTIMETER;
	}

	protected static function getRatio($unit)
	{
		switch ($unit)
		{
			case static::UNIT_METER:
				$result = 1;
			break;

			case static::UNIT_DECIMETER:
				$result = 0.1;
			break;

			case static::UNIT_CENTIMETER:
				$result = 0.01;
			break;

			case static::UNIT_MILLIMETER:
				$result = 0.001;
			break;

			default:
				throw new Main\ArgumentException('unknown unit');
			break;
		}

		return $result;
	}
}