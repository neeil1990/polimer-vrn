<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendPrices\Business;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\BusinessRequest
{
	protected $offers;

	public function getPath()
	{
		return '/businesses/' . $this->getBusinessId() . '/offer-prices/updates.json';
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
			'offers' => $this->getOffers(),
		];
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function getOffers()
	{
		Market\Reference\Assert::notNull($this->offers, 'offers');

		return $this->offers;
	}

	public function setOffers($offers)
	{
		$this->offers = $offers;
	}
}