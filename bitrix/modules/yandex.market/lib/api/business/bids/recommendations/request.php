<?php
namespace Yandex\Market\Api\Business\Bids\Recommendations;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Reference\Assert;

class Request extends Api\Partner\Reference\BusinessRequest
{
	protected $query = [];

	public function getPath()
	{
		return '/businesses/' . $this->getBusinessId() . '/bids/recommendations.json';
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function getQuery()
	{
		Assert::notEmpty($this->query['skus'], 'query[skus]');

		return $this->query;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function setSkus(array $skus)
	{
		$this->query['skus'] = $skus;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}