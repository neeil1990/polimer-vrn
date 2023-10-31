<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

abstract class Model extends Market\Reference\Common\Model
{
	protected $relativePath;

	public static function initialize($fields, $relativePath = '')
	{
		$result = parent::initialize($fields);
		$result->setRelativePath($relativePath);

		return $result;
	}

	public function setRelativePath($path)
	{
		$this->relativePath = $path;
	}

	public function getId()
	{
		return $this->getField('id');
	}

	public function hasField($name)
	{
		return Market\Utils\Field::hasChainValue($this->fields, $name);
	}

	public function getField($name)
	{
		return Market\Utils\Field::getChainValue($this->fields, $name);
	}

	public function getRequiredField($name)
	{
		$value = $this->getField($name);

		if ($value === null || $value === '')
		{
			$fieldFullPath = $this->relativePath . $name;
			throw new Market\Exceptions\Api\ObjectPropertyException($fieldFullPath);
		}

		return $value;
	}

	/**
	 * @param $fieldKey
	 *
	 * @return \Yandex\Market\Reference\Common\Collection
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function getRequiredCollection($fieldKey)
	{
		$result = $this->getChildCollection($fieldKey);

		if ($result === null)
		{
			$fieldFullPath = $this->relativePath . $fieldKey;
			throw new Market\Exceptions\Api\ObjectPropertyException($fieldFullPath);
		}

		return $result;
	}

	protected function loadChildCollection($fieldKey)
	{
		$reference = $this->getChildCollectionReference();

		if (!isset($reference[$fieldKey])) { throw new Main\SystemException('child reference not found'); }

		$collectionClassName = $reference[$fieldKey];
		$childPath = $this->relativePath . $fieldKey;

		if ($this->hasField($fieldKey))
		{
			$dataList = (array)$this->getField($fieldKey);
		}
		else
		{
			$dataList = [];
		}

		return $collectionClassName::initialize($dataList, $this, $childPath);
	}

	protected function getChildCollectionReference()
	{
		return [];
	}

	protected function getRequiredModel($fieldKey)
	{
		$result = $this->getChildModel($fieldKey);

		if ($result === null)
		{
			$fieldFullPath = $this->relativePath . $fieldKey;
			throw new Market\Exceptions\Api\ObjectPropertyException($fieldFullPath);
		}

		return $result;
	}

	protected function loadChildModel($fieldKey)
	{
		$reference = $this->getChildModelReference();
		$modelClassName = isset($reference[$fieldKey]) ? $reference[$fieldKey] : null;
		$result = null;

		if (!isset($modelClassName)) { throw new Main\SystemException('child reference not found'); }

		if ($this->hasField($fieldKey))
		{
			/** @var Model $result */
			$data = (array)$this->getField($fieldKey);
			$childPath = $this->relativePath . $fieldKey . '.';
			$result = $modelClassName::initialize($data, $childPath);
			$result->setParent($this);
		}

		return $result;
	}

	protected function getChildModelReference()
	{
		return [];
	}
}