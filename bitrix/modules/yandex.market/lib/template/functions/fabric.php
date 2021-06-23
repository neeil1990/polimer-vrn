<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

class Fabric
{
	protected static $internalCache = [];

	public static function createInstance($functionName)
	{
		if (static::isInternal($functionName))
		{
			$result = static::getInternal($functionName);
		}
		else
		{
			$iblockFunction = Iblock\Template\Functions\Fabric::createInstance($functionName);

			if (static::isDummy($iblockFunction) && Registry::isExists($functionName))
			{
				$result = static::getInternal($functionName);
			}
			else
			{
				$result = $iblockFunction;
			}
		}

		return $result;
	}

	protected static function isInternal($functionName)
	{
		return isset(static::$internalCache[$functionName]);
	}

	protected static function getInternal($functionName)
	{
		if (!isset(static::$internalCache[$functionName]))
		{
			static::$internalCache[$functionName] = Registry::createInstance($functionName);
		}

		return static::$internalCache[$functionName];
	}

	protected static function isDummy($function)
	{
		return !is_subclass_of($function, Iblock\Template\Functions\FunctionBase::class);
	}
}