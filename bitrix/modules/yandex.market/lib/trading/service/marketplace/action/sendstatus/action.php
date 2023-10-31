<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\SendStatus\Action
	implements TradingService\Reference\Action\HasActivity
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

	public function getActivity()
	{
		return new Activity($this->provider, $this->environment);
	}

	protected function isChangedOrderStatus($orderId, $state)
	{
		$result = parent::isChangedOrderStatus($orderId, $state);

		if ($result === false) { return false; }

		if ($state === TradingService\Marketplace\Status::STATE_SHOP_FAILED)
		{
			$stored = (string)$this->provider->getStatus()->getStored($orderId);
			list(, $storedSubStatus) = explode(':', $stored, 2);

			$result = ($storedSubStatus !== $state);
		}
		else if ($state === TradingService\Marketplace\Status::STATE_SHIPPED)
		{
			$result = false; // prevent submit deprecated substatus
		}

		return $result;
	}

	protected function checkHasStatus($orderId, $state)
	{
		$result = false;

		try
		{
			$serviceStatuses = $this->provider->getStatus();
			$externalOrder = $this->getExternalOrder();
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
						|| ($serviceStatuses->isProcessing($orderStatus) && isset($availableStates[$subStatus]))
						|| ($serviceStatuses->isProcessing($orderStatus) && $serviceStatuses->getSubStatusOrder($subStatus) === null);
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

	protected function fixStatus(Main\Result $sendResult, $orderId, $state)
	{
		if ($this->request->getImmediate()) { return false; }

		return $this->fixStatusBySubmitStack($sendResult, $orderId, $state);
	}

	protected function fixStatusBySubmitStack(Main\Result $sendResult, $orderId, $state)
	{
		$currentStatus = $this->extractSendResultSkipErrorCurrentStatus($sendResult, $state);

		if ($currentStatus === null) { return false; }

		$targetStatus = $this->getExternalStatus($state);
		$submitStack = $this->getSubmitStack($currentStatus, $targetStatus);

		if ($submitStack === null || count($submitStack) === 1) { return false; }

		foreach ($submitStack as $stackState)
		{
			$stackResult = $this->sendStatus($orderId, $stackState);

			if (!$stackResult->isSuccess())
			{
				return false;
			}
		}

		return true;
	}

	protected function extractSendResultSkipErrorCurrentStatus(Main\Result $sendResult, $state)
	{
		list(, $subStatus) = $this->getExternalStatus($state);
		$result = null;

		foreach ($sendResult->getErrors() as $error)
		{
			$message = $error->getMessage();
			$regexp =
				'#No permission to set'
				. '(?: status (?<requestStatus>\w+) and)?'
				. ' substatus (?<requestSubstatus>\w+) for order \d+'
				. ' with status (?<status>\w+) and substatus (?<substatus>\w+)'
				.'(?: by reason: (?<reason>.+)$)?#';

			if (!preg_match($regexp, $message, $matches)) { continue; }
			if ($subStatus !== null && $matches['requestSubstatus'] !== $subStatus) { continue; }
			if (isset($matches['reason']) && trim($matches['reason']) !== '') { continue; } // something not filled

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
			TradingService\Marketplace\Status::STATE_SHOP_FAILED => true,
		];

		if ($fromStatus[0] !== TradingService\Marketplace\Status::STATUS_PROCESSING) { return null; }
		if ($fromStatus[0] !== $toStatus[0]) { return null; }
		if ($fromStatus[1] === null || $toStatus[1] === null) { return null; }
		if (isset($disabled[$toStatus[1]])) { return null; }

		$statusProvider = $this->provider->getStatus();
		$substatusOrder = $statusProvider->getSubStatusProcessOrder();

		if (!isset($substatusOrder[$fromStatus[1]], $substatusOrder[$toStatus[1]])) { return null; }

		$result = [];
		$fromFound = false;

		foreach ($substatusOrder as $processSubstatus => $processOrder)
		{
			if ($processSubstatus === $fromStatus[1])
			{
				$fromFound = true;
			}
			else if ($fromFound && !isset($disabled[$processSubstatus]))
			{
				$result[$processOrder] = $processSubstatus;

				if ($processSubstatus === $toStatus[1]) { break; }
			}
		}

		return $result;
	}

	protected function saveState($orderId, $state)
	{
		if ($state !== TradingService\Marketplace\Status::STATE_SHOP_FAILED)
		{
			parent::saveState($orderId, $state);
			return;
		}

		$serviceKey = $this->provider->getUniqueKey();
		$fullStatus = [ TradingService\Marketplace\Status::STATUS_PROCESSING, $state ];

		Market\Trading\State\OrderStatus::setValue($serviceKey, $orderId, implode(':', $fullStatus));
		Market\Trading\State\OrderStatus::commit($serviceKey, $orderId);
	}
}