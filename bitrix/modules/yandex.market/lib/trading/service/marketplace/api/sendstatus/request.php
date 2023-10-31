<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendStatus;

use Yandex\Market;

class Request extends Market\Api\Partner\SendStatus\Request
{
	public function buildResponse($data)
	{
		return new Response($data);
	}
}