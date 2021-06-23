<?php

namespace Yandex\Market\Reference\Storage;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Model extends Market\Reference\Common\Model
{
	public static function getClassName()
	{
		return '\\' . get_called_class();
	}

	/**
	 * Загружаем список объектов по параметрам запроса d7
	 *
	 * @param array $parameters
	 *
	 * @return static[]
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function loadList($parameters = array())
	{
		$result = [];
		$tableClass = static::getDataClass();
		$distinctField = null;
		$distinctMap = null;

		if (isset($parameters['distinct']))
		{
			$distinctMap = [];

			if ($parameters['distinct'] === true)
			{
				$distinctField = $tableClass::getEntity()->getPrimary();
			}
			else
			{
				$distinctField = $parameters['distinct'];
			}

			unset($parameters['distinct']);
		}

		$query = $tableClass::getList($parameters);

		while ($itemData = $query->fetch())
		{
			if ($distinctField !== null && isset($itemData[$distinctField]))
			{
				$itemDistinctValue = $itemData[$distinctField];

				if (isset($distinctMap[$itemDistinctValue]))
				{
					continue;
				}
				else
				{
					$distinctMap[$itemDistinctValue] = true;
				}
			}

			$result[] = new static($itemData);
		}

		return $result;
	}

	/**
	 * Загружаем объект по ид
	 *
	 * @param $id int
	 *
	 * @return static
	 * @throws Main\ObjectNotFoundException
	 */
	public static function loadById($id)
	{
		$result = null;
		$tableClass = static::getDataClass();
		$query = $tableClass::getById($id);

		if ($itemData = $query->fetch())
		{
			$result = new static($itemData);
		}
		else
		{
			throw new Main\ObjectNotFoundException(Market\Config::getLang('REFERENCE_STORAGE_MODEL_LOAD_NOT_FOUND'));
		}

		return $result;
	}

	/**
	 * @return String|null
	 */
	public static function getParentReferenceField()
	{
		return null;
	}

	/**
	 * @return Table
	 */
	public static function getDataClass()
	{
		throw new Main\SystemException('not implemented');
	}

	protected function loadChildCollection($fieldKey)
	{
		$collectionClassName = $this->getChildCollectionReference($fieldKey);
		$result = null;

		if (!isset($collectionClassName)) { throw new Main\SystemException('child reference not found'); }

		if ($this->hasField($fieldKey))
		{
			$dataList = (array)$this->getField($fieldKey);
			$result = $collectionClassName::initialize($dataList, $this);
		}
		else if ($this->getId() > 0)
		{
			if ($this->hasSiblings() && $this->supportsBatchCollectionLoading($fieldKey))
			{
				$result = $this->batchChildCollection($collectionClassName, $fieldKey);
			}
			else
			{
				$result = $this->queryChildCollection($collectionClassName, $fieldKey);
			}
		}
		else
		{
			$result = new $collectionClassName;
			$result->setParent($this);
		}

		return $result;
	}

	/**
	 * Загрузка дочерней коллекции из базы данных
	 *
	 * @param Collection $collectionClassName
	 * @param string $fieldKey
	 *
	 * @return Collection
	 */
	protected function queryChildCollection($collectionClassName, $fieldKey)
	{
		$queryParams = $this->getChildCollectionQueryParameters($fieldKey);

		return $collectionClassName::load($this, $queryParams);
	}

