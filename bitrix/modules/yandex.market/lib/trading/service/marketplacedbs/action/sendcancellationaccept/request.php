<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendCancellationAccept;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\SendRequest
{
	public function isAccepted()
	{
		return (bool)$this->getRequiredField('accepted');
	}

	public function getReason()
	{
		return (string)$this->getRequiredField('reason');
	}
}