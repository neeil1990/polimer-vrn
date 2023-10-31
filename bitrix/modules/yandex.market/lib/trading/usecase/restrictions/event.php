<?php

namespace Yandex\Market\Trading\UseCase\Restrictions;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Event extends Market\Reference\Event\Regular
{
	public static function getHandlers()
	{
		return [
			[
				'module' => 'sale',
				'event' => 'onSaleDeliveryRestrictionsClassNamesBuildList',
				'method' => 'onDeliveryBuildList',
			],
			[
				'module' => 'sale',
				'event' => 'onSalePaySystemRestrictionsClassNamesBuildList',
				'method' => 'onPaySystemBuildList',
			],
		];
	}

	public static function onDeliveryBuildList()
	{
		return static::onBuildList('Delivery');
	}

	public static function onPaySystemBuildList()
	{
		return static::onBuildList('PaySystem');
	}

	protected static function onBuildList($type)
	{
		$classMap = static::getClassMap($type);

		return new Main\EventResult(Main\EventResult::SUCCESS, $classMap);
	}

	protected static function getRules()
	{
		return [
			'ByPlatform',
		];
	}

	protected static function getClassMap($type)
	{
		if (!class_exists(Sale\Services\Base\Restriction::class)) { return []; }

		$result = [];

		foreach (static::getRules() as $rule)
		{
			try
			{
				$classFile = static::getRuleFile($rule, $type);
				$className = static::getRuleClass($rule, $type);

				$result[$className] = Market\Utils\IO\Path::absoluteToRelative($classFile->getPath());
			}
			catch (Main\SystemException $exception)
			{
				continue;
			}
		}

		return $result;
	}

	protected static function getRuleFile($rule, $type)
	{
		$path = static::makeRuleFilePath($rule, $type);
		$file = new Main\IO\File($path);

		if (!$file->isExists())
		{
			throw new Main\IO\FileNotFoundException($file->getPath());
		}

		return $file;
	}

	protected static function makeRuleFilePath($rule, $type)
	{
		return sprintf(
			'%s/%s/%s.php',
			__DIR__,
			Market\Data\TextString::toLower($rule),
			Market\Data\TextString::toLower($type)
		);
	}

	protected static function getRuleClass($rule, $type)
	{
		$className = static::makeRuleClassName($rule, $type);

		if (!class_exists($className))
		{
			throw new Main\ObjectNotFoundException();
		}

		if (!is_subclass_of($className, Sale\Services\Base\Restriction::class))
		{
			throw new Main\InvalidOperationException();
		}

		if (!$className::isAvailable())
		{
			throw new Main\NotSupportedException();
		}

		return $className;
	}

	protected static function makeRuleClassName($rule, $type)
	{
		return sprintf(
			'%s\\%s\\%s',
			__NAMESPACE__,
			$rule,
			$type
		);
	}
}