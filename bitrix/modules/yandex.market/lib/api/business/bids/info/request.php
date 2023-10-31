<?php
namespace Yandex\Market\Api\Business\Bids\Info;

use Bitrix\Main;
use Yandex\Market\Api;

class Request extends Api\Partner\Reference\BusinessRequest
{
	protected $queryUrl = [];
	protected $queryBody = [];

	public function getPath()
	{
		return '/businesses/' . $this->getBusinessId() . '/bids/info.json?' . http_build_query($this->queryUrl);
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
		return !empty($this->queryBody) ? $this->queryBody : null;
	}

	public function setSkus(array $skus)
	{
		$this->queryBody['skus'] = $skus;
	}

	/** @param int $limit */
	public function setLimit($limit)
	{
		$this->queryUrl['limit'] = (int)$limit;
	}

	/** @param string $pageToken */
	public function setPageToken($pageToken)
	{
		$this->queryUrl['page_token'] = $pageToken;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}