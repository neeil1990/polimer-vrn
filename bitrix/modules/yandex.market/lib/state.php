<?php

namespace Yandex\Market;

use Yandex\Market;

class State
{
	protected static $cache = [];

	public static function get($name, $default = null)
	{
		$stored = static::load($name);

		return $stored !== null ? $stored : $default;
	}

	public static function set($name, $value)
	{
		$value = (string)$value;
		$stored = static::load($name);

		if ((string)$stored === $value) { return; }

		if ($value === '')
		{
			$queryResult = Reference\StateTable::delete($name);
			$value = null;
		}
		else if ($stored === null)
		{
			$queryResult = Reference\StateTable::add([
				'NAME' => $name,
				'VALUE' => $value,
			]);
		}
		else
		{
			$queryResult = Reference\StateTable::update($name, [
				'VALUE' => $value,
			]);
		}

		Market\Result\Facade::handleException($queryResult);
		static::$cache[$name] = $value;
	}

	public static function remove($name)
	{
		static::set($name, null);
	}

	protected static function load($name)
	{
		if (isset(static::$cache[$name]))
		{
			$result = static::$cache[$name];
		}
		else if (!array_key_exists($name, static::$cache))
		{
			$result = static::query($name);
			static::$cache[$name] = $result;
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected static function query($name)
	{
		$result = null;

		$query = Reference\StateTable::getList([
			'filter' => [ '=NAME' => $name ],
			'select' => [ 'VALUE' ],
		]);

		if ($row = $query->fetch())
		{
			$result = (string)$row['VALUE'];
		}

		return $result;
	}
}