<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\ShipmentConfirm;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $shipmentId;
	protected $externalShipmentId;
	protected $orderIds;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/first-mile/shipments/%s/confirm.json',
			$this->getCampaignId(),
			$this->getShipmentId()
		);
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function getQuery()
	{
		return [
			'externalShipmentId' => $this->getExternalShipmentId(),
			'orderIds' => $this->getOrderIds(),
		];
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

	/** @return string */
	public function getExternalShipmentId()
	{
		Market\Reference\Assert::notNull($this->externalShipmentId, 'externalShipmentId');

		return $this->externalShipmentId;
	}

	/** @param string $externalShipmentId */
	public function setExternalShipmentId($externalShipmentId)
	{
		$this->externalShipmentId = $externalShipmentId;
	}

	/** @return int[] */
	public function getOrderIds()
	{
		Market\Reference\Assert::notNull($this->orderIds, 'orderIds');

		return $this->orderIds;
	}

	/** @param int[]|int $orderIds */
	public function setOrderIds($orderIds)
	{
		$this->orderIds = (array)$orderIds;
	}
}
