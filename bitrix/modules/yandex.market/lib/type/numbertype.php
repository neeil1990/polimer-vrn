<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class NumberType extends AbstractType
{
	const ROUND_FLOOR = 'floor';
	const ROUND_CEIL = 'ceil';

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

			if ($sanitizedValue * $this->getRatio($node) < $minimalValue)
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

		return $this->roundValue($value, $precision, $node);
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

	protected function roundValue($value, $precision, Market\Export\Xml\Reference\Node $node = null)
	{
		$rule = $this->getRoundRule($node);

		if ($rule === static::ROUND_FLOOR)
		{
			$result = $this->callRound('floor', $value, $precision);
		}
		else if ($rule === static::ROUND_CEIL)
		{
			$result = $this->callRound('ceil', $value, $precision);
		}
		else
		{
			$result = round($value, $precision);
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

	protected function getRoundRule(Market\Export\Xml\Reference\Node $node = null)
	{
		if ($node === null) { return null; }

		return $node->getParameter('value_round');
	}

	protected function callRound($method, $value, $precision)
	{
		if ($precision <= 0) { return $method($value); }

		$multiplier = 10 ** $precision;

		return $method($value * $multiplier) / $multiplier;
	}
}