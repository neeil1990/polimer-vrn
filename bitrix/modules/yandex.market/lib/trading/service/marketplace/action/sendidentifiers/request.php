<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendIdentifiers;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\SendRequest
{
	public function getItems()
	{
		return (array)$this->getRequiredField('items');
	}
}