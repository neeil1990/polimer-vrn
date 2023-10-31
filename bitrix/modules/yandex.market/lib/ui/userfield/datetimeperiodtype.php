<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class DateTimePeriodType extends DateTimeType
{
	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		$value = Helper\ComplexValue::asSingle($userField, $htmlControl);

		return static::printValue($value);
	}

	protected static function printValue($value)
	{
		$dates = array_filter([
			isset($value['FROM']) ? Market\Data\DateTime::sanitize($value['FROM']) : null,
			isset($value['TO']) ? Market\Data\DateTime::sanitize($value['TO']) : null,
		]);

		if (empty($dates)) { return ''; }

		$datesFormatted = array_map(static function(Main\Type\DateTime $date) { return Market\Data\Date::format($date); }, $dates);
		$datesUnique = array_unique($datesFormatted);
		$timesFormatted = array_map(static function(Main\Type\DateTime $date) { return $date->format('H:i'); }, $dates);
		$timesUnique = array_unique($timesFormatted);
		$useTime = (
			count($timesUnique) > 1
			|| (count($timesUnique) === 1 && reset($timesUnique) !== '00:00')
		);

		if (count($datesUnique) === 1)
		{
			$result =
				reset($datesUnique)
				. ($useTime ? ' ' . implode('-', $timesUnique) : '');
		}
		else
		{
			$parts = [];

			foreach ($datesFormatted as $key => $dateFormatted)
			{
				$timeFormatted = $timesFormatted[$key];

				$parts[] =
					$dateFormatted
					. ($useTime ? ' ' . $timeFormatted : '');
			}

			$result = implode(' - ', $parts);
		}

		return $result;
	}
}