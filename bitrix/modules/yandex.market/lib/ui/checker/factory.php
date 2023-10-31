<?php

namespace Yandex\Market\Ui\Checker;

use Bitrix\Main;

class Factory
{
	/**
	 * @param string $className
	 *
	 * @return Reference\AbstractTest
	 * @throws Main\SystemException
	 */
	public static function make($className)
	{
		if (!class_exists($className))
		{
			throw new Main\SystemException(sprintf(
				'test %s class not exists',
				$className
			));
		}

		if (!is_subclass_of($className, Reference\AbstractTest::class))
		{
			throw new Main\SystemException(sprintf(
				'test %s class must extends %s',
				$className,
				Reference\AbstractTest::class
			));
		}

		return new $className;
	}
}