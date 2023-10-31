<?php
namespace Yandex\Market\Template\Functions;

use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionFloor extends FunctionRound
{
	protected function applyRound($value)
	{
		return floor($value);
	}
}