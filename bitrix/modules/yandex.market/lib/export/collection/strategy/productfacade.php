<?php
namespace Yandex\Market\Export\Collection\Strategy;

use Yandex\Market\Export\CollectionProduct;
use Yandex\Market\Export\Filter;

class ProductFacade
{
	public static function emulateProductCollection($collectionId, $iblockId, array $filter)
	{
		return static::makeProductCollection($collectionId, [
			$iblockId => [ $filter ],
		]);
	}

	public static function makeProductCollection($collectionId, array $iblockFilters)
	{
		$collection = new CollectionProduct\Collection();

		foreach ($iblockFilters as $iblockId => $filters)
		{
			$product = static::makeProduct($collectionId, $iblockId, $filters);

			$product->setCollection($collection);
			$collection->addItem($product);
		}

		return $collection;
	}

	public static function makeProduct($collectionId, $iblockId, array $filters)
	{
		$product = new CollectionProduct\Model([
			'IBLOCK_ID' => $iblockId,
			'COLLECTION_ID' => $collectionId,
		]);
		$product->setFilterCollection(static::makeFilterCollection($filters));

		return $product;
	}

	protected static function makeFilterCollection(array $filters)
	{
		$filterCollection = new Filter\Collection();

		foreach ($filters as $filter)
		{
			$filterModel = Filter\Model::initialize([
				'ENTITY_TYPE' => Filter\Table::ENTITY_TYPE_COLLECTION_PRODUCT,
			]);

			$filterModel->setCollection($filterCollection);
			$filterModel->setPlainFilter((array)$filter);

			$filterCollection->addItem($filterModel);
		}

		return $filterCollection;
	}
}