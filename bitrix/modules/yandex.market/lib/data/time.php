<?php

namespace Yandex\Market\Data;

class Time
{
	public static function isValid($value)
	{
		$parsed = static::parse($value);

		return (
			$parsed !== null
			&& ($parsed[0] >= 0 && $parsed[0] <= 23)
			&& ($parsed[1] >= 0 && $parsed[1] <= 59)
		);
	}

	public static function sanitize($value)
	{
		return static::format($value);
	}

	public static function format($value)
	{
		$parsed = static::parse($value);

		if ($parsed === null) { return null; }

		return static::makeFormatString($parsed[0], $parsed[1]);
	}

	public static function makeIntervalString($value)
	{
		$parsed = static::parse($value);

		if ($parsed === null) { return null; }

		return 'PT' . $parsed[0] . 'H' . $parsed[1] . 'M';
	}

	public static function toNumber($value)
	{
		$parts = static::parse($value);

		if ($parts === null) { return null; }

		return $parts[0] + ($parts[1] / 60);
	}

	public static function fromNumber($value)
	{
		$hours = (int)$value;
		$minutes = (int)(($value - $hours) * 60);

		return static::makeFormatString($hours, $minutes);
	}

	public static function parse($value)
	{
		if (is_numeric($value))
		{
			$hour = (int)$value;
			$time = 0;
		}
		else if (preg_match('/(?<hour>\d{1,2}):(?<time>\d{1,2})/', $value, $matches))
		{
			$hour = (int)$matches['hour'];
			$time = (int)$matches['time'];
		}
		else
		{
			$hour = null;
			$time = null;
		}

		return $hour !== null ? [ $hour, $time ] : null;
	}

	protected static function makeFormatString($hours, $minutes)
	{
		return
			TextString::padLeft($hours, 2, '0')
			. ':'
			. TextString::padLeft($minutes, 2, '0');
	}
}