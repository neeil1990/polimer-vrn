<?php

namespace Yandex\Market\Reference\Common;

use Bitrix\Main;
use Yandex\Market;

abstract class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/** @var Model[] */
	protected $collection = [];
	/** @var Model */
	protected $parent;

	/** @return Model */
	public static function getItemReference()
	{
		throw new Main\NotImplementedException();
	}

	public static function initialize($dataList, Model $parent = null)
	{
		$collection = new static();

		if ($parent !== null)
		{
			$collection->setParent($parent);
		}

		foreach ($dataList as $data)
		{
			$collection->createItem($data);
		}

		return $collection;
	}

	public function getParent()
	{
		return $this->parent;
	}

	public function setParent(Model $model)
	{
		$this->parent = $model;
	}

	public function addItem(Model $model)
	{
		$this->collection[] = $model;
	}

	public function createItem($data)
	{
		$modelClassName = static::getItemReference();

		if (!isset($modelClassName)) { throw new Main\SystemException('reference item not defined'); }

		$model = $modelClassName::initialize($data);
		$model->setCollection($this);

		$this->addItem($model);

		return $model;
	}

	public function getItemById($id)
	{
		$result = null;

		foreach ($this->collection as $item)
		{
			if ((string)$item->getId() === (string)$id)
			{
				$result = $item;
				break;
			}
		}

		return $result;
	}

	protected function applyFilter(Model $setup, $filter)
	{
		if ($filter === null)
		{
			$result = true;
		}
		else if (is_array($filter))
		{
			$result = true;

			foreach ($filter as $key => $value)
			{
				if ($setup->getField($key) !== $value)
				{
					$result = false;
					break;
				}
			}
		}
		else if (is_string($filter))
		{
			$fieldValue = (string)$setup->getField($filter);

			$result = (
				$fieldValue === Market\Reference\Storage\Table::BOOLEAN_Y
				|| $fieldValue === 'Y'
			);
		}
		else if (is_callable($filter))
		{
			$result = $filter($setup);
		}
		else
		{
			throw new Main\NotImplementedException('unknown filter type');
		}

		return $result;
	}

	public function getItemIndex(Model $item)
	{
		$key = array_search($item, $this->collection, true);

		return $key !== false ? $key : null;
	}

	public function toArray()
	{
		$result = [];

		foreach ($this->collection as $item)
		{
			$result[] = $item->getFields();
		}

		return $result;
	}

	/*
	 * Array iterator
	 * */
	public function getIterator()
	{
		return new \ArrayIterator($this->collection);
	}

	/**
	 * Whether a offset exists
	 */
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]) || array_key_exists($offset, $this->collection);
	}

	/**
	 * Offset to retrieve
	 */
	public function offsetGet($offset)
	{
		if (isset($this->collection[$offset]) || array_key_exists($offset, $this->collection))
		{
			return $this->collection[$offset];
		}

		return null;
	}

	/**
	 * Offset to set
	 */
	public function offsetSet($offset, $value)
	{
		if($offset === null)
		{
			$this->collection[] = $value;
		}
		else
		{
			$this->collection[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	public function count()
	{
		return count($this->collection);
	}

	/**
	 * Return the current element
	 */
	public function current()
	{
		return current($this->collection);
	}

	/**
	 * Move forward to next element
	 */
	public function next()
	{
		return next($this->collection);
	}

	/**
	 * Return the key of the current element
	 */
	public function key()
	{
		return key($this->collection);
	}

	/**
	 * Checks if current position is valid
	 */
	public function valid()
	{
		$key = $this->key();
		return $key !== null;
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind()
	{
		return reset($this->collection);
	}
}