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

	public static function parse($date, $siteId)
	{
		$result = null;
		$formats = static::makeParseFormats($siteId);

		foreach ($formats as $format)
		{
			$result = static::sanitize($date, $format);

			if ($result !== null) { break; }
		}

		return $result;
	}

	protected static function makeParseFormats($siteId)
	{
		return array_unique(array_filter([
			Site::getCultureValue($siteId, 'FORMAT_DATE'),
			FORMAT_DATE,
		]));
	}

	public static function sanitize($date, $format = FORMAT_DATE)
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
			$timestamp = MakeTimeStamp($date, $format);

			if ($timestamp !== false)
			{
				$result = Main\Type\Date::createFromTimestamp($timestamp);
			}
		}

		return $result;
	}

	public static function diff(Main\Type\Date $from, Main\Type\Date $to)
	{
		$fromDate = (new \DateTime())->setTimestamp($from->getTimestamp())->setTime(0, 0);
		$toDate = (new \DateTime())->setTimestamp($to->getTimestamp())->setTime(0, 0);
		$interval = $toDate->diff($fromDate);

		return (int)$interval->format('%a');
	}

	public static function unique(array $dates)
	{
		$used = [];
		$result = [];

		foreach ($dates as $date)
		{
			$dateFormatted = static::format($date);

			if (isset($used[$dateFormatted])) { continue; }

			$used[$dateFormatted] = true;
			$result[] = $date;
		}

		return $result;
	}

	public static function max(Main\Type\Date ...$dates)
	{
		$result = array_shift($dates);

		foreach ($dates as $date)
		{
			if (static::compare($result, $date) === -1)
			{
				$result = $date;
			}
		}

		return $result;
	}

	public static function min(Main\Type\Date ...$dates)
	{
		$result = array_shift($dates);

		foreach ($dates as $date)
		{
			if (static::compare($result, $date) === 1)
			{
				$result = $date;
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

	public static function convertForService($timestamp, $format = \DateTime::ATOM, $useServiceTimezone = false)
	{
		if ($timestamp instanceof Main\Type\Date || $timestamp instanceof \DateTime)
		{
			$dateTime = clone $timestamp;
		}
		else
		{
			$dateTime = Main\Type\DateTime::createFromTimestamp($timestamp);
		}

		if ($useServiceTimezone && static::supportsTimezone($dateTime) && !static::hasFormatTimezone($format))
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