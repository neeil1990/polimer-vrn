<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DeliveryOptionsType extends AbstractType
{
	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$errorMessage = null;
		$result = true;

		if (!is_array($value))
		{
			$errorMessage = 'NOT_ARRAY';
		}
		else
		{
			foreach ($value as $option)
			{
				if (!isset($option['COST']) || $option['COST'] === '')
				{
					$errorMessage = 'NOT_SET_COST';
					break;
				}
				else if (!isset($option['DAYS']) || $option['DAYS'] === '')
				{
					$errorMessage = 'NOT_SET_DAYS';
					break;
				}
			}
		}

		if ($errorMessage !== null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_DELIVERY_OPTIONS_ERROR_' . $errorMessage));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		return $value;
	}
}