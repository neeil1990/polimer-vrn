<?php

namespace Yandex\Market\Api\Partner\Reference;

use Yandex\Market;

abstract class BusinessRequest extends Market\Api\Reference\RequestClientTokenized
{
	protected $businessId;

	public function getHost()
	{
		return 'api.partner.market.yandex.ru';
	}

	public function setBusinessId($businessId)
	{
		$this->businessId = $businessId;
	}

	public function getBusinessId()
	{
		Market\Reference\Assert::notNull($this->businessId, 'businessId');

		return (string)$this->businessId;
	}

	protected function createLocker()
	{
		$key = $this->getHost() . '_' . $this->getBusinessId();
		$limit = 2;

		return new Market\Api\Locker($key, $limit);
	}
}