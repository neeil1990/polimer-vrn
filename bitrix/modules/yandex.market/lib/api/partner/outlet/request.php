<?php

namespace Yandex\Market\Api\Partner\Outlet;

use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $outletId;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/outlets/' . $this->getOutletId() . '.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function getOutletId()
	{
		Market\Reference\Assert::notNull($this->outletId, 'outletId');

		return $this->outletId;
	}

	public function setOutletId($outletId)
	{
		$this->outletId = $outletId;
	}
}
