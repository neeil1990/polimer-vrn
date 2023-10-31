<?php

namespace Yandex\Market\Type;

use Yandex\Market;

class PeriodType extends AbstractType
{
	use Market\Reference\Concerns\HasMessage;

	const UNIT_HOUR = 'TH';
	const UNIT_DAY = 'D';
	const UNIT_MONTH = 'M';
	const UNIT_YEAR = 'Y';

	protected $wordForms;
	protected $units = [
		self::UNIT_YEAR => 1,
		self::UNIT_MONTH => 12,
		self::UNIT_DAY => 30,
		self::UNIT_HOUR => 24,
	];

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$errorCode = null;

		if ($this->isPrepared($value))
		{
			$value = Market\Data\TextString::toUpper(trim($value));

			if (!preg_match('/^P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/', $value))
			{
				$errorCode = 'PREPARED_INVALID';
			}
		}
		else
		{
			$numbers = $this->parseText($value, 0);

			if (empty($numbers))
			{
				$errorCode = 'NUMBER_NOT_FOUND';
			}
			else
			{
				list($number, $unit) = reset($numbers);

				if ($number <= 0)
				{
					$errorCode = 'NUMBER_NOT_POSITIVE';
				}
				else if ($unit === null && $this->getUnit($node) === null)
				{
					$errorCode = 'UNIT_NOT_DEFINED';
				}
			}
		}

		if ($errorCode !== null && $nodeResult)
		{
			$nodeResult->registerError(self::getMessage('ERROR_' . $errorCode), $errorCode);
		}

		return ($errorCode === null);
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		if ($this->isPrepared($value))
		{
			$result = Market\Data\TextString::toUpper(trim($value));
		}
		else
		{
			$parts = [];

			foreach ($this->parseText($value) as list($number, $unit))
			{
				if ($number <= 0) { continue; }

				if ($unit === null)
				{
					$unit = $this->getUnit($node) ?: static::UNIT_DAY;
				}

				$parts[$unit] = (float)$number;
			}

			$parts = $this->periodPartsToInteger($parts);
			$parts = $this->periodPartsOverlap($parts);

			$result = $this->gluePeriodParts($parts);
		}

		return $result;
	}

	protected function periodPartsToInteger(array $parts)
	{
		$left = 0;
		$result = [];

		foreach ($this->units as $unit => $ratio)
		{
			$number = isset($parts[$unit]) ? $parts[$unit] : 0;
			$number += $left * $ratio;

			if ($number <= 0) { continue; }

			$integer = (int)$number;
			$left = round($number - $integer, 2);

			$result[$unit] = $integer;
		}

		return $result;
	}

	protected function periodPartsOverlap(array $parts)
	{
		$limits = [
			[ static::UNIT_DAY, 365, static::UNIT_YEAR ],
			[ static::UNIT_MONTH, 12, static::UNIT_YEAR ],
		];

		foreach ($limits as list($unitFrom, $limit, $unitTo))
		{
			if (isset($parts[$unitFrom]) && $parts[$unitFrom] >= $limit)
			{
				if (!isset($parts[$unitTo])) { $parts[$unitTo] = 0; }

				$parts[$unitTo] += (int)floor($parts[$unitFrom] / $limit);
				$parts[$unitFrom] %= $limit;
			}
		}

		return $parts;
	}

	protected function gluePeriodParts(array $parts)
	{
		$isTimeStarted = false;
		$result = '';

		foreach ($this->units as $unit => $ratio)
		{
			$number = isset($parts[$unit]) ? (int)$parts[$unit] : 0;

			if ($number <= 0) { continue; }

			if ($result === '') { $result .= 'P'; }

			if (!$isTimeStarted && Market\Data\TextString::getPosition($unit, 'T') === 0)
			{
				$result .= 'T';
				$isTimeStarted = true;
			}

			if ($isTimeStarted)
			{
				$unit = Market\Data\TextString::getSubstring($unit, 1);
			}

			$result .= $number . $unit;
		}

		return $result;
	}

	public function isPrepared($value)
	{
		return (
			is_string($value)
			&& Market\Data\TextString::getPositionCaseInsensitive(ltrim($value), 'P') === 0
		);
	}

	protected function parseText($value, $limit = null)
	{
		if (is_numeric($value))
		{
			return [
				[ (float)$value, null ],
			];
		}

		$result = [];
		$search = (string)$value;
		$pattern = '/^\s*(?P<base>\d+)(?:[,.](?P<decimal>\d+))?\s*(?:(?P<unit>[^\d\W]+)|$)/u';

		while ($search !== '' && Market\Data\TextString::match($pattern, $search, $matches))
		{
			if (!empty($matches['unit']))
			{
				$unit = $this->resolveWordUnit($matches['unit']);
				$valid = ($unit !== null);
			}
			else
			{
				$unit = null;
				$valid = empty($result);
			}

			if ($valid)
			{
				$number = !empty($matches['decimal']) ? (float)($matches['base'] . '.' . $matches['decimal']) : (float)$matches['base'];

				$result[] = [ $number, $unit ];

				if ($limit !== null && count($result) >= $limit) { break; }
			}

			$search = Market\Data\TextString::getSubstring(
				$search,
				Market\Data\TextString::getLength($matches[0])
			);
			$search = ltrim($search, '.'); // abbreviations support
		}

		return $result;
	}

	protected function resolveWordUnit($word)
	{
		$forms = $this->getWordForms();
		$word = Market\Data\TextString::toLower($word);

		return isset($forms[$word]) ? $forms[$word] : null;
	}

	protected function getWordForms()
	{
		if ($this->wordForms === null)
		{
			$this->wordForms = $this->loadWordForms();
		}

		return $this->wordForms;
	}

	protected function loadWordForms()
	{
		$result = [];

		foreach ($this->units as $unit => $ratio)
		{
			$formsGlued = self::getMessage('UNIT_WORD_' . $unit);
			$formsGlued = Market\Data\TextString::toLower($formsGlued);
			$forms = explode('|', $formsGlued);

			$result += array_fill_keys($forms, $unit);
		}

		return $result;
	}

	protected function getUnit(Market\Export\Xml\Reference\Node $node = null)
	{
		if ($node === null) { return null; }

		$unit = (string)$node->getParameter('value_unit');

		return $unit !== '' ? $unit : null;
	}
}