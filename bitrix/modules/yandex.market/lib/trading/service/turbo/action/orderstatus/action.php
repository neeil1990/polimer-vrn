<?php

namespace Yandex\Market\Trading\Service\Turbo\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\OrderStatus\Action
{
	/** @var TradingService\Turbo\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	public function __construct(TradingService\Turbo\Provider $provider, TradingEntity\Reference\Environment $environment, Main\HttpRequest $request, Main\Server $server)
	{
		parent::__construct($provider, $environment, $request, $server);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function makeStatusInSearchVariants($status)
	{
		$paymentType = $this->request->getOrder()->getPaymentType();
		$servicePaySystem = $this->provider->getPaySystem();
		$result = [
			$status,
		];

		if ($servicePaySystem->isPrepaid($paymentType))
		{
			array_unshift($result, $status . '_PREPAID');
		}

		return $result;
	}
}