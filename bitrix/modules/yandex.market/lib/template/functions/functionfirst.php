<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionFirst extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle()
	{
		return static::getLang('TEMPLATE_FUNCTION_FIRST', null, 'first');
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
			if (!Market\Utils\Value::isEmpty($parameter))
			{
				$result = $parameter;
				break;
			}
		}

		return $result;
	}
}