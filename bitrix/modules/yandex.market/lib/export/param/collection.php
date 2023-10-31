<?php

namespace Yandex\Market\Export\Param;

use Yandex\Market;

/**
 * @property Model[] $collection
 */
class Collection extends Market\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::class;
	}

	public function initChildren()
	{
		foreach ($this->collection as $item)
		{
			$item->initChildren();
		}
	}

	public function preloadReference()
	{
		foreach ($this->collection as $item)
		{
			$item->getValueCollection();
		}
	}
}