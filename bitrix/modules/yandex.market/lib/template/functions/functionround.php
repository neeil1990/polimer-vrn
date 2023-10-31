<?php
namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionRound extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		if (!isset($parameters[0]) || !is_numeric($parameters[0])) { return null; }

		$value = (float)$parameters[0];
		$precision = isset($parameters[1]) ? (float)$parameters[1] : 1;

		if ($precision === 0.0) { $precision = 1; }

		$result = $this->applyRound($value / $precision) * $precision;

		if ($precision < 1)
		{
			$result = round($result, 4);
		}

		return $result;
	}

	protected function applyRound($value)
	{
		return round($value);
	}
}