<?php

namespace Yandex\Market\Trading\Service\Common\Action;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class HttpAction extends TradingService\Reference\Action\HttpAction
{
	/** @var TradingService\Common\Provider */
	protected $provider;
	/** @var HttpRequest */
	protected $request;

	public function __construct(TradingService\Common\Provider $provider, TradingEntity\Reference\Environment $environment, Main\HttpRequest $request, Main\Server $server)
	{
		parent::__construct($provider, $environment, $request, $server);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new HttpRequest($request, $server);
	}

	public function checkAuthorization()
	{
		$requestToken = $this->request->getAuthToken();
		$optionsTokens = $this->provider->getOptions()->getYandexTokens();

		if (!in_array($requestToken, $optionsTokens, true))
		{
			throw new Market\Exceptions\Trading\AccessDenied('Auth token does not match');
		}
	}
}