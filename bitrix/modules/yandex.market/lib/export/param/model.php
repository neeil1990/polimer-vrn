<?php

namespace Yandex\Market\Export\Param;

use Yandex\Market;

class Model extends Market\Reference\Storage\Model
{
	/** @return class-string<Table> */
	public static function getDataClass()
	{
		return Table::class;
	}

	public function getSettings()
	{
		$fieldValue = $this->getField('SETTINGS');

		return is_array($fieldValue) ? $fieldValue : null;
	}

	/**
	 * @return Market\Export\ParamValue\Collection
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection PhpIncompatibleReturnTypeInspection
	 */
	public function getValueCollection()
	{
		return $this->getChildCollection('PARAM_VALUE');
	}

	public function initChildren()
	{
		if (!$this->hasField('CHILDREN'))
		{
			$this->setField('CHILDREN', []);
		}

		return $this->getChildren();
	}

	/**
	 * @return Market\Export\Param\Collection
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 * @noinspection PhpIncompatibleReturnTypeInspection
	 */
	public function getChildren()
	{
		return $this->getChildCollection('CHILDREN');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		if ($fieldKey === 'PARAM_VALUE')
		{
			$result = Market\Export\ParamValue\Collection::class;
		}
		else if ($fieldKey === 'CHILDREN')
		{
			$result = Market\Export\Param\Collection::class;
		}

		return $result;
	}
}