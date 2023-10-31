<?php

namespace Yandex\Market\Confirmation\Behavior;

use Bitrix\Main;
use Yandex\Market;

class Manager
{
	use Market\Reference\Concerns\HasLang;

	const TYPE_FILE = 'file';
	const TYPE_META = 'meta';

	protected static $behaviors = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getTypes()
	{
		return [
			static::TYPE_FILE,
			static::TYPE_META,
		];
	}

	public static function getTitle($type)
	{
		return static::getLang('CONFIRMATION_BEHAVIOR_' . Market\Data\TextString::toUpper($type));
	}

	public static function getBehavior($code)
	{
		if (!isset(static::$behaviors[$code]))
		{
			static::$behaviors[$code] = static::createBehavior($code);
		}

		return static::$behaviors[$code];
	}

	/**
	 * @param $code
	 *
	 * @return Reference\Behavior
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function createBehavior($code)
	{
		$className = static::getBehaviorClassName($code);

		if (!class_exists($className))
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_NOT_EXISTS', [
				'#CODE#' => $code,
			]);
			throw new Main\SystemException($message);
		}

		if (!is_subclass_of($className, Reference\Behavior::class))
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_CLASS_INVALID', [
				'#CLASS#' => $className,
				'#REFERENCE#' => Reference\Behavior::class,
			]);
			throw new Main\SystemException($message);
		}

		return new $className;
	}

	protected static function getBehaviorClassName($code)
	{
		return __NAMESPACE__ . '\\' . ucfirst($code) . '\\Behavior';
	}
}