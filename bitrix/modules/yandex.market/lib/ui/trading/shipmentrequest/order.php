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

	public function getInternalId()
	{
		return (int)$this->getRequiredField('INTERNAL_ID');
	}

	public function getAccountNumber()
	{
		return (string)$this->getRequiredField('ACCOUNT_NUMBER');
	}

	public function useAutoFinish()
	{
		return (string)$this->getField('AUTO_FINISH') === 'Y';
	}

	/** @return Basket */
	public function getBasket()
	{
		return $this->getRequiredCollection('BASKET');
	}

	/** @return BasketConfirm */
	public function getBasketConfirm()
	{
		return $this->getChildModel('BASKET_CONFIRM');
	}

	/** @return ShipmentCollection */
	public function getShipments()
	{
		return $this->getRequiredCollection('SHIPMENT');
	}

	public function useDimensions()
	{
		return $this->getField('USE_DIMENSIONS') !== 'N';
	}

	protected function getChildModelReference()
	{
		return [
			'BASKET_CONFIRM' => BasketConfirm::class,
		];
	}

	protected function getChildCollectionReference()
	{
		return [
			'BASKET' => Basket::class,
			'SHIPMENT' => ShipmentCollection::class,
		];
	}
}