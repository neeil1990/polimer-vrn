<?php

namespace Yandex\Market\Utils;

class Caller
{
	public static function getArgumentsHash($arguments)
	{
		if ($arguments === null)
		{
			$result = '';
		}
		else if (!is_array($arguments))
		{
			$result = static::stringifyArgument($arguments);
		}
		else
		{
			$parts = [];

			foreach ($arguments as $argument)
			{
				$parts[] = static::stringifyArgument($argument);
			}

			$result = implode(':', $parts);
		}

		if (strlen($result) > 32)
		{
			$result = md5($result);
		}

		return $result;
	}

	protected static function stringifyArgument($argument)
	{
		if (is_object($argument))
		{
			$result = spl_object_hash($argument);
		}
		else if (is_scalar($argument))
		{
			$result = $argument;
		}
		else
		{
			$result = serialize($argument);
		}

		return $result;
	}
}