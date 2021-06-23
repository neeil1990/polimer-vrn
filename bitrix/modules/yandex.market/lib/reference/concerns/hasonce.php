<?php

namespace Yandex\Market\Reference\Concerns;

use Yandex\Market;

trait HasOnce
{
	private $onceMemoized = [];

	protected function once($name, $arguments = null)
	{
		$cacheKey = $name . ':' . Market\Utils\Caller::getArgumentsHash($arguments);

		if (!isset($this->onceMemoized[$cacheKey]) && !array_key_exists($cacheKey, $this->onceMemoized))
		{
			$this->onceMemoized[$cacheKey] = $this->callOnce($name, $arguments);
		}

		return $this->onceMemoized[$cacheKey];
	}

	private function callOnce($name, $arguments)
	{
		if ($arguments === null)
		{
			$result = $this->{$name}();
		}
		else if (is_array($arguments))
		{
			$result = $this->{$name}(...$arguments);
		}
		else
		{
			$result = $this->{$name}($arguments);
		}

		return $result;
	}
}