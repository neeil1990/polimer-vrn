<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

/** @deprecated */
class ConditionType extends StringType
{
	const TYPE_NEW = 'new';
	const TYPE_LIKE_NEW = 'likenew';
	const TYPE_USED = 'used';

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		if ($value === static::TYPE_LIKE_NEW || $value === static::TYPE_USED)
		{
			$result = true;
		}
		else if ($value === static::TYPE_NEW)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->invalidate();
			}
		}
		else
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_CONDITION_ERROR_INVALID', [
					'#VALUE#' => $this->truncateText($value, 10),
					'#AVAILABLE#' => implode(', ', [
						static::TYPE_NEW,
						static::TYPE_LIKE_NEW,
						static::TYPE_USED,
					])
				]));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		return $value;
	}
}