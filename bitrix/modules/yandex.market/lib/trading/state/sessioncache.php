<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;

class SessionCache
{
	const SESSION_KEY = 'YAMARKET_TRADING_CACHE';

	public static function releaseByType($type)
	{
		if (isset($_SESSION[static::SESSION_KEY][$type]))
		{
			unset($_SESSION[static::SESSION_KEY][$type]);
		}
	}

	public static function set($type, $id, $data)
	{
		if (!isset($_SESSION[static::SESSION_KEY]))
		{
			$_SESSION[static::SESSION_KEY] = [];
		}

		if (!isset($_SESSION[static::SESSION_KEY][$type]))
		{
			$_SESSION[static::SESSION_KEY][$type] = [];
		}

		$_SESSION[static::SESSION_KEY][$type][$id] = $data;
	}

	public static function has($type, $id)
	{
		return isset($_SESSION[static::SESSION_KEY][$type][$id]);
	}

	public static function get($type, $id)
	{
		if (!isset($_SESSION[static::SESSION_KEY][$type][$id]))
		{
			throw new Main\ArgumentException('no cache info for ' . $type . ' ' . $id);
		}

		return $_SESSION[static::SESSION_KEY][$type][$id];
	}

	public static function release($type, $id)
	{
		if (isset($_SESSION[static::SESSION_KEY][$type][$id]))
		{
			unset($_SESSION[static::SESSION_KEY][$type][$id]);
		}
	}
}