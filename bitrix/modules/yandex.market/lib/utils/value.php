<?php

namespace Yandex\Market\Utils;

use Yandex\Market;

class Value
{
	public static function isEmpty($value)
	{
		if (is_scalar($value))
		{
			$result = (string)$value === '';
		}
		else
		{
			$result = empty($value);
		}

		return $result;
	}

	public static function toBoolean($value)
	{
		$booleanType = Market\Type\Manager::getType(Market\Type\Manager::TYPE_BOOLEAN);
		$booleanString = $booleanType->format($value);

		return ($booleanString === 'true');
	}
}