<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;

class HitCache
{
	protected static $cache = [];

	public static function releaseByType($type)
	{
		if (isset(static::$cache[$type]))
		{
			unset(static::$cache[$type]);
		}
	}

	public static function set($type, $id, $data)
	{
		if (!isset(static::$cache))
		{
			static::$cache = [];
		}

		if (!isset(static::$cache[$type]))
		{
			static::$cache[$type] = [];
		}

		static::$cache[$type][$id] = $data;
	}

	public static function has($type, $id)
	{
		return isset(static::$cache[$type][$id]);
	}

	public static function get($type, $id)
	{
		if (!isset(static::$cache[$type][$id]))
		{
			throw new Main\ArgumentException('no cache info for ' . $type . ' ' . $id);
		}

		return static::$cache[$type][$id];
	}

	public static function release($type, $id)
	{
		if (isset(static::$cache[$type][$id]))
		{
			unset(static::$cache[$type][$id]);
		}
	}
}