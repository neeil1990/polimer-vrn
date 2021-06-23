<?php

namespace Yandex\Market\Data;

class Price
{
	public static function format($value, $precision = 2)
	{
		return number_format($value, $precision, ',', ' ');
	}

	public static function round($value)
	{
		return round($value, 2);
	}
}