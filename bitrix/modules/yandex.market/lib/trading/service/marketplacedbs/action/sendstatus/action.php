<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Marketplace\Action\SendStatus\Action
{
	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;

	public function __construct(TradingService\MarketplaceDbs\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	protected function checkHasStatus($orderId, $state)
	{
		try
		{
			/** @var Market\Trading\Service\MarketplaceDbs\Status $serviceStatuses */
			$serviceStatuses = $this->provider->getStatus();
			$externalOrder = $this->loadExternalOrder($orderId);
			$currentStatus = $externalOrder->getStatus();

			if ($state === TradingService\MarketplaceDbs\Status::STATUS_CANCELLED)
			{
				$result = $externalOrder->isCancelRequested() || $serviceStatuses->isCanceled($currentStatus);
			}
			else
			{
				$outgoingOrder = $serviceStatuses->getStatusOrder($state);
				$currentOrder = $serviceStatuses->getStatusOrder($currentStatus);

				$result = (
					$outgoingOrder !== null
					&& $outgoingOrder <= $currentOrder
				);
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
		$status = $state;
		$subStatus = null;

		if ($status === TradingService\MarketplaceDbs\Status::STATUS_CANCELLED)
		{
			$subStatus = $this->getCancelReason();
		}

		return [ $status, $subStatus ];
	}

	protected function getCancelReason()
	{
		return
			$this->getCancelReasonFromStatusOption()
			?: $this->getCancelReasonFromProperty()
			?: $this->getCancelReasonFromOrder()
			?: $this->getCancelReasonDefault();
	}

	protected function getCancelReasonFromStatusOption()
	{
		$requestStatus = $this->request->getStatus();
		$orderStatuses = $this->getOrder()->getStatuses();
		$result = null;

		if (!in_array($requestStatus, $orderStatuses, true))
		{
			$orderStatuses[] = $requestStatus;
		}

		foreach ($this->provider->getOptions()->getCancelStatusOptions() as $cancelStatusOption)
		{
			$optionStatus = $cancelStatusOption->getStatus();

			if (in_array($optionStatus, $orderStatuses, true))
			{
				$result = $cancelStatusOption->getCancelReason();
				break;
			}
		}

		return $result;
	}

	protected function getCancelReasonFromProperty()
	{
		$propertyId = (string)$this->provider->getOptions()->getProperty('REASON_CANCELED');
		$result = null;

		if ($propertyId === '') { return $result; }

		$propertyValue = $this->getOrder()->getPropertyValue($propertyId);

		return $this->provider->getCancelReason()->resolveVariant($propertyValue);
	}

	protected function getCancelReasonFromOrder()
	{
		$reason = $this->getOrder()->getReasonCanceled();

		return $this->provider->getCancelReason()->resolveVariant($reason);
	}

	protected function getCancelReasonDefault()
	{
		return $this->provider->getCancelReason()->getDefault();
	}
}