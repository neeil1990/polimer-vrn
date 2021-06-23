<?php

namespace Yandex\Market\Trading\Service;

use Bitrix\Main;

class Migration
{
	public static function getMap()
	{
		return [
			Manager::SERVICE_BERU => Manager::SERVICE_MARKETPLACE,
		];
	}

	public static function isDeprecated($code)
	{
		$map = static::getMap();

		return isset($map[$code]);
	}

	public static function getDeprecateUse($code)
	{
		$map = static::getMap();

		if (!isset($map[$code]))
		{
			throw new Main\ArgumentException(sprintf(
				'hasn\'t migration replace for %s',
				$code
			));
		}

		return $map[$code];
	}

	public static function hasMigrated($code)
	{
		$map = static::getMap();

		return in_array($code, $map, true);
	}

	public static function getMigrated($code)
	{
		$map = static::getMap();
		$result = [];

		foreach ($map as $deprecated => $use)
		{
			if ($use === $code)
			{
				$result[] = $deprecated;
			}
		}

		if (empty($result))
		{
			throw new Main\ArgumentException(sprintf(
				'hasn\'t migrated services for %s',
				$code
			));
		}

		return $result;
	}
}