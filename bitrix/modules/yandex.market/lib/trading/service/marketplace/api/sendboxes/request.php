<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendBoxes;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $shipmentId;
	protected $boxes;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/orders/' . $this->getOrderId() .'/delivery/shipments/' . $this->getShipmentId() .'/boxes.json';
	}

	public function getQuery()
	{
		return [
			'boxes' => $this->getBoxes()
		];
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_PUT;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;
	}

	public function getOrderId()
	{
		if ($this->orderId === null)
		{
			throw new Main\SystemException('orderId not set');
		}

		return (string)$this->orderId;
	}

	public function setShipmentId($shipmentId)
	{
		$this->shipmentId = $shipmentId;
	}

	public function getShipmentId()
	{
		return (string)($this->shipmentId ?: 1);
	}

	public function setBoxes($boxes)
	{
		$this->boxes = $boxes;
	}

	public function getBoxes()
	{
		if ($this->boxes === null)
		{
			throw new Main\SystemException('boxes set');
		}

		return (array)$this->boxes;
	}
}