	/**
	 * Групповая загрузка дочерних коллекций из базы данных
	 *
	 * @param Collection $collectionClassName
	 * @param string $fieldKey
	 *
	 * @return Collection
	 * @throws Main\ObjectNotFoundException
	 */
	protected function batchChildCollection($collectionClassName, $fieldKey)
	{
		$siblingsMap = $this->getBatchSiblingsMap($fieldKey);
		$queryParams = $this->getChildCollectionBatchParameters($fieldKey, $siblingsMap);
		$associationFlag = $this->getChildCollectionAssociationFlag($fieldKey);
		$siblingCollections = $collectionClassName::loadBatch($siblingsMap, $queryParams, $associationFlag);
		$result = null;

		foreach ($siblingsMap as $siblingId => $sibling)
		{
			if (!isset($siblingCollections[$siblingId])) { throw new Main\ObjectNotFoundException('batch child collection not loaded'); }

			$collection = $siblingCollections[$siblingId];

			if ($sibling === $this)
			{
				$result = $collection;
			}
			else
			{
				$sibling->passChildCollection($fieldKey, $collection);
			}
		}

		if ($result === null)
		{
			throw new Main\ObjectNotFoundException('batch child collection not loaded for self');
		}

		return $result;
	}

	/**
	 * Переопределить дочернюю коллекцию (используется для групповой загрузки)
	 *
	 * @param string $fieldKey
	 * @param Collection $collection
	 *
	 * @throws Main\SystemException
	 */
	protected function passChildCollection($fieldKey, Collection $collection)
	{
		if (isset($this->childCollection[$fieldKey]))
		{
			throw new Main\SystemException('child collection already loaded');
		}

		$this->childCollection[$fieldKey] = $collection;
	}

	/**
	 * Имеет ли соседние элементы для групповой загрузки
	 *
	 * @return bool
	 */
	protected function hasSiblings()
	{
		$collection = $this->getCollection();

		return $collection !== null && count($collection) > 1;
	}

	/**
	 * Соседние элементы для групповой загрузки дочерних элементов
	 *
	 * @param string $fieldKey
	 *
	 * @return array<int|string, Model>
	 */
	protected function getBatchSiblingsMap($fieldKey)
	{
		$result = [];

		/** @var Model $sibling */
		foreach ($this->getCollection() as $sibling)
		{
			if (!$sibling->supportsBatchCollectionLoading($fieldKey)) { continue; }

			$siblingId = $sibling->getId();

			if ((string)$siblingId !== '')
			{
				$result[$siblingId] = $sibling;
			}
		}

		return $result;
	}

	/**
	 * Поддерживает загрузку дочерней коллекции группой
	 *
	 * @param string $fieldKey
	 *
	 * @return bool
	 */
	protected function supportsBatchCollectionLoading($fieldKey)
	{
		return $this->getChildCollectionAssociationFlag($fieldKey) !== null;
	}

	/**
	 * Правило распределения моделей по коллекциям при групповой загрузке
	 *
	 * @param string $fieldKey
	 *
	 * @return string|callable|null
	 */
	protected function getChildCollectionAssociationFlag($fieldKey)
	{
		$tableClass = static::getDataClass();
		$reference = $tableClass::getReference();
		$result = null;

		if (isset($reference[$fieldKey]['LINK_FIELD']) && is_string($reference[$fieldKey]['LINK_FIELD']))
		{
			$result = $reference[$fieldKey]['LINK_FIELD'];
		}

		return $result;
	}

	protected function getChildCollectionBatchParameters($fieldKey, $siblingsMap)
	{
		$ids = array_keys($siblingsMap);

		return $this->makeCollectionQueryParameters($fieldKey, $ids);
	}

	protected function getChildCollectionQueryParameters($fieldKey)
	{
		return $this->makeCollectionQueryParameters($fieldKey, $this->getId());
	}

	protected function makeCollectionQueryParameters($fieldKey, $ids)
	{
		$tableClass = static::getDataClass();
		$reference = $tableClass::getReference($ids);

		if (!isset($reference[$fieldKey]['LINK'])) { throw new Main\SystemException('child reference not found'); }

		$queryParams = [
			'filter' => $tableClass::makeReferenceLinkFilter($reference[$fieldKey]['LINK'])
		];

		if (isset($reference[$fieldKey]['ORDER']))
		{
			$queryParams['order'] = $reference[$fieldKey]['ORDER'];
		}

		return $queryParams;
	}

	/**
	 * @param $fieldKey
	 *
	 * @return Collection
	 */
	protected function getChildCollectionReference($fieldKey)
	{
		return null;
	}
}