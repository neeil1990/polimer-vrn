<?php
namespace Yandex\Market\Template\Functions;

use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionCeil extends FunctionRound
{
	protected function applyRound($value)
	{
		return ceil($value);
	}
}