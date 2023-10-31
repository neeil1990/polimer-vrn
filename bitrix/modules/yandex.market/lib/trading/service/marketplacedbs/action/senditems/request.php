<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendItems;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Marketplace\Action\SendItems\Request
{
	public function getReason()
	{
		return (string)$this->getRequiredField('reason');
	}
}