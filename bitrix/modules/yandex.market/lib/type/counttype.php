<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

class CountType extends NumberType
{
	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$result = true;

		if (!is_numeric($value))
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_NUMBER_ERROR_NOT_NUMERIC'));
			}
		}
		else if ((float)$value < 0 && $nodeResult)
		{
			$nodeResult->registerWarning(Market\Config::getLang('TYPE_NUMBER_ERROR_NEGATIVE'));
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$result = parent::format($value, $context, $node, $nodeResult);

		return $result > 0 ? $result : 0;
	}
}