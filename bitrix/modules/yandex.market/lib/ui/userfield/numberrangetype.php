<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;

class NumberRangeType
{
	public static function GetAdminListViewHtml($userField, $htmlControl)
	{
		$values = Helper\ComplexValue::asSingle($userField, $htmlControl);
		$valueFrom = isset($values['FROM']) ? (string)$values['FROM'] : '';
		$valueTo = isset($values['TO']) ? (string)$values['TO'] : '';
		$unit = static::getUnit($userField);
		$parts = [];
		$result = '';

		if ($valueFrom !== '')
		{
			$parts[] = $valueFrom;
		}

		if ($valueTo !== '')
		{
			$parts[] = $valueTo;
		}

		if (!empty($parts))
		{
			$result = implode('-', $parts);

			if ($unit)
			{
				$lastNumber = end($parts);
				$unitFormatted = is_array($unit) ? Market\Utils::sklon($lastNumber, $unit) : $unit;

				$result .= '&nbsp;' . $unitFormatted;
			}
		}

		return $result;
	}

	public static function GetEditFormHtml($userField, $htmlControl)
	{
		$values = Helper\ComplexValue::asSingle($userField, $htmlControl);
		$valueFrom = isset($values['FROM']) ? $values['FROM'] : null;
		$valueTo = isset($values['TO']) ? $values['TO'] : null;
		$unit = static::getUnit($userField);

		$result = View\Number::getControl($valueFrom, [
			'name' => $htmlControl['NAME'] . '[FROM]',
		]);
		$result .= '&nbsp;&mdash;&nbsp;';
		$result .= View\Number::getControl($valueTo, [
			'name' => $htmlControl['NAME'] . '[TO]',
		]);

		if ($result !== '' && $unit)
		{
			$result .=
				'&nbsp;'
				. (is_array($unit) ? end($unit) : $unit);
		}

		$htmlControl['VALIGN'] = 'middle';

		return $result;
	}

	protected static function getUnit($userField)
	{
		return isset($userField['SETTINGS']['UNIT']) ? $userField['SETTINGS']['UNIT'] : null;
	}
}