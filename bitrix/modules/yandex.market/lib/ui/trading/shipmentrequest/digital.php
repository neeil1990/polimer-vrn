<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;

class Digital extends Market\Api\Reference\Model
{
	/** @return DigitalItemCollection */
	public function getItems()
	{
		$items = $this->getRequiredCollection('ITEM');

		if (count($items) === 0)
		{
			throw new Market\Exceptions\Api\ObjectPropertyException($this->relativePath . 'ITEM');
		}

		return $items;
	}

	public function getSlip()
	{
		return (string)$this->getField('SLIP');
	}

	protected function getChildCollectionReference()
	{
		return [
			'ITEM' => DigitalItemCollection::class,
		];
	}
}