<?php

namespace Yandex\Market\Reference\Concerns;

use Yandex\Market;

trait HasOnceStatic
{
	private static $onceMemoizedStatic = [];

	protected static function onceStatic($name, $arguments = null, $callable = null)
	{
		if ($callable === null && is_callable($arguments))
		{
			$callable = $arguments;
			$arguments = null;
		}

		$cacheKey = $name . ':' . Market\Utils\Caller::getArgumentsHash($arguments);

		if (!isset(self::$onceMemoizedStatic[$cacheKey]) && !array_key_exists($cacheKey, self::$onceMemoizedStatic))
		{
			self::$onceMemoizedStatic[$cacheKey] = static::callOnceStatic($name, $arguments, $callable);
		}

		return self::$onceMemoizedStatic[$cacheKey];
	}

	private static function callOnceStatic($name, $arguments = null, $callable = null)
	{
		if ($arguments === null)
		{
			$result = $callable !== null ? $callable() : static::{$name}();
		}
		else if (is_array($arguments))
		{
			$result = $callable !== null ? $callable(...$arguments) : static::{$name}(...$arguments);
		}
		else
		{
			$result = $callable !== null ? $callable($arguments) : static::{$name}($arguments);
		}

		return $result;
	}
}