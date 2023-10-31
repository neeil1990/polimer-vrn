<?php

namespace Yandex\Market\Api\Partner\SendStatus;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $status;
	protected $subStatus;
	protected $payload;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/orders/' . $this->getOrderId() .'/status.json';
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_PUT;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function getQuery()
	{
		$subStatus = $this->getSubStatus();
		$payload = $this->getPayload();
		$result = [
			'order' => [
				'status' => $this->getStatus(),
			]
		];

		if ($subStatus !== null)
		{
			$result['order']['substatus'] = $this->getSubStatus();
		}

		if (!empty($payload))
		{
			$result['order'] += $this->convertPayload($payload);
		}

		return $result;
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

	public function setStatus($status)
	{
		$this->status = (string)$status;
	}

	public function getStatus()
	{
		if ($this->status === null)
		{
			throw new Main\SystemException('status not set');
		}

		return $this->status;
	}

	public function setSubStatus($subStatus)
	{
		$this->subStatus = $subStatus;
	}

	public function getSubStatus()
	{
		return $this->subStatus;
	}

	public function setPayload($payload)
	{
		$this->payload = $payload;
	}

	public function getPayload()
	{
		return $this->payload;
	}

	protected function convertPayload($payload)
	{
		return [];
	}
}