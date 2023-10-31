<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\SendDeliveryStorageLimit;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $newDate;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/delivery/storage-limit.json',
			$this->getCampaignId(),
			$this->getOrderId()
		);
	}

	public function getQuery()
	{
		return [
			'newDate' => Market\Data\Date::convertForService(
				$this->getNewDate(),
				Market\Data\Date::FORMAT_DEFAULT_SHORT
			),
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

	public function setNewDate(Main\Type\Date $accepted)
	{
		$this->newDate = $accepted;
	}

	public function getNewDate()
	{
		Market\Reference\Assert::notNull($this->newDate, 'newDate');

		return $this->newDate;
	}
}