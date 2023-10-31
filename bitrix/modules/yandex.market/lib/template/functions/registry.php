<?php

namespace Yandex\Market\Template\Functions;

use Bitrix\Main;

class Registry
{
	const FUNCTION_NOT = 'not';
	const FUNCTION_SUM = 'sum';
	const FUNCTION_TOTAL = 'total';
	const FUNCTION_SUBTRACT = 'subtract';
	const FUNCTION_MULTIPLY = 'multiply';
	const FUNCTION_IF = 'if';
	const FUNCTION_FIRST = 'first';
	const FUNCTION_CHARACTERISTIC = 'characteristic';
	const FUNCTION_ROUND = 'round';
	const FUNCTION_FLOOR = 'floor';
	const FUNCTION_CEIL = 'ceil';

	protected static $typeMap;

	public static function getTypes()
	{
		return [
			static::FUNCTION_NOT,
			static::FUNCTION_SUM,
			static::FUNCTION_TOTAL,
			static::FUNCTION_SUBTRACT,
			static::FUNCTION_MULTIPLY,
			static::FUNCTION_IF,
			static::FUNCTION_FIRST,
			static::FUNCTION_CHARACTERISTIC,
			static::FUNCTION_ROUND,
			static::FUNCTION_FLOOR,
			static::FUNCTION_CEIL
		];
	}

	public static function isExists($type)
	{
		if (static::$typeMap === null)
		{
			static::$typeMap = array_flip(static::getTypes());
		}

		return isset(static::$typeMap[$type]);
	}

	public static function createInstance($type, $data = null)
	{
		$className = static::getTypeClassName($type);

		return new $className($data);
	}

	protected static function getTypeClassName($type)
	{
		$result = __NAMESPACE__ . '\Function' . ucfirst($type);

		if (!class_exists($result))
		{
			throw new Main\SystemException($result . ' not found');
		}

		return $result;
	}
}