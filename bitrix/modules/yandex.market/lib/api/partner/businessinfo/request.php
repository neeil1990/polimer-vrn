<?php

namespace Yandex\Market\Api\Partner\BusinessInfo;

use Yandex\Market;

/** @deprecated */
class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/business-info.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}