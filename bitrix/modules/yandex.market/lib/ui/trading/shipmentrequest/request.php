<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;
use Bitrix\Main;

/** @deprecated */
class Request extends Market\Api\Reference\Model
{
	public function getOrderId()
	{
		return (int)$this->getRequiredField('ORDER_ID');
	}

	public function getSetupId()
	{
		return (int)$this->getRequiredField('SETUP_ID');
	}

	public function getAccountNumber()
	{
		return (string)$this->getRequiredField('ORDER_NUM');
	}

	public function getShipmentId()
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