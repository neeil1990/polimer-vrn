<?php

namespace Yandex\Market\Reference;

use Bitrix\Main;

class Assert
{
	public static function notNull($value, $argument, $message = null)
	{
		if ($value === null)
		{
			$message = $message !== null ? $message : sprintf('Argument "%s" is null', $argument);

			throw new Main\ArgumentException($message, $argument);
		}
	}

	public static function notEmpty($value, $argument, $message = null)
	{
		if (empty($value))
		{
			$message = $message !== null ? $message : sprintf('Argument "%s" is empty', $argument);

			throw new Main\ArgumentException($message, $argument);
		}
	}

	public static function typeOf($value, $className, $argument)
	{
		if (!($value instanceof $className))
		{
			throw new Main\ArgumentTypeException($argument, $className);
		}
	}

	public static function isArray($value, $argument)
	{
		if (!is_array($value))
		{
			throw new Main\ArgumentTypeException($argument, 'Array');
		}
	}

	public static function classExists($className)
	{
		if (!class_exists($className))
		{
			throw new Main\NotImplementedException(sprintf('class %s not exists', $className));
		}
	}

	public static function isSubclassOf($className, $parentName)
	{
		if (!is_subclass_of($className, $parentName))
		{
			throw new Main\InvalidOperationException(sprintf(
				'%s must extends %s',
				$className,
				$parentName
			));
		}
	}

	public static function methodExists($classOrObject, $method)
	{
		if (!method_exists($classOrObject, $method))
		{
			throw new Main\InvalidOperationException(sprintf(
				'Class %s method %s is missing',
				is_object($classOrObject) ? get_class($classOrObject) : $classOrObject,
				$method
			));
		}
	}

	public static function positiveInteger($value, $argument)
	{
		if (!is_int($value))
		{
			throw new Main\ArgumentTypeException($argument, 'integer');
		}

		if ($value <= 0)
		{
			throw new Main\ArgumentException(sprintf('%s must be positive', $argument));
		}
	}
}