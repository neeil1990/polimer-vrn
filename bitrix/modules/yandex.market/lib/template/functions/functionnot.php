<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionNot extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle()
	{
		return static::getLang('TEMPLATE_FUNCTION_NOT', null, 'not');
	}

	public function isMultiple()
	{
		return false;
	}

	public function calculate(array $parameters)
	{
		if (!isset($parameters[0]))
		{
			$result = null;
		}
		else
		{
			$result = Market\Utils\Value::toBoolean($parameters[0]) ? 'false' : 'true';
		}

		return $result;
	}
}