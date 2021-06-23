<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;
use Bitrix\Main;

class Shipment extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getRequiredField('ID');
	}

	public function getBoxes()
	{
		$boxes = $this->getRequiredCollection('BOX');

		if (count($boxes) === 0)
		{
			throw new Market\Exceptions\Api\ObjectPropertyException($this->relativePath . 'BOX');
		}

		return $boxes;
	}

	protected function getChildCollectionReference()
	{
		return [
			'BOX' => BoxCollection::class,
		];
	}
}