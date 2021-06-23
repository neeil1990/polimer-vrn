<?php

namespace Yandex\Market\Data;

class Number
{
	public static function format($value, $precision = 2)
	{
		$result = number_format($value, $precision, ',', ' ');
		$result = rtrim($result, '0');
		$result = rtrim($result, ',');

		return $result;
	}

	public static function normalize($value)
	{
		$value = trim($value);
		$result = null;

		if ($value !== '')
		{
			$result = (float)$value;
		}

		return $result;
	}
}