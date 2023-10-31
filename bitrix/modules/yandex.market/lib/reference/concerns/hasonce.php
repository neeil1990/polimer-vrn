<?php

namespace Yandex\Market\Reference\Concerns;

use Yandex\Market;

trait HasOnce
{
	private $onceMemoized = [];

	protected function once($name, $arguments = null, $callable = null)
	{
		if ($callable === null && is_callable($arguments))
		{
			$callable = $arguments;
			$arguments = null;
		}

		$cacheKey = $name . ':' . Market\Utils\Caller::getArgumentsHash($arguments);

		if (!isset($this->onceMemoized[$cacheKey]) && !array_key_exists($cacheKey, $this->onceMemoized))
		{
			$this->onceMemoized[$cacheKey] = $this->callOnce($name, $arguments, $callable);
		}

		return $this->onceMemoized[$cacheKey];
	}

	private function callOnce($name, $arguments = null, $callable = null)
	{
		if ($arguments === null)
		{
			$result = $callable !== null ? $callable() : $this->{$name}();
		}
		else if (is_array($arguments))
		{
			$result = $callable !== null ? $callable(...$arguments) : $this->{$name}(...$arguments);
		}
		else
		{
			$result = $callable !== null ? $callable($arguments) : $this->{$name}($arguments);
		}

		return $result;
	}
}