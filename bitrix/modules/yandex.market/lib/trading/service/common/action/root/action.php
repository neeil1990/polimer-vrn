<?php

namespace Yandex\Market\Trading\Service\Common\Action\Root;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\HttpAction
{
	/** @var Request */
	protected $request;

	public function checkAuthorization()
	{
		// nothing
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function process()
	{
		$this->response->setRaw('OK');
	}
}