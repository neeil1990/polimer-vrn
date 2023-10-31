<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionTotal extends Iblock\Template\Functions\FunctionBase
	implements HasConfiguration
{
	use Market\Reference\Concerns\HasMessage;

	public function getTitle()
	{
		return static::getMessage('TITLE', null, 'total');
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
		}

		return $result;
	}
}