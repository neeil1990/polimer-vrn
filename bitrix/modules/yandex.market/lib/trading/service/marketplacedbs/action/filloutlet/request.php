<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\FillOutlet;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\TaskRequest
{
	public function getOutletCode()
	{
		return $this->getRequiredField('outletCode');
	}
}