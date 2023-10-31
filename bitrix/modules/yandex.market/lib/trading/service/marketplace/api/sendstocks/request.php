<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendStocks;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $skus;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/offers/stocks.json';
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
		return [
			'skus' => $this->getSkus(),
		];
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

	public function getSkus()
	{
		Market\Reference\Assert::notNull($this->skus, 'skus');

		return $this->skus;
	}

	public function setSkus($skus)
	{
		$this->skus = $skus;
	}
}