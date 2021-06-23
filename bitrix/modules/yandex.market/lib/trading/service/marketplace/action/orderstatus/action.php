<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\OrderStatus\Action
{
	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	public function __construct(TradingService\Marketplace\Provider $provider, TradingEntity\Reference\Environment $environment, Main\HttpRequest $request, Main\Server $server)
	{
		parent::__construct($provider, $environment, $request, $server);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function loadOrder()
	{
		try
		{
			parent::loadOrder();
		}
		catch (Main\InvalidOperationException $exception)
		{
			throw new Market\Exceptions\Trading\NotRecoverable($exception->getMessage(), $exception->getCode(), '', 0, $exception);
		}
	}

	protected function makeStatusPayload($meaningfulStatus)
	{
		if ($meaningfulStatus === Market\Data\Trading\MeaningfulStatus::PAYED)
		{
			$result = [
				'EXCLUDE' => [
					'SUBSIDY' => true,
				],
			];
		}
		else
		{
			$result = parent::makeStatusPayload($meaningfulStatus);
		}

		return $result;
	}
}