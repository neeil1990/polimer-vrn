<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DatePeriodType extends DateType
{
	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		if ($this->isPeriod($value))
		{
			$value = Market\Data\TextString::toUpper(trim($value));
			$result = false;

			if (preg_match('/^P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/', $value))
			{
				$result = true;
			}
			else if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_DATEPERIOD_ERROR_INVALID'));
			}
		}
		else
		{
			$result = parent::validate($value, $context, $node, $nodeResult);
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		if ($this->isPeriod($value))
		{
			$result = Market\Data\TextString::toUpper(trim($value));
		}
		else
		{
			$result = parent::format($value, $context, $node, $nodeResult);
		}

		return $result;
	}

	protected function isPeriod($value)
	{
		return (
			is_string($value)
			&& Market\Data\TextString::getPositionCaseInsensitive(ltrim($value), 'P') === 0
		);
	}
}