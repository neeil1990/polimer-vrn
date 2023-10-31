<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\VerifyEac;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $code;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/verifyEac.json',
			$this->getCampaignId(),
			$this->getOrderId()
		);
	}

	public function getQuery()
	{
		return [
			'code' => $this->getCode(),
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
		Market\Reference\Assert::notNull($this->orderId, 'orderId');

		return (string)$this->orderId;
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function getCode()
	{
		Market\Reference\Assert::notNull($this->code, 'code');

		return $this->code;
	}
}