<?php

namespace Yandex\Market\Trading\Service\Turbo\Action\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\Cart\Action
{
	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function fillOrder()
	{
		$this->fillXmlId();
		$this->fillProfile();
		$this->fillBasket();
	}
}