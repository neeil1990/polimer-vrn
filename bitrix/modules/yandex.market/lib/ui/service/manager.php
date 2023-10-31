<?php

namespace Yandex\Market\Ui\Service;

use Bitrix\Main;

class Manager
{
	const TYPE_COMMON = 'common';
	const TYPE_TURBO = 'turbo';
	const TYPE_BERU = 'beru';
	const TYPE_MARKETPLACE = 'marketplace';

	const BEHAVIOR_EXPORT = 'export';
	const BEHAVIOR_TRADING = 'trading';

	public static function getTypes()
	{
		return [
			static::TYPE_TURBO,
			static::TYPE_BERU,
			static::TYPE_MARKETPLACE,
		];
	}

	public static function isExists($type)
	{
		return in_array($type, static::getTypes(), true);
	}

	public static function getCommonInstance()
	{
		return static::getInstance(static::TYPE_COMMON);
	}

	/**
	 * @param string $type
	 *
	 * @return AbstractService
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getInstance($type)
	{
		if (!static::isKnownType($type))
		{
			throw new Main\SystemException('Unknown ui service "' . $type . '"');
		}

		$className = __NAMESPACE__ . '\\' . ucfirst($type);

		if (!class_exists($className))
		{
			throw new Main\SystemException('Ui service "' . $type . '" class not exists');
		}

		if (!is_subclass_of($className, AbstractService::class))
		{
			throw new Main\SystemException('Ui service "' . $type . '" must be instance of ' . AbstractService::class);
		}

		return new $className($type);
	}

	protected static function isKnownType($type)
	{
		$commonType = static::TYPE_COMMON;
		$specialTypes = static::getTypes();

		return ($type === $commonType || in_array($type, $specialTypes, true));
	}
}