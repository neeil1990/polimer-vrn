<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DaysType extends NumberType
{
	protected $splitCacheValue;
	protected $splitCacheResult;

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$splitValue = $this->splitValue($value);
		$result = true;

		if ($splitValue === null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_DAYS_ERROR_NOT_PARSED'));
			}
		}
		else
		{
			$previousValue = null;

			foreach ($splitValue as $valuePart)
			{
				if (!parent::validate($valuePart, $context, $node, $nodeResult))
				{
					$result = false;
					break;
				}

				if ($previousValue !== null && $previousValue >= $valuePart)
				{
					$result = false;

					if ($nodeResult)
					{
						$nodeResult->registerError(Market\Config::getLang('TYPE_DAYS_ERROR_NOT_ASC_ORDER'));
					}

					break;
				}

				$previousValue = $valuePart;
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$splitValue = $this->splitValue($value);

		return $splitValue !== null ? implode('-', $splitValue) : '';
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

			if (is_numeric($value))
			{
				$result = [ $value ];
			}
			else if (preg_match('/^(\d+(?:\.\d+)?)[^0-9.]{1,3}(\d+(?:\.\d+)?)$/', $value, $matches))
			{
				$result = [];

				for ($i = 1; $i <= 2; $i++)
				{
					$result[] = (int)$matches[$i];
				}
			}

			$this->splitCacheValue = $value;
			$this->splitCacheResult = $result;
		}

		return $result;
	}
}