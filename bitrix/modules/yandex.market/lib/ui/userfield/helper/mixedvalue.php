<?php

namespace Yandex\Market\Ui\UserField\Helper;

class MixedValue extends Value
{
	protected static function isSingle($value)
	{
		return !is_array($value) || static::isAssociativeArray($value);
	}

	protected static function isEmpty($value)
	{
		return !is_array($value) || empty($value);
	}

	protected static function isMultiple($value)
	{
		return is_array($value) && !static::isAssociativeArray($value);
	}

	protected static function isAssociativeArray($value)
	{
		$result = false;

		foreach ($value as $key => $item)
		{
			if (!is_numeric($key))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}