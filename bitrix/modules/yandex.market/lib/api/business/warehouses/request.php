<?php

namespace Yandex\Market\Api\Business\Warehouses;

use Yandex\Market;

class Request extends Market\Api\Partner\Reference\BusinessRequest
{
	public function getPath()
	{
		return '/businesses/' . $this->getBusinessId() . '/warehouses.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}