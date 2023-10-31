<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DimensionsType extends AbstractType
{
	protected $splitCacheValue;
	protected $splitCacheResult;

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$splitValue = $this->splitValue($value);
		$errorMessage = null;
		$result = true;

		if ($splitValue === null)
		{
			$errorMessage = 'INVALID';
		}
		else
		{
			$unitRatio = $this->getRatio($node);
			$splitValue = $this->applyUnitRatio($splitValue, $unitRatio);

			foreach ($splitValue as $dimension)
			{
				if ($dimension <= 0)
				{
					$errorMessage = 'NOT_POSITIVE';
				}
			}
		}

		if ($errorMessage !== null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_DIMENSIONS_ERROR_' . $errorMessage));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$splitValue = $this->splitValue($value);
		$unitRatio = $this->getRatio($node);
		$splitValue = $this->applyUnitRatio($splitValue, $unitRatio);

		return implode('/', $splitValue);
	}

	protected function splitValue($value)
	{
		if ($value === $this->splitCacheValue)
		{
			$result = $this->splitCacheResult;
		}
		else
		{
			$result = null;
			$value = trim($value);

			if (preg_match('/^(\d+(?:[.,]\d+)?)\D*([^0-9.]{1,3})(\d+(?:[.,]\d+)?)\D*\2(\d+(?:[.,]\d+)?)/', $value, $matches))
			{
				$precision = 3;
				$result = [];

				for ($i = 1; $i <= 4; $i++)
				{
					if ($i === 2) { continue; } // is glue

					$match = $this->sanitizeValuePart($matches[$i]);
					$matchValue = round($match, $precision);

					if ($matchValue === 0.0 && ceil($match) === 1.0) // round and ceil is float
					{
						$matchValue = pow(0.1, $precision);
					}

					$result[] = $matchValue;
				}
			}

			$this->splitCacheValue = $value;
			$this->splitCacheResult = $result;
		}

		return $result;
	}

	protected function sanitizeValuePart($part)
	{
		if (Market\Data\TextString::getPosition($part, ',') !== false)
		{
			$part = str_replace(',', '.', $part);
		}

		return $part;
	}

	protected function getRatio(Market\Export\Xml\Reference\Node $node = null)
	{
		$result = 1;

		if ($node !== null)
		{
			$nodeValueRatio = (float)$node->getParameter('value_ratio');

			if ($nodeValueRatio > 0)
			{
				$result = $nodeValueRatio;
			}
		}

		return $result;
	}

	protected function applyUnitRatio($splitValue, $ratio)
	{
		$result = $splitValue;

		if ($splitValue !== null)
		{
			foreach ($result as &$value)
			{
				$value *= $ratio;
			}
			unset($value);
		}

		return $result;
	}
}