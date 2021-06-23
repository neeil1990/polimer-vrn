<?php

namespace Yandex\Market\Trading\Service\Common\Action\Hello;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\HttpRequest
{
	/**
	 * @return bool
	 */
	public function getHello()
	{
		return $this->getRequiredField('hello');
	}
}