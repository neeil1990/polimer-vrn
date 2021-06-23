<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class NumberType extends AbstractType
{
	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$errorCode = null;
		$sanitizedValue = $this->sanitizeValue($value);

		if ($sanitizedValue === null)
		{
			$errorCode = 'NOT_NUMERIC';
		}
		else if ((float)$sanitizedValue < 0)
		{
			$errorCode = 'NEGATIVE';
		}
		else if ($node && $node->getParameter('value_positive') === true)
		{
			$precision = $this->getPrecision($node);
			$minimalValue = ($precision > 0 ? pow(0.1, $precision) * 0.5 : 0.5);

			if ($value * $this->getRatio($node) < $minimalValue)
			{
				$errorCode = 'NON_POSITIVE';
			}
		}

		if ($errorCode === null)
		{
			$result = true;
		}
		else
		{
			$result = false;

			if ($nodeResult !== null)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_NUMBER_ERROR_' . $errorCode));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$precision = $this->getPrecision($node);
		$value = $this->sanitizeValue($value);
		$value *= $this->getRatio($node);

		return round($value, $precision);
	}

	protected function sanitizeValue($value)
	{
		if (is_numeric($value))
		{
			$result = $value;
		}
		else if (preg_match('/^\s*(\d+)(?:[,.](\d+))?/', $value, $matches))
		{
			$result = isset($matches[2])
				? (float)($matches[1] . '.' . $matches[2])
				: (float)$matches[1];
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function getPrecision(Market\Export\Xml\Reference\Node $node = null)
	{
		$precision = 2;

		if ($node)
		{
			$nodePrecision = $node->getParameter('value_precision');

			if ($nodePrecision !== null)
			{
				$precision = (int)$nodePrecision;
			}
		}

		return $precision;
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
}