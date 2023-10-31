<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class HttpAction extends AbstractAction
{
	public function __construct(TradingService\Reference\Provider $provider, TradingEntity\Reference\Environment $environment, Main\HttpRequest $request, Main\Server $server)
	{
		parent::__construct($provider, $environment);
		$this->request = $this->createRequest($request, $server);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new HttpRequest($request, $server);
	}

	abstract public function checkAuthorization();
}