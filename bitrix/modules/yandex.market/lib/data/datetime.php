<?php

namespace Yandex\Market\Data;

use Bitrix\Main;

class DateTime extends Date
{
	// same date with UTC timezone shift
	public static function toCanonical(Main\Type\DateTime $dateTime)
	{
		if ($dateTime instanceof Type\CanonicalDateTime) { return $dateTime; }

		return Type\CanonicalDateTime::createFromTimestamp($dateTime->getTimestamp());
	}

	// same time with UTC timezone without shift
	public static function asCanonical(Main\Type\DateTime $dateTime)
	{
		if ($dateTime instanceof Type\CanonicalDateTime) { return $dateTime; }

		$format = 'Y-m-d H:i:s';
		$dateString = $dateTime->format($format);

		return new Type\CanonicalDateTime($dateString, $format);
	}

	public static function format(Main\Type\Date $date)
	{
		$timestamp = $date->getTimestamp();

		return ConvertTimeStamp($timestamp, 'FULL');
	}

	protected static function makeParseFormats($siteId)
	{
		return array_unique(array_filter([
			Site::getCultureValue($siteId, 'FORMAT_DATETIME'),
			FORMAT_DATETIME,
		]));
	}

	public static function sanitize($date, $format = FORMAT_DATETIME)
	{
		$result = null;

		if ($date instanceof Main\Type\DateTime)
		{
			$result = $date;
		}
		else if ($date instanceof Main\Type\Date)
		{
			$timestamp = $date->getTimestamp();
			$result = Main\Type\DateTime::createFromTimestamp($timestamp);
		}
		else if ($date instanceof \DateTime)
		{
			$result = Main\Type\DateTime::createFromPhp($date);
		}
		else if (is_numeric($date) && (int)$date > 0) // timestamp
		{
			$result = Main\Type\DateTime::createFromTimestamp($date);
		}
		else if (is_scalar($date) && (string)$date !== '')
		{
			$timestamp = MakeTimeStamp($date, $format);

			if ($timestamp !== false)
			{
				$result = Main\Type\DateTime::createFromTimestamp($timestamp);
			}
		}

		return $result;
	}

	public static function compare(Main\Type\Date $first, Main\Type\Date $second)
	{
		$firstValue = $first->getTimestamp();
		$secondValue = $second->getTimestamp();

		if ($firstValue === $secondValue)
		{
			return 0;
		}

		return $firstValue < $secondValue ? -1 : 1;
	}

	public static function convertFromService($dateString, $format = DateTime::FORMAT_DEFAULT_FULL)
	{
		return new Main\Type\DateTime($dateString, $format);
	}

	public static function makeCanonicalDummy()
	{
		return Type\CanonicalDateTime::createFromTimestamp(0);
	}

	public static function makeDummy()
	{
		return Main\Type\DateTime::createFromTimestamp(0);
	}
}