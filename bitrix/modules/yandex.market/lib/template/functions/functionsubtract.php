<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionSubtract extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle()
	{
		return static::getLang('TEMPLATE_FUNCTION_SUBTRACT', null, 'subtract');
	}

	public function isMultiple()
	{
		return true;
	}

	public function calculate(array $parameters)
	{
		$firstParameter = array_shift($parameters);

		if ($firstParameter === null || !is_numeric($firstParameter))
		{
			$result = null;
		}
		else
		{
			$result = (float)$firstParameter;

			foreach ($parameters as $parameter)
			{
				if ($parameter !== null && is_numeric($parameter))
				{
					$result -= (float)$parameter;
				}
			}
		}

		return $result;
	}
}