<?php

namespace Yandex\Market\Utils\Delivery;

use Yandex\Market;
use Bitrix\Main;

class PeriodParser
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function parse($text)
	{
		$fromParts = [];
		$toParts = [];
		$offset = 0;

		do
		{
			list($unit, $position, $word) = static::searchUnit($text, $offset);
			$foundUnit = ($unit !== null);

			if ($foundUnit)
			{
				list($fromDigit, $toDigit) = static::searchDigits($text, $offset, $position);

				if ($fromDigit !== null)
				{
					$fromParts[$unit] = $fromDigit;
				}

				if ($toDigit !== null)
				{
					$toParts[$unit] = $toDigit;
				}

				$offset = $position + Market\Data\TextString::getLength($word);
			}
		}
		while ($foundUnit);

		return [
			static::combineInterval($fromParts),
			static::combineInterval($toParts),
		];
	}

	protected static function searchUnit($text, $offset = 0)
	{
		$matchUnit = null;
		$matchPosition = null;
		$matchWord = null;

		foreach (static::getUnitVariants() as $unit)
		{
			foreach (static::getUnitWords($unit) as $word)
			{
				$position = Market\Data\TextString::getPositionCaseInsensitive($text, $word, $offset);

				if (
					$position !== false
					&& ($matchPosition === null || $matchPosition > $position)
				)
				{
					$matchUnit = $unit;
					$matchPosition = $position;
					$matchWord = $word;
				}
			}
		}

		return [
			$matchUnit,
			$matchPosition,
			$matchWord
		];
	}

	protected static function searchDigits($text, $startPosition, $finishPosition)
	{
		$textBefore = Market\Data\TextString::getSubstring($text, $startPosition, $finishPosition);
		$result = [
			'FROM' => null,
			'TO' => null,
		];

		if (Market\Data\TextString::match(
			'/(?:(?P<pretext>[^\d\W]{1,3})\s*)?(?P<first>\d+(?:[.,]\d+)?)(?:\s*?(?P<glue>[^\d\W]{1,2}|-)\s*?(?P<second>\d+(?:[.,]\d+)?))?/u',
			$textBefore,
			$matches
		))
		{
			$firstType = 'FROM';

			if (isset($matches['pretext']))
			{
				$firstType = static::getPretextType($matches['pretext']) ?: $firstType;
			}

			$result[$firstType] = static::sanitizeDigit($matches['first']);

			if (isset($matches['second']))
			{
				$secondType = static::getPretextType($matches['glue']) ?: 'TO';
				$result[$secondType] = static::sanitizeDigit($matches['second']);
			}
		}

		return [ $result['FROM'], $result['TO'] ];
	}

	protected static function getUnitVariants()
	{
		return [
			'M',
			'W',
			'D',
			'TH',
		];
	}

	protected static function getUnitWords($unit)
	{
		$result = [];
		$forms = [
			$unit,
			$unit . '_2',
			$unit . '_5',
			$unit . '_SHORT',
		];

		foreach ($forms as $form)
		{
			$word = (string)static::getLang('UTILS_DELIVERY_PERIOD_' . $form, null, '');

			if ($word !== '')
			{
				$result[] = $word;
			}
		}

		return $result;
	}

	protected static function getPretextType($pretext)
	{
		$result = null;
		$pretext = trim($pretext);
		$pretextLower = Market\Data\TextString::toLower($pretext);
		$types = [
			'FROM',
			'TO',
		];

		foreach ($types as $type)
		{
			$word = (string)static::getLang('UTILS_DELIVERY_PERIOD_PRETEXT_' . $type, null, '');
			$wordLower = Market\Data\TextString::toLower($word);

			if ($word !== '' && $pretextLower === $wordLower)
			{
				$result = $type;
				break;
			}
		}

		return $result;
	}

	protected static function sanitizeDigit($digit)
	{
		$digit = str_replace(',', '.', $digit);
		$digit = preg_replace('/[^0-9.]/', '', $digit);

		return is_numeric($digit) ? (float)$digit : null;
	}

	protected static function combineInterval($parts)
	{
		$result = null;
		$hasTime = false;

		foreach (static::getUnitVariants() as $unit)
		{
			$value = isset($parts[$unit]) ? (int)$parts[$unit] : 0;

			if ($value > 0)
			{
				if ($result === null) { $result = 'P'; }

				if (Market\Data\TextString::getSubstring($unit, 0, 1) === 'T')
				{
					if (!$hasTime)
					{
						$result .= 'T';
						$hasTime = true;
					}

					$unit = Market\Data\TextString::getSubstring($unit, 1);
				}

				$result .= $value . $unit;
			}
		}

		return $result;
	}
}