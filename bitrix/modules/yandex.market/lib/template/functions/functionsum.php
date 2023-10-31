<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionSum extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle()
	{
		return static::getLang('TEMPLATE_FUNCTION_SUM', null, 'sum');
	}

	public function isMultiple()
	{
		return true;
	}

	public function calculate(array $parameters)
	{
		$result = null;
		$isFirst = true;

		foreach ($parameters as $parameter)
		{
			if (!is_numeric($parameter))
			{
				// nothing
			}
			else if ($result === null)
			{
				$result = (float)$parameter;
			}
			else
			{
				$result += (float)$parameter;
			}

			if ($isFirst && $result <= 0) { break; }

			$isFirst = false;
		}

		return $result;
	}
}