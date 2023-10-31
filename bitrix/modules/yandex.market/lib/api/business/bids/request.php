<?php
namespace Yandex\Market\Api\Business\Bids;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Reference\Assert;

class Request extends Api\Partner\Reference\BusinessRequest
{
	protected $bids;

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_PUT;
	}

	public function getPath()
	{
		return '/businesses/' . $this->getBusinessId() . '/bids.json';
	}

	public function getQuery()
	{
		Assert::notNull($this->bids, 'bids');

		return [
			'bids' => $this->bids,
		];
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function setBids(array $bids)
	{
		$this->bids = $bids;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}