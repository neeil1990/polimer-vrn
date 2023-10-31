<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Marketplace\Action\SendStatus\Action
{
	use TradingService\Common\Concerns\Action\HasChangesTrait;
	use TradingService\Common\Concerns\Action\HasMeaningfulProperties;
	use TradingService\MarketplaceDbs\Concerns\Action\HasDeliveryDates;

	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	public function __construct(TradingService\MarketplaceDbs\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function getActivity()
	{
		return new Activity($this->provider, $this->environment);
	}

	protected function checkHasStatus($orderId, $state)
	{
		try
		{
			/** @var Market\Trading\Service\MarketplaceDbs\Status $serviceStatuses */
			$serviceStatuses = $this->provider->getStatus();
			$externalOrder = $this->getExternalOrder();
			$currentStatus = $externalOrder->getStatus();

			if ($state === TradingService\MarketplaceDbs\Status::STATUS_CANCELLED)
			{
				$result = $externalOrder->isCancelRequested() || $serviceStatuses->isCanceled($currentStatus);
			}
			else if ($serviceStatuses->isCanceled($currentStatus))
			{
				$result = false;
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

	protected function getExternalPayload($status, $subStatus)
	{
		$result = [];

		if (
			$status === TradingService\MarketplaceDbs\Status::STATUS_PICKUP
			|| $this->request->hasRealDeliveryDate()
			|| (
				$status === TradingService\MarketplaceDbs\Status::STATUS_DELIVERED
				&& $this->getCurrentStatus() !== TradingService\MarketplaceDbs\Status::STATUS_PICKUP
			)
		)
		{
			$result['realDeliveryDate'] = $this->getRealDeliveryDate();
		}

		return $result;
	}

	protected function getCurrentStatus()
	{
		$result = $this->getStoredOrderStatus();

		if ($result === null)
		{
			$result = $this->getExternalOrderStatus();
		}

		return $result;
	}

	protected function getStoredOrderStatus()
	{
		$orderId = $this->request->getOrderId();
		$stored = (string)$this->provider->getStatus()->getStored($orderId);
		$result = null;

		if ($stored !== '')
		{
			list($result) = explode(':', $stored, 2);
		}

		return $result;
	}

	protected function getExternalOrderStatus()
	{
		return $this->getExternalOrder()->getStatus();
	}

	protected function getRealDeliveryDate()
	{
		$fixed = $this->getRealDeliveryDateFromRequest();

		if ($fixed) { return $fixed; }

		$predicted =
			$this->getRealDeliveryDateFromTargetProperty()
			?: $this->getRealDeliveryDateFromStored()
			?: $this->getRealDeliveryDateFromPeriodProperty()
			?: $this->getRealDeliveryDateFromExternalOrder()
			?: new Main\Type\Date();

		$marketDate = new Main\Type\DateTime();
		$marketDate->setTimeZone(new \DateTimeZone('Europe/Moscow'));

		return Market\Data\Date::min($predicted, $marketDate);
	}

	protected function getRealDeliveryDateFromRequest()
	{
		return $this->request->getRealDeliveryDate();
	}

	protected function getRealDeliveryDateFromTargetProperty()
	{
		return $this->getDateFromProperties([
			'DELIVERY_REAL_DATE',
		]);
	}

	protected function getRealDeliveryDateFromStored()
	{
		return $this->getDateFromStored([
			'REAL_DELIVERY_DATE',
			'DELIVERY_DATE',
		]);
	}

	protected function getRealDeliveryDateFromPeriodProperty()
	{
		return $this->getDateFromProperties([
			'DELIVERY_DATE_FROM',
			'DELIVERY_DATE_TO',
		]);
	}

	protected function getRealDeliveryDateFromExternalOrder()
	{
		$order = $this->getExternalOrder();

		if (!$order->hasDelivery()) { return null; }

		$dates = $order->getDelivery()->getDates();

		if ($dates === null) { return null; }

		return $dates->getRealDeliveryDate() ?: $dates->getFromDate();
	}

	protected function getDateFromProperties(array $types)
	{
		$result = null;

		foreach ($types as $type)
		{
			$propertyId = (string)$this->provider->getOptions()->getProperty($type);

			if ($propertyId === '') { continue; }

			$order = $this->getOrder();
			$siteId = $order->getSiteId();
			$propertyValue = $order->getPropertyValue($propertyId);
			$propertyValue = is_array($propertyValue) ? reset($propertyValue) : $propertyValue;

			$date = Market\Data\Date::parse($propertyValue, $siteId);

			if ($date !== null)
			{
				$result = $date;
				break;
			}
		}

		return $result;
	}

	protected function getDateFromStored(array $types)
	{
		$orderId = $this->request->getOrderId();
		$uniqueKey = $this->provider->getUniqueKey();
		$result = null;

		foreach ($types as $type)
		{
			$value = Market\Trading\State\OrderData::getValue($uniqueKey, $orderId, $type);
			$value = trim($value);

			if ($value === '') { continue; }

			$result = new Main\Type\Date($value, Market\Data\Date::FORMAT_DEFAULT_SHORT);
			break;
		}

		return $result;
	}

	protected function getCancelReason()
	{
		return
			$this->getCancelReasonFromRequest()
			?: $this->getCancelReasonFromStatusOption()
			?: $this->getCancelReasonFromProperty()
			?: $this->getCancelReasonFromOrder()
			?: $this->getCancelReasonDefault();
	}

	protected function getCancelReasonFromRequest()
	{
		return $this->request->getCancelReason();
	}

	protected function getCancelReasonFromStatusOption()
	{
		$requestStatus = $this->request->getStatus();
		$orderStatuses = $this->getOrder()->getStatuses();
		$result = null;

		if ($requestStatus !== null && !in_array($requestStatus, $orderStatuses, true))
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

	protected function extractSendResultSkipErrorCurrentStatus(Main\Result $sendResult, $state)
	{
		list($status, $subStatus) = $this->getExternalStatus($state);
		$result = null;

		foreach ($sendResult->getErrors() as $error)
		{
			$message = $error->getMessage();
			$regexp =
				'#Order \d+ with status'
				. ' (?<status>\w+)( and substatus (?<substatus>\w+))?'
				. ' is not allowed for status'
				. ' (?<requestStatus>\w+)( and substatus (?<requestSubstatus>\w+))?#';

			if (!preg_match($regexp, $message, $matches)) { continue; }
			if ($matches['requestStatus'] !== $status) { continue; }
			if ($subStatus !== null && $matches['requestSubstatus'] !== $subStatus) { continue; }

			$result = [
				$matches['status'],
				isset($matches['substatus']) ? $matches['substatus'] : null,
			];
			break;
		}

		return $result;
	}

	protected function getSubmitStack($fromStatus, $toStatus)
	{
		$disabled = [
			TradingService\MarketplaceDbs\Status::STATUS_CANCELLED => true,
		];

		if ($fromStatus[0] === null || $toStatus[0] === null) { return null; }
		if (isset($disabled[$toStatus[0]])) { return null; }

		$statusProvider = $this->provider->getStatus();
		$statusOrder = $statusProvider->getProcessOrder();

		if (!isset($statusOrder[$fromStatus[0]], $statusOrder[$toStatus[0]])) { return null; }

		$result = [];
		$fromFound = false;
		$skip = $disabled + [
			TradingService\MarketplaceDbs\Status::STATUS_PICKUP => true,
		];
		$skip = array_diff_key($skip, [ $toStatus[0] => true ]);

		foreach ($statusOrder as $processStatus => $processOrder)
		{
			if ($processStatus === $fromStatus[0])
			{
				$fromFound = true;
			}
			else if ($fromFound && !isset($skip[$processStatus]))
			{
				$result[$processOrder] = $processStatus;

				if ($processStatus === $toStatus[0]) { break; }
			}
		}

		return $result;
	}

	protected function finalize($orderId, $state)
	{
		$this->resetChanges();
		$this->fillDeliveryDatesProperties($this->externalOrder);
		$this->finalizePhone($state);

		if ($this->hasChanges())
		{
			$this->updateOrder();
		}
	}

	protected function finalizePhone($state)
	{
		$statusService = $this->provider->getStatus();
		$isFinal = ($statusService->isCanceled($state) || $statusService->isOrderDelivered($state));

		if (!$isFinal) { return; }

		$this->setMeaningfulPropertyValues([
			'PHONE' => '',
		]);
	}

	protected function makeData()
	{
		return $this->makeDeliveryDatesData($this->externalOrder);
	}
}