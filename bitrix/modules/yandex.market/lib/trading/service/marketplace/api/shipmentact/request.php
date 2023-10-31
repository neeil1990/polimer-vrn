<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\ShipmentAct;

use Yandex\Market;

class Request extends Market\Api\Partner\File\Request
{
	protected $shipmentId;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/first-mile/shipments/' . $this->getShipmentId() . '/act.json';
	}

	/** @return int */
	public function getShipmentId()
	{
		Market\Reference\Assert::notNull($this->shipmentId, 'shipmentId');

		return $this->shipmentId;
	}

	public function setShipmentId($shipmentId)
	{
		$this->shipmentId = $shipmentId;
	}
}
