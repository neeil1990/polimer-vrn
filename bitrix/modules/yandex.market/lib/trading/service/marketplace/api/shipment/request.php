<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Shipment;

use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $shipmentId;
	protected $externalShipmentId;
	protected $orderIds;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/first-mile/shipments/%s.json',
			$this->getCampaignId(),
			$this->getShipmentId()
		);
	}

	public function buildResponse($data)
	{
		return new Response($data);
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
