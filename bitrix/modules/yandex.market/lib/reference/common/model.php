<?php

namespace Yandex\Market\Reference\Common;

use Bitrix\Main;

abstract class Model
{
	protected static $internalIndex = 0;

	/** @var string */
	protected $internalId = null;
	/** @var array */
	protected $fields;
	/** @var Model */
	protected $parent;
	/** @var Collection */
	protected $collection;
	/** @var Collection[] */
	protected $childCollection = [];
	/** @var Model[] */
	protected $childModel = [];

	public static function initialize($fields)
	{
		return new static($fields);
	}

	public function __construct(array $fields = [])
	{
		$this->fields = $fields;
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function hasField($name)
	{
		return array_key_exists($name, $this->fields);
	}

	/**
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function getField($name)
	{
		return isset($this->fields[$name]) ? $this->fields[$name] : null;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setField($name, $value)
	{
		$this->fields[$name] = $value;
	}

	/**
	 * @return mixed|null
	 */
	public function getId()
	{
		return $this->getField('ID');
	}

	/**
	 * @return mixed
	 */
	public function getInternalId()
	{
		$id = $this->getId();

		if ($id !== null && $id !== '')
		{
			// nothing
		}
		else if ($this->internalId !== null)
		{
			$id = $this->internalId;
		}
		else
		{
			$id = 'n' . static::$internalIndex;
			$this->internalId = $id;

			++static::$internalIndex;
		}

		return $id;
	}

	/**
	 * @param Model $parent
	 */
	public function setParent(Model $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return Model|null
	 */
	public function getParent()
	{
		$result = null;

		if ($this->parent !== null)
		{
			$result = $this->parent;
		}
		else if ($this->collection !== null)
		{
			$result = $this->collection->getParent();
		}

		return $result;
	}

	/**
	 * @param Collection $collection
	 */
	public function setCollection(Collection $collection)
	{
		$this->collection = $collection;
	}

	/**
	 * @return Collection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	protected function getChildCollection($fieldKey)
	{
		if (!isset($this->childCollection[$fieldKey]))
		{
			$this->childCollection[$fieldKey] = $this->loadChildCollection($fieldKey);
		}

		return $this->childCollection[$fieldKey];
	}

	/**
	 * @param $fieldKey
	 * @return Collection|null
	 */
	protected function loadChildCollection($fieldKey)
	{
		return null;
	}

	/**
	 * @param $fieldKey
	 * @return Model|null
	 */
	protected function getChildModel($fieldKey)
	{
		if (!isset($this->childModel[$fieldKey]))
		{
			$this->childModel[$fieldKey] = $this->loadChildModel($fieldKey);
		}

		return $this->childModel[$fieldKey];
	}

	/**
	 * @param $fieldKey
	 * @return Model|null
	 */
	protected function loadChildModel($fieldKey)
	{
		return null;
	}
}