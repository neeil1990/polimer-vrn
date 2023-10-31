<?php

namespace Yandex\Market\Api\Delivery\Services;

use Yandex\Market;

class Request extends Market\Api\Reference\RequestClientTokenized
{
	protected $orderId;

	public function getHost()
	{
		return 'api.partner.market.yandex.ru';
	}

	public function getPath()
	{
		return '/v2/delivery/services.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}