<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\SendStatus\Action
{
	/** @var TradingService\Marketplace\Provider */
	protected $provider;

	public function __construct(TradingService\Marketplace\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	protected function checkHasStatus($orderId, $state)
	{
		$result = false;

		try
		{
			$serviceStatuses = $this->provider->getStatus();
			$externalOrder = $this->loadExternalOrder($orderId);
			$orderStatus = $externalOrder->getStatus();
			$subStatus = $externalOrder->getSubStatus();

			switch ($state)
			{
				case TradingService\Marketplace\Status::STATE_SHOP_FAILED:
					$result = $externalOrder->isCancelRequested() || $serviceStatuses->isCanceled($orderStatus);
				break;

				case TradingService\Marketplace\Status::STATE_READY_TO_SHIP:
					$availableStates = [
						TradingService\Marketplace\Status::STATE_READY_TO_SHIP => true,
						TradingService\Marketplace\Status::STATE_SHIPPED => true,
					];

					$result =
						$serviceStatuses->isLeftProcessing($orderStatus)
						|| ($serviceStatuses->isProcessing($orderStatus) && isset($availableStates[$subStatus]));
				break;

				case TradingService\Marketplace\Status::STATE_SHIPPED:
					$result =
						$serviceStatuses->isLeftProcessing($orderStatus)
						|| ($serviceStatuses->isProcessing($orderStatus) && $subStatus === $state);
				break;
			}
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			$result = false;
		}

		return $result;
	}

	protected function getExternalStatus($state)
	{
		if ($state === TradingService\Marketplace\Status::STATE_SHOP_FAILED)
		{
			$status = TradingService\Marketplace\Status::STATUS_CANCELLED;
		}
		else
		{
			$status = TradingService\Marketplace\Status::STATUS_PROCESSING;
		}

		return [ $status, $state ];
	}
}