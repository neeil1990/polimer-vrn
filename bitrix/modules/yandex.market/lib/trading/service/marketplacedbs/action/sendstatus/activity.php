<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendStatus;

use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Marketplace\Action\SendStatus\Activity
{
	public function getActivities()
	{
		return array_merge(parent::getActivities(), [
			'status' => new StatusActivity($this->provider, $this->environment),
		]);
	}
}