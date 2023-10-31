<?php

namespace Yandex\Market\Reference\Storage;

use Yandex\Market;
use Bitrix\Main;

abstract class Collection extends Market\Reference\Common\Collection
{
	/** @var Model[] */
	protected $collection = [];
	/** @var Model */
	protected $parent;

	public static function getClassName()
	{
		return '\\' . get_called_class();
	}

	/**
	 * Загружаем коллекции для родительских сущностей
	 *
	 * @param Model[] $parents
	 * @param array   $filter
	 * @param string|callable $associationFlag
	 *
	 * @return static[]
	 */
	public static function loadBatch(array $parents, $filter, $associationFlag)
	{
		$result = static::makeBatchCollections($parents);

		if (!empty($result))
		{
			$items = static::queryItems($filter);

			static::applyBatchAssociationFlag($result, $items, $associationFlag);
		}

		return $result;
	}

	/**
	 * @param Model[] $parents
	 *
	 * @return static[]
	 */
	protected static function makeBatchCollections(array $parents)
	{
		$result = [];

		foreach ($parents as $parent)
		{
			$parentId = (string)$parent->getId();

			if ($parentId !== '')
			{
				$collection = new static();
				$collection->setParent($parent);

				$result[$parentId] = $collection;
			}
		}

		return $result;
	}

	/**
	 * @param Collection[] $collections
	 * @param Model[] $models
	 * @param string|callable $associationFlag
	 *
	 * @throws Main\ArgumentException
	 */
	protected static function applyBatchAssociationFlag(array $collections, array $models, $associationFlag)
	{
		/** @var Model $model */
		foreach ($models as $model)
		{
			if (is_string($associationFlag))
			{
				$linkValue = $model->getField($associationFlag);

				if (!isset($collections[$linkValue])) { continue; }

				$collection = $collections[$linkValue];

				$model->setCollection($collection);
				$collection->addItem($model);
			}
			else if (is_callable($associationFlag))
			{
				$associationFlag($collections, $model);
			}
			else
			{
				throw new Main\ArgumentException('unknown associationFlag format');
			}
		}
	}

	/**
	 * Загружаем коллекцию для родительской сущности
	 *
	 * @param Model $parent
	 * @param array $filter
	 *
	 * @return static
	 * @throws Main\SystemException
	 */
	public static function load(Model $parent, $filter)
	{
		if ($parent->getId() > 0)
		{
			$collection = static::loadByFilter($filter);
			$collection->setParent($parent);
		}
		else
		{
			$collection = new static();
			$collection->setParent($parent);
		}

		return $collection;
	}

	/**
	 * Загружаем коллекцию по фильтру
	 *
	 * @param array $filter
	 *
	 * @return static
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function loadByFilter($filter)
	{
		$collection = new static();

		foreach (static::queryItems($filter) as $model)
		{
			$model->setCollection($collection);
			$collection->addItem($model);
		}

		return $collection;
	}

	/**
	 * @param array|null $filter
	 *
	 * @return Model[]
	 * @throws Main\SystemException
	 */
	protected static function queryItems($filter)
	{
		$modelClassName = static::getItemReference();

		if (!isset($modelClassName)) { throw new Main\SystemException('reference item not defined'); }

		return $modelClassName::loadList($filter);
	}
}