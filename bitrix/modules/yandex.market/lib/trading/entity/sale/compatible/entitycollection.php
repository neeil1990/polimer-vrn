<?php

namespace Yandex\Market\Trading\Entity\Sale\Compatible;

use Bitrix\Sale;

class EntityCollection
{
	public static function isAnyItemDeleted(Sale\Internals\EntityCollection $collection)
	{
		if (method_exists($collection, 'isAnyItemDeleted')) { return $collection->isAnyItemDeleted(); }
		if (static::hasMissingIndex($collection)) { return true; }

		$internalIndex = static::getInternalIndex($collection);
		$internalCount = $internalIndex + 1;

		return $collection->count() < $internalCount;
	}

	protected static function hasMissingIndex(Sale\Internals\EntityCollection $collection)
	{
		$nextIndex = 0;
		$result = false;

		foreach ($collection as $index => $item)
		{
			if ($index !== $nextIndex)
			{
				$result = true;
				break;
			}

			++$nextIndex;
		}

		return $result;
	}

	protected static function getInternalIndex(Sale\Internals\EntityCollection $collection)
	{
		$reflection = new \ReflectionClass(Sale\Internals\EntityCollection::class);

		$indexProperty = $reflection->getProperty('index');
		$indexProperty->setAccessible(true);

		return $indexProperty->getValue($collection);
	}
}