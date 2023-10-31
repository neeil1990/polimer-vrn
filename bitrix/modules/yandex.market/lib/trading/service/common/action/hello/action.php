<?php

namespace Yandex\Market\Trading\Service\Common\Action\Hello;

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
		$requestToken = (string)$this->request->getAuthToken();

		if ($requestToken === '')
		{
			throw new Market\Exceptions\Trading\AccessDenied('Auth token missing');
		}
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function process()
	{
		if ($this->request->getHello())
		{
			$this->response->setField('hello', true);
		}
	}
}