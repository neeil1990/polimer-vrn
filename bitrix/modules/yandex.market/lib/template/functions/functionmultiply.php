<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionMultiply extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle()
	{
		return static::getLang('TEMPLATE_FUNCTION_MULTIPLY', null, 'multiply');
	}

	public function isMultiple()
	{
		return true;
	}

	public function calculate(array $parameters)
	{
		$result = null;

		foreach ($parameters as $parameter)
		{
			if ($parameter === null || !is_numeric($parameter))
			{
				$result = null;
				break;
			}
			else if ($result === null)
			{
				$result = (float)$parameter;
			}
			else
			{
				$result *= (float)$parameter;
			}
		}

		return $result;
	}
}