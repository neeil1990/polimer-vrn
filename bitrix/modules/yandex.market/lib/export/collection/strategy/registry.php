<?php
namespace Yandex\Market\Export\Collection\Strategy;

use Yandex\Market\Reference\Assert;

class Registry
{
	const PRODUCT_FILTER = 'productFilter';
	const IBLOCK_SECTION = 'iblockSection';

	public static function getTypes()
	{
		return array_keys(self::getClassMap());
	}

	/** @return array<string, Strategy> */
	public static function getStrategies()
	{
		$result = [];

		foreach (static::getTypes() as $type)
		{
			$result[$type] = static::createStrategy($type);
		}

		return $result;
	}

	/**
	 * @param string $type
	 * @return Strategy
	 */
	public static function createStrategy($type)
	{
		$types = static::getClassMap();

		Assert::notNull($types[$type], sprintf('types[%s]', $type));

		$className = $types[$type];

		Assert::classExists($className);
		Assert::isSubclassOf($className, Strategy::class);

		return new $className;
	}

	private static function getClassMap()
	{
		return [
			static::PRODUCT_FILTER => ProductFilter::class,
			static::IBLOCK_SECTION => IblockSection::class,
		];
	}
}