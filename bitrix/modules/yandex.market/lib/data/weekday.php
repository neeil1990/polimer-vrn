<?php

namespace Yandex\Market\Data;

class WeekDay
{
	public static function isValid($value)
	{
		if (!is_numeric($value)) { return false; }

		$value = (int)$value;

		return ($value >= 1 && $value <= 7);
	}

	public static function sanitize($value)
	{
		return static::isValid($value) ? (int)$value : null;
	}
}