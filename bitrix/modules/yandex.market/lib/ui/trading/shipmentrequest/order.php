<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Yandex\Market;
use Bitrix\Main;

class Order extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getRequiredField('ID');
	}

	public function getSetupId()
	{
		return (int)$this->getRequiredField('SETUP_ID');
	}

	public function getAccountNumber()
	{
		return (string)$this->getRequiredField('ACCOUNT_NUMBER');
	}

	/** @return Basket */
	public function getBasket()
	{
		return $this->getRequiredCollection('BASKET');
	}

	/** @return ShipmentCollection */
	public function getShipments()
	{
		return $this->getRequiredCollection('SHIPMENT');
	}

	protected function getChildCollectionReference()
	{
		return [
			'BASKET' => Basket::class,
			'SHIPMENT' => ShipmentCollection::class,
		];
	}
}