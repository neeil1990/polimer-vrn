<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionIf extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle()
	{
		return static::getLang('TEMPLATE_FUNCTION_IF', null, 'if');
	}

	public function isMultiple()
	{
		return true;
	}

	public function calculate(array $parameters)
	{
		if (!isset($parameters[0]))
		{
			$resultKey = 2;
		}
		else
		{
			$booleanFirst = Market\Utils\Value::toBoolean($parameters[0]);
			$resultKey = $booleanFirst ? 1 : 2;
		}

		return isset($parameters[$resultKey]) ? $parameters[$resultKey] : null;
	}
}