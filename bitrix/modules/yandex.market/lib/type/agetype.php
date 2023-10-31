<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class AgeType extends AbstractType
{
	const UNIT_YEAR = 'year';
	const UNIT_MONTH = 'month';

	protected $unitYearValues = [
		0 => true,
		6 => true,
		12 => true,
		16 => true,
		18 => true,
	];

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$errorMessage = null;
		$valueInteger = (int)$value;
		$result = true;

		if (!is_numeric($value))
		{
			$errorMessage = 'NOT_NUMERIC';
		}
		else if ($valueInteger < 0)
		{
			$errorMessage = 'NEGATIVE';
		}
		else
		{
			$unit = $this->getNodeUnit($node);

			if ($unit === static::UNIT_MONTH)
			{
				$validateUnit = 'MONTH';
				$isMatchUnit = ($valueInteger >= 0 && $valueInteger <= 12);
			}
			else
			{
				$validateUnit = 'YEAR';
				$isMatchUnit = isset($this->unitYearValues[$valueInteger]);
			}

			if (!$isMatchUnit)
			{
				$errorMessage = 'INVALID_' . $validateUnit;
			}
		}

		if ($errorMessage !== null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_AGE_ERROR_' . $errorMessage));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		return (int)$value;
	}

	protected function getNodeUnit(Market\Export\Xml\Reference\Node $node = null)
	{
		$result = null;

		if ($node instanceof Market\Export\Xml\Tag\Age)
		{
			$result = $node->getValueUnit();
		}

		return $result;
	}
}