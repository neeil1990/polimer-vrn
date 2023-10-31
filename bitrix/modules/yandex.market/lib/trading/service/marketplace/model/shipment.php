<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;

class Shipment extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getField('id');
	}

	public function getExternalId()
	{
		return (string)$this->getField('externalId');
	}

	public function getPlanIntervalFrom()
	{
		return Market\Data\DateTime::convertFromService($this->getField('planIntervalFrom'), \DateTime::ATOM);
	}

	public function getPlanIntervalTo()
	{
		return Market\Data\DateTime::convertFromService($this->getField('planIntervalTo'), \DateTime::ATOM);
	}

	public function getShipmentType()
	{
		return (string)$this->getField('shipmentType');
	}

	public function getStatus()
	{
		return (string)$this->getField('status');
	}

	public function getStatusDescription()
	{
		return (string)$this->getField('statusDescription');
	}

	public function getDraftCount()
	{
		return Market\Data\Number::normalize($this->getField('draftCount'));
	}

	public function getPlannedCount()
	{
		return Market\Data\Number::normalize($this->getField('plannedCount'));
	}

	public function getFactCount()
	{
		return Market\Data\Number::normalize($this->getField('factCount'));
	}

	/** @return Shipment\DeliveryService */
	public function getDeliveryService()
	{
		return $this->getChildModel('deliveryService');
	}

	protected function getChildModelReference()
	{
		return [
			'deliveryService' => Shipment\DeliveryService::class,
		];
	}
}