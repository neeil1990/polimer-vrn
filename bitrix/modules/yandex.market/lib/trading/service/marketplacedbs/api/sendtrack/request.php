<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\SendTrack;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $trackCode;
	protected $deliveryServiceId;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/delivery/track.json',
			$this->getCampaignId(),
			$this->getOrderId()
		);
	}

	public function getQuery()
	{
		return [
			'trackCode' => $this->getTrackCode(),
			'deliveryServiceId' => $this->getDeliveryServiceId(),
		];
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	protected function parseHttpResponse($httpResponse, $contentType = 'application/json')
	{
		if ($httpResponse === '')
		{
			return [];
		}

		return parent::parseHttpResponse($httpResponse, $contentType);
	}

	public function buildResponse($data)
	{
		return new Response($data + [
			'status' => Response::STATUS_OK,
		]);
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

	public function setTrackCode($trackCode)
	{
		$this->trackCode = $trackCode;
	}

	public function getTrackCode()
	{
		if ($this->trackCode === null)
		{
			throw new Main\SystemException('trackCode not set');
		}

		return (string)$this->trackCode;
	}

	public function setDeliveryServiceId($deliveryServiceId)
	{
		$this->deliveryServiceId = $deliveryServiceId;
	}

	public function getDeliveryServiceId()
	{
		if ($this->deliveryServiceId === null)
		{
			throw new Main\SystemException('deliveryServiceId not set');
		}

		return (string)$this->deliveryServiceId;
	}
}