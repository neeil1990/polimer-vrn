<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\SendCancellationAccept;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $accepted;
	protected $reason;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/cancellation/accept.json',
			$this->getCampaignId(),
			$this->getOrderId()
		);
	}

	public function getQuery()
	{
		$result = [
			'accepted' => $this->getAccepted(),
		];

		if (!$result['accepted'])
		{
			$result['reason'] = $this->getReason();
		}

		return $result;
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_PUT;
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

	public function setOrderId($trackCode)
	{
		$this->orderId = $trackCode;
	}

	public function getOrderId()
	{
		Market\Reference\Assert::notNull($this->orderId, 'orderId');

		return (string)$this->orderId;
	}

	public function setAccepted($accepted)
	{
		$this->accepted = $accepted;
	}

	public function getAccepted()
	{
		Market\Reference\Assert::notNull($this->accepted, 'accepted');

		return (bool)$this->accepted;
	}

	public function setReason($reason)
	{
		$this->reason = $reason;
	}

	public function getReason()
	{
		Market\Reference\Assert::notNull($this->reason, 'reason');

		return (string)$this->reason;
	}
}