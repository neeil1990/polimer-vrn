<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\Order;

use Yandex\Market;

class Request extends Market\Api\Partner\Order\Request
{
	public function buildResponse($data)
	{
		return new Response($data);
	}
}