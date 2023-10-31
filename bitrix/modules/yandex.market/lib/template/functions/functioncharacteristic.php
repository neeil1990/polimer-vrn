<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionCharacteristic extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		list($values, $descriptions, $name) = $parameters;

		if (!is_array($descriptions) || !is_array($values)) { return null; }

		$nameIndex = array_search($name, $descriptions, true);

		if ($nameIndex === false || !isset($values[$nameIndex])) { return null; }

		return $values[$nameIndex];
	}
}
