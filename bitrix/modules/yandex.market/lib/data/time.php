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

	public static function compare($a, $b)
	{
		$aTime = static::parse($a);
		$bTime = static::parse($b);

		if ($aTime === $bTime)
		{
			$result = 0;
		}
		else if ($aTime === null)
		{
			$result = 1;
		}
		else if ($bTime === null)
		{
			$result = -1;
		}
		else if ($aTime[0] !== $bTime[0])
		{
			$result = ($aTime[0] < $bTime[0] ? -1 : 1);
		}
		else
		{
			$result = ($aTime[1] < $bTime[1] ? -1 : 1);
		}

		return $result;
	}

	public static function min($a, $b)
	{
		return static::compare($a, $b) === 1 ? $b : $a;
	}

	public static function diff($a, $b)
	{
		$aTime = static::parse($a);
		$bTime = static::parse($b);

		if ($aTime === null || $bTime === null) { return null; }

		$diffHours = $aTime[0] - $bTime[0];
		$diffMinutes = $aTime[1] - $bTime[1];
		$sign = 1;

		if ($diffMinutes < 0)
		{
			$diffHours -= (int)ceil(abs($diffMinutes) / 60);
			$diffMinutes = (60 + $diffMinutes % 60);
		}

		if ($diffHours < 0)
		{
			$sign = -1;
			$diffHours *= -1;

			if ($diffMinutes > 0)
			{
				$diffHours -= (int)ceil($diffMinutes / 60);
				$diffMinutes = (60 - $diffMinutes % 60);
			}
		}

		return [ $sign, static::makeFormatString($diffHours, $diffMinutes) ];
	}

	public static function parse($value)
	{
		if (is_array($value))
		{
			$hour = isset($value[0]) && is_numeric($value[0]) ? (int)$value[0] : null;
			$time = isset($value[1]) && is_numeric($value[1]) ? (int)$value[1] : null;
		}
		else if (is_numeric($value))
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

	public static function makeFormatString($hours, $minutes)
	{
		return
			TextString::padLeft($hours, 2, '0')
			. ':'
			. TextString::padLeft($minutes, 2, '0');
	}
}