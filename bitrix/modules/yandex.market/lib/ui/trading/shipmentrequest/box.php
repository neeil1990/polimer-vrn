<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Bitrix\Main;
use Yandex\Market;

class Box extends Market\Api\Reference\Model
{
	public function getFulfilmentId()
	{
		return (string)$this->getRequiredField('FULFILMENT_ID');
	}

	public function getSize($size)
	{
		$dimensions = $this->getField('DIMENSIONS');
		$value = isset($dimensions[$size])
			? Market\Data\Number::normalize($dimensions[$size])
			: null;

		if ($value === null)
		{
			throw new Market\Exceptions\Api\ObjectPropertyException($this->relativePath . 'DIMENSIONS');
		}

		return $value;
	}

	/**
	 * @deprecated 
	 * @return BoxItemCollection
	 */
	public function getItems()
	{
		$items = $this->getRequiredCollection('ITEM');

		if (count($items) === 0)
		{
			throw new Market\Exceptions\Api\ObjectPropertyException($this->relativePath . 'ITEM');
		}

		return $items;
	}

	protected function getChildCollectionReference()
	{
		return [
			'ITEM' => BoxItemCollection::class,
		];
	}
}