<?php

namespace Yandex\Market\Trading\Service\Reference\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

abstract class Skeleton
{
	protected $provider;
	protected $values;
	protected $fieldset = [];
	protected $fieldsetCollection = [];
	protected $suppressRequired = false;

	public function __construct(TradingService\Reference\Provider $provider)
	{
		$this->provider = $provider;
	}

	public function __clone()
	{
		foreach ($this->fieldset as $key => $fieldset)
		{
			$this->fieldset[$key] = clone $fieldset;
		}

		foreach ($this->fieldsetCollection as $key => $fieldsetCollection)
		{
			$this->fieldsetCollection[$key] = clone $fieldsetCollection;
		}
	}

	abstract public function getFields(TradingEntity\Reference\Environment $environment, $siteId);

	public function extendValues(array $values)
	{
		$values = array_merge((array)$this->values, $values);

		$this->setValues($values);
	}

	public function setValues(array $values)
	{
		$leftValues = $this->setFieldsetValues($values);
		$leftValues = $this->setFieldsetCollectionValues($leftValues);

		$this->values = $leftValues;
		$this->applyValues();
	}

	protected function setFieldsetValues(array $values)
	{
		$map = $this->getFieldsetMap();

		if (empty($map))
		{
			$leftValues = $values;
		}
		else
		{
			foreach ($map as $key => $dummy)
			{
				$fieldsetValues = isset($values[$key]) && is_array($values[$key])
					? $values[$key]
					: [];

				$this->getFieldset($key)->setValues($fieldsetValues);
			}

			$leftValues = array_diff_key($values, $map);
		}

		return $leftValues;
	}

	protected function setFieldsetCollectionValues(array $values)
	{
		$map = $this->getFieldsetCollectionMap();

		if (empty($map))
		{
			$leftValues = $values;
		}
		else
		{
			foreach ($map as $key => $dummy)
			{
				$fieldsetValues = isset($values[$key]) && is_array($values[$key])
					? $values[$key]
					: [];

				$this->getFieldsetCollection($key)->setValues($fieldsetValues);
			}

			$leftValues = array_diff_key($values, $map);
		}

		return $leftValues;
	}

	protected function applyValues()
	{
		// nothing by default
	}

	public function takeChanges(Skeleton $previous)
	{
		// nothing by default
	}

	public function getValue($key, $default = null)
	{
		return isset($this->values[$key]) ? $this->values[$key] : $default;
	}

	public function getRequiredValue($key, $default = null)
	{
		$result = $this->getValue($key, $default);

		if (!$this->suppressRequired && Market\Utils\Value::isEmpty($result))
		{
			throw new Main\SystemException('Required option ' . $key . ' not set');
		}

		return $result;
	}

	public function suppressRequired($enable = true)
	{
		$this->suppressFieldsetRequired($enable);
		$this->suppressFieldsetCollectionRequired($enable);

		$this->suppressRequired = $enable;
	}

	protected function suppressFieldsetRequired($enable)
	{
		foreach ($this->fieldset as $fieldset)
		{
			$fieldset->suppressRequired($enable);
		}
	}

	protected function suppressFieldsetCollectionRequired($enable)
	{
		foreach ($this->fieldsetCollection as $fieldsetCollection)
		{
			$fieldsetCollection->suppressRequired($enable);
		}
	}

	public function getValues()
	{
		$result = $this->values;
		$result += $this->getFieldsetValues();
		$result += $this->getFieldsetCollectionValues();

		return $result;
	}

	protected function getFieldsetValues()
	{
		$result = [];

		foreach ($this->getFieldsetMap() as $key => $dummy)
		{
			$result[$key] = $this->getFieldset($key)->getValues();
		}

		return $result;
	}

	protected function getFieldsetCollectionValues()
	{
		$result = [];

		foreach ($this->getFieldsetCollectionMap() as $key => $dummy)
		{
			$result[$key] = $this->getFieldsetCollection($key)->getValues();
		}

		return $result;
	}

	/** @return array<string, Fieldset> */
	protected function getFieldsetMap()
	{
		return [];
	}

	/**
	 * @param $key
	 *
	 * @return Fieldset
	 * @throws Main\ArgumentException
	 */
	protected function getFieldset($key)
	{
		if (!isset($this->fieldset[$key]))
		{
			$this->fieldset[$key] = $this->createFieldset($key);
		}

		return $this->fieldset[$key];
	}

	protected function createFieldset($key)
	{
		$classMap = $this->getFieldsetMap();

		if (!isset($classMap[$key]))
		{
			throw new Main\ArgumentException(sprintf('Fieldset %s not defined', $key));
		}

		$className = $classMap[$key];

		return new $className($this->provider);
	}

	/** @return array<string, FieldsetCollection> */
	protected function getFieldsetCollectionMap()
	{
		return [];
	}

	/**
	 * @param $key
	 *
	 * @return FieldsetCollection
	 * @throws Main\ArgumentException
	 */
	protected function getFieldsetCollection($key)
	{
		if (!isset($this->fieldsetCollection[$key]))
		{
			$this->fieldsetCollection[$key] = $this->createFieldsetCollection($key);
		}

		return $this->fieldsetCollection[$key];
	}

	protected function createFieldsetCollection($key)
	{
		$classMap = $this->getFieldsetCollectionMap();

		if (!isset($classMap[$key]))
		{
			throw new Main\ArgumentException(sprintf('Fieldset collection %s not defined', $key));
		}

		$className = $classMap[$key];

		return new $className($this->provider);
	}
}