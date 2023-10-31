<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\VerifyEac;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\SendRequest
{
	public function getCode()
	{
		return (string)$this->getRequiredField('code');
	}
}