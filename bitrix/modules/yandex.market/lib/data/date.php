<?php

namespace Yandex\Market\Data;

use Bitrix\Main;

class Date
{
	const FORMAT_DEFAULT_FULL = 'd-m-Y H:i:s';
	const FORMAT_DEFAULT_SHORT = 'd-m-Y';

	public static function format(Main\Type\Date $date)
	{
		$timestamp = $date->getTimestamp();

		return ConvertTimeStamp($timestamp, 'SHORT');
	}

	public static function sanitize($date)
	{
		$result = null;

		if ($date instanceof Main\Type\Date)
		{
			$result = $date;
		}
		else if ($date instanceof \DateTime)
		{
			$result = Main\Type\Date::createFromPhp($date);
		}
		else if (is_numeric($date) && (int)$date > 0) // timestamp
		{
			$result = Main\Type\Date::createFromTimestamp($date);
		}
		else if (is_scalar($date) && (string)$date !== '')
		{
			$timestamp = MakeTimeStamp($date);

			if ($timestamp !== false)
			{
				$result = Main\Type\Date::createFromTimestamp($timestamp);
			}
		}

		return $result;
	}

	public static function compare(Main\Type\Date $first, Main\Type\Date $second)
	{
		$firstValue = $first->format('Y-m-d');
		$secondValue = $second->format('Y-m-d');

		if ($firstValue === $secondValue)
		{
			return 0;
		}

		return $firstValue < $secondValue ? -1 : 1;
	}

	public static function convertFromService($dateString, $format = Date::FORMAT_DEFAULT_SHORT)
	{
		return new Main\Type\Date($dateString, $format);
	}

	public static function convertForService($timestamp, $format = \DateTime::ATOM)
	{
		if ($timestamp instanceof Main\Type\Date || $timestamp instanceof \DateTime)
		{
			$dateTime = $timestamp;
		}
		else
		{
			$dateTime = Main\Type\DateTime::createFromTimestamp($timestamp);
		}

		if (static::supportsTimezone($dateTime) && !static::hasFormatTimezone($format))
		{
			$timezone = static::getTimezone();
			$dateTime->setTimezone($timezone);
		}

		return $dateTime->format($format);
	}

	protected static function supportsTimezone($dateTime)
	{
		return ($dateTime instanceof Main\Type\DateTime || $dateTime instanceof \DateTime);
	}

	protected static function hasFormatTimezone($format)
	{
		$variants = [ 'P', 'O', 'T', 'Z', 'e' ];
		$result = false;

		foreach ($variants as $variant)
		{
			if (TextString::getPosition($format, $variant) !== false)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public static function isValid($date)
	{
		return $date instanceof Main\Type\Date && !static::isDummy($date);
	}

	public static function isDummy($date)
	{
		return $date instanceof Main\Type\Date && $date->getTimestamp() === 0;
	}

	public static function makeDummy()
	{
		return Main\Type\Date::createFromTimestamp(0);
	}

	public static function getTimezone()
	{
		return new \DateTimeZone('Europe/Moscow');
	}
}