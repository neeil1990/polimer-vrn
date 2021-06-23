<?php

namespace Yandex\Market\Data;

use Bitrix\Main;

class DateTime extends Date
{
	public static function format(Main\Type\Date $date)
	{
		$timestamp = $date->getTimestamp();

		return ConvertTimeStamp($timestamp, 'FULL');
	}

	public static function sanitize($date)
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
			$timestamp = MakeTimeStamp($date);

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

	public static function makeDummy()
	{
		return Main\Type\DateTime::createFromTimestamp(0);
	}
}