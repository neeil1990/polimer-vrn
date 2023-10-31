<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

class WeekdayType extends AbstractType
{
	use Market\Reference\Concerns\HasLang;

	protected $lastSanitizedValue;
	protected $lastSanitizedResult;
	protected $variants;
	protected $formatsMap;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$value = $this->sanitizeValue($value);
		$errorCode = null;

		if (is_numeric($value))
		{
			$valueInteger = (int)$value;

			if ($valueInteger < 0)
			{
				$errorCode = 'NUMERIC_NEGATIVE';
			}
			else if ($valueInteger > 6)
			{
				$errorCode = 'NUMERIC_EXCEED';
			}
		}
		else if ($this->getVariantDayNumber($value) === null)
		{
			$errorCode = 'INVALID';
		}

		if ($errorCode !== null && $nodeResult)
		{
			$nodeResult->registerError(static::getLang('TYPE_WEEKDAY_CODE_ERROR_' . $errorCode));
		}

		return ($errorCode === null);
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$value = $this->sanitizeValue($value);
		$format = 'INTERNATIONAL_FULL';

		if (is_numeric($value))
		{
			$result = $this->getVariant($value, $format);
		}
		else if ($this->isVariantMatch($value, $format))
		{
			$result = $value;
		}
		else
		{
			$dayNumber = $this->getVariantDayNumber($value);
			$result = $this->getVariant($dayNumber, $format);
		}

		return $result;
	}

	protected function sanitizeValue($value)
	{
		if ($this->lastSanitizedValue === $value)
		{
			$result = $this->lastSanitizedResult;
		}
		else
		{
			if (is_numeric($value))
			{
				$result = (int)$value;

				if ($result === 7)
				{
					$result = 0;
				}
			}
			else
			{
				$result = trim($value);
				$result = Market\Data\TextString::toUpper($result);
			}

			$this->lastSanitizedValue = $value;
			$this->lastSanitizedResult = $result;
		}

		return $result;
	}

	protected function getVariant($dayNumber, $format)
	{
		$variants = $this->getVariants($format);

		return $variants[$dayNumber];
	}

	protected function isVariantMatch($sanitizedValue, $format)
	{
		$map = $this->getFormatsMap();

		return isset($map[$format][$sanitizedValue]);
	}

	protected function getVariants($format)
	{
		if (!isset($this->variants[$format]))
		{
			$map = $this->getFormatsMap();

			if (!isset($map[$format]))
			{
				throw new Main\ArgumentException('unknown weekday format ' . $format);
			}

			$this->variants[$format] = array_flip($map[$format]);
		}

		return $this->variants[$format];
	}

	protected function getVariantDayNumber($sanitizedValue)
	{
		$formatsMap = $this->getFormatsMap();
		$result = null;

		foreach ($formatsMap as $map)
		{
			if (isset($map[$sanitizedValue]))
			{
				$result = $map[$sanitizedValue];
				break;
			}
		}

		return $result;
	}

	protected function getFormatsMap()
	{
		if ($this->formatsMap === null)
		{
			$this->formatsMap =
				$this->getInternationalMap()
				+ $this->getLocalMap();
		}

		return $this->formatsMap;
	}

	protected function getInternationalMap()
	{
		$date = new \DateTime();
		$interval = new \DateInterval('P1D');
		$prefix = 'INTERNATIONAL_';
		$formats = $this->getInternationalFormats();
		$formatKeys = array_keys($formats);
		$formatKeysWithPrefix = $this->appendFormatsPrefix($formatKeys, $prefix);
		$result = array_fill_keys($formatKeysWithPrefix, []);

		for ($i = 0; $i <= 6; $i++)
		{
			$weekdayNumber = $date->format('w');

			foreach ($formats as $formatKey => $format)
			{
				$weekdayName = $date->format($format);
				$weekdayName = Market\Data\TextString::toUpper($weekdayName);

				$result[$prefix . $formatKey][$weekdayName] = $weekdayNumber;
			}

			$date->add($interval);
		}

		return $result;
	}

	protected function getInternationalFormats()
	{
		return [
			'SHORT' => 'D',
			'FULL' => 'l',
		];
	}

	protected function getLocalMap()
	{
		$prefix = 'LOCAL_';
		$formats = $this->getLocalFormats();
		$formatsWithPrefix = $this->appendFormatsPrefix($formats, $prefix);
		$result = array_fill_keys($formatsWithPrefix, []);

		for ($weekdayNumber = 0; $weekdayNumber <= 6; $weekdayNumber++)
		{
			foreach ($formats as $format)
			{
				$langKey = sprintf('TYPE_WEEKDAY_LOCAL_%s_%s', $weekdayNumber, $format);
				$weekdayName = static::getLang($langKey, null, '');

				if ($weekdayName === '') { continue; }

				$weekdayName = Market\Data\TextString::toUpper($weekdayName);

				$result[$prefix . $format][$weekdayName] = $weekdayNumber;
			}
		}

		return $result;
	}

	protected function getLocalFormats()
	{
		return [
			'FULL',
			'TWO',
			'THREE',
		];
	}

	protected function appendFormatsPrefix($formats, $prefix)
	{
		$result = [];

		foreach ($formats as $format)
		{
			$result[] = $prefix . $format;
		}

		return $result;
	}
}