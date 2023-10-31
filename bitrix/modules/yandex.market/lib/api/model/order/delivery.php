<?php

namespace Yandex\Market\Api\Model\Order;

use Yandex\Market;

class Delivery extends Market\Api\Model\Cart\Delivery
{
	public function getPrice()
	{
		return Market\Data\Number::normalize($this->getField('price'));
	}

	public function getVat()
	{
		return $this->getField('vat');
	}

	/** @return ShipmentCollection */
	public function getShipments()
	{
		return $this->getChildCollection('shipments');
	}

	/** @return TrackCollection|null */
	public function getTracks()
	{
		return $this->getChildCollection('tracks');
	}

	/** @return Dates|null */
	public function getDates()
	{
		return $this->getChildModel('dates');
	}

	public function getServiceName()
	{
		return (string)$this->getField('serviceName');
	}

	public function getServiceId()
	{
		return (string)$this->getField('deliveryServiceId');
	}

	protected function getChildCollectionReference()
	{
		return [
			'shipments' => ShipmentCollection::class,
			'tracks' => TrackCollection::class,
		];
	}

	protected function getChildModelReference()
	{
		return array_merge(parent::getChildModelReference(), [
			'dates' => Dates::class,
		]);
	}
}