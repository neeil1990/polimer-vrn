<?php

namespace Yandex\Market\Trading\Service\Common\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\HttpAction
{
	use Market\Reference\Concerns\HasLang;
	use TradingService\Common\Concerns\Action\HasMeaningfulProperties;
	use TradingService\Common\Concerns\Action\HasChangesTrait;
	use TradingService\Common\Concerns\Action\HasTasks;

	/** @var Request */
	protected $request;
	/** @var TradingEntity\Reference\Order */
	protected $order;
	/** @var array|null */
	protected $previousState;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::ORDER_STATUS;
	}

	public function process()
	{
		$this->loadOrder();
		$this->extendLogger();
		$this->fillOrder();

		if ($this->hasChanges())
		{
			$this->updateOrder();
		}

		$this->finalize();
		$this->saveData();
		$this->registerTasks();
		$this->collectSuccess();
	}

	protected function loadOrder()
	{
		$orderRegistry = $this->environment->getOrderRegistry();
		$externalId = $this->request->getOrder()->getId();
		$platform = $this->getPlatform();
		$orderId = $orderRegistry->search($externalId, $platform, false);

		if ($orderId === null)
		{
			$message = static::getLang('TRADING_ACTION_ORDER_STATUS_ORDER_NOT_EXISTS', [
				'#EXTERNAL_ID#' => $externalId,
			]);
			throw new Market\Exceptions\Trading\InvalidOperation($message);
		}

		$this->order = $orderRegistry->loadOrder($orderId);
	}

	protected function extendLogger()
	{
		$logger = $this->provider->getLogger();

		if (!($logger instanceof Market\Logger\Reference\Logger)) { return; }

		$logger->setContext('ENTITY_TYPE', TradingEntity\Registry::ENTITY_TYPE_ORDER);
		$logger->setContext('ENTITY_ID', $this->order->getAccountNumber());
	}

	protected function fillOrder()
	{
		$this->fillStatus();
		$this->fillProperties();
		$this->fillNotes();
	}

	protected function fillStatus()
	{
		$statuses = $this->getStatusIn();

		if ($this->isStateChanged())
		{
			$this->saveState();
		}

		$this->setStatus($statuses);
	}

	protected function finalize()
	{
		$this->finalizeStatus();
	}

	protected function finalizeStatus()
	{
		$this->commitState();

		if ($this->getChange('STATUS') !== null)
		{
			$this->logStatus();
		}
	}

	protected function getStatusIn()
	{
		$skippedMap = $this->getStatusSkippedSearchMap();
		$skippedActions = $this->statusToEnvironmentAction($skippedMap);
		$isChanged = $this->isStateChanged();

		if (!empty($skippedActions) || $isChanged)
		{
			$currentVariants = $this->getStatusInSearchVariants();
			$currentMap = array_fill_keys($currentVariants, $isChanged);
			$currentActions = $this->statusToEnvironmentAction($currentMap);

			$allActions = array_diff_key($skippedActions, $currentActions);
			$allActions += $currentActions;

			$result = array_values($allActions);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function getStatusSkippedSearchMap()
	{
		$statusService = $this->provider->getStatus();
		$current = $this->request->getOrder()->getStatus();
		$currentSubstatus = $this->request->getOrder()->getSubStatus();
		$currentOrder = $statusService->getStatusOrder($current);
		list($stored, $storedSubstatus) = $this->getStoredStatus();

		if (
			$currentOrder === null // unknown state
			|| $statusService->isCanceled($current, $currentSubstatus) // current status is cancel
			|| $statusService->isCanceled($stored, $storedSubstatus) // already sent cancel
		)
		{
			return [];
		}

		list($last, $lastSubstatus) = $this->getLastIncomingStatus();
		$lastOrder = $statusService->getStatusOrder($last);
		$storedOrder = $statusService->getStatusOrder($stored);
		$result = [];

		foreach ($statusService->getProcessOrder() as $intermediateStatus => $intermediateOrder)
		{
			if ($intermediateOrder < $lastOrder || $intermediateOrder > $currentOrder) { continue; }
			if ($intermediateOrder === $currentOrder && $intermediateStatus !== $current) { continue; }

			// status

			if ($intermediateOrder !== $lastOrder)
			{
				foreach ($this->makeStatusInSearchVariants($intermediateStatus) as $variant)
				{
					$result[$variant] = ($intermediateOrder > $storedOrder);
				}
			}

			// substatus

			if ($statusService->hasSubstatus($intermediateStatus))
			{
				$fromSubstatusOrder = ($last === $intermediateStatus ? $statusService->getSubStatusOrder($lastSubstatus) : -1);
				$toSubstatusOrder = ($current === $intermediateStatus ? $statusService->getSubStatusOrder($currentSubstatus) : INF);

				if ($fromSubstatusOrder !== null && $toSubstatusOrder !== null)
				{
					foreach ($statusService->getSubStatusProcessOrder() as $intermediateSubstatus => $intermediateSubstatusOrder)
					{
						if ($intermediateSubstatusOrder <= $fromSubstatusOrder || $intermediateSubstatusOrder > $toSubstatusOrder) { continue; }
						if ($intermediateSubstatusOrder === $toSubstatusOrder && $intermediateSubstatus !== $currentSubstatus) { continue; }
						if ($statusService->isCanceled($intermediateStatus, $intermediateSubstatus)) { continue; }

						foreach ($this->makeSubstatusInSearchVariants($intermediateStatus, $intermediateSubstatus) as $variant)
						{
							$result[$variant] = (
								$intermediateOrder > $storedOrder
								|| ($intermediateStatus === $stored && $intermediateSubstatusOrder > $statusService->getSubStatusOrder($storedSubstatus))
							);
						}
					}
				}
			}
		}

		return $result;
	}

	protected function getLastIncomingStatus()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();
		$stored = (string)Market\Trading\State\OrderData::getValue($serviceKey, $orderId, 'INCOMING_STATUS');

		return explode(':', $stored, 2);
	}

	protected function getStoredStatus()
	{
		$statusService = $this->provider->getStatus();
		$orderId = $this->request->getOrder()->getId();
		$stored = (string)$statusService->getStored($orderId);

		return explode(':', $stored, 2);
	}

	protected function getStatusInSearchVariants()
	{
		$status = $this->request->getOrder()->getStatus();
		$substatus = $this->request->getOrder()->getSubStatus();
		list($last) = $this->getLastIncomingStatus();

		if ($last !== $status)
		{
			$result = array_merge(
				$this->makeStatusInSearchVariants($status),
				$this->makeSubstatusInSearchVariants($status, $substatus)
			);
		}
		else
		{
			$result = $this->makeSubstatusInSearchVariants($status, $substatus);
		}

		return $result;
	}

	protected function makeStatusInSearchVariants($status)
	{
		return [
			$status,
		];
	}

	protected function makeSubstatusInSearchVariants($status, $substatus)
	{
		return [
			$status . '_' . $substatus,
		];
	}

	protected function statusToEnvironmentAction($searchMap)
	{
		$environmentStatus = $this->environment->getStatus();
		$index = 0;
		$result = [];
		$sort = [];

		foreach ($searchMap as $variant => $needApply)
		{
			$optionValue = (string)$this->statusConfiguredAction($variant);

			if ($optionValue === '') { continue; }
			if (!$needApply && !$environmentStatus->isStandalone($optionValue)) { continue; }

			$group = $environmentStatus->getGroup($optionValue);

			$result[$group] = $optionValue;
			$sort[$group] = ++$index;
		}

		uksort($result, static function($groupA, $groupB) use ($sort) {
			$sortA = $sort[$groupA];
			$sortB = $sort[$groupB];

			if ($sortA === $sortB) { return 0; }

			return ($sortA < $sortB ? -1 : 1);
		});

		return $result;
	}

	protected function statusConfiguredAction($status)
	{
		$options = $this->provider->getOptions();
		$result = $options->getStatusIn($status);

		if ($result === null && ($options->useSyncStatusOut() || $this->request->isDownload()))
		{
			$result = $options->getStatusOutRaw($status);
		}

		return $result;
	}

	protected function setStatus($statuses)
	{
		$environmentStatus = $this->environment->getStatus();
		$changesParts = [];

		foreach ($statuses as $status)
		{
			$meaningfulStatus = $environmentStatus->getMeaningful($status);
			$payload = $meaningfulStatus !== null ? $this->makeStatusPayload($meaningfulStatus) : null;
			$setResult = $this->order->setStatus($status, $payload);

			if (!$setResult->isSuccess())
			{
				$this->order->addMarker(
					implode(' ', $setResult->getErrorMessages()),
					$this->provider->getDictionary()->getErrorCode('ORDER_STATUS_' . $status)
				);
				$this->pushChange('MARKER', true);

				continue;
			}

			$setData = $setResult->getData();

			if (!empty($setData['CHANGES']))
			{
				$changesParts[] = $setData['CHANGES'];
			}
		}

		if (!empty($changesParts))
		{
			$this->pushChange('STATUS', array_merge(...$changesParts));
		}
	}

	protected function makeStatusPayload($meaningfulStatus)
	{
		if ($meaningfulStatus === Market\Data\Trading\MeaningfulStatus::CANCELED)
		{
			$result = $this->request->getOrder()->getSubStatus();
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function fillProperties()
	{
		$this->fillUtilProperties();
	}

	protected function fillUtilProperties()
	{
		$meaningfulValues = $this->request->getOrder()->getMeaningfulValues();

		$this->setMeaningfulPropertyValues($meaningfulValues);
	}

	protected function fillNotes()
	{
		$notes = $this->request->getOrder()->getNotes();

		if ($notes !== '')
		{
			$this->order->setNotes($notes);
		}
	}

	protected function updateOrder()
	{
		$updateResult = $this->order->update();

		Market\Result\Facade::handleException($updateResult);
	}

	protected function logStatus()
	{
		$logger = $this->provider->getLogger();
		$message = static::getLang('TRADING_ACTION_ORDER_STATUS_LOG', [
			'#STATUS#' => $this->request->getOrder()->getStatus(),
			'#SUBSTATUS#' => $this->request->getOrder()->getSubStatus(),
		]);

		$logger->info($message, [
			'AUDIT' => $this->getAudit(),
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->order->getAccountNumber(),
		]);
	}

	protected function isStateChanged()
	{
		$orderId = $this->request->getOrder()->getId();
		list($status, $substatus) = $this->getExternalStatus();

		return $this->provider->getStatus()->isChanged($orderId, $status, $substatus);
	}

	protected function saveState()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();
		$incomingStatus = $this->getExternalStatus();

		$this->previousState = explode(':', (string)$this->provider->getStatus()->getStored($orderId));
		Market\Trading\State\OrderStatus::setValue($serviceKey, $orderId, implode(':', $incomingStatus));
	}

	protected function commitState()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();

		Market\Trading\State\OrderStatus::commit($serviceKey, $orderId);
	}

	protected function getExternalStatus()
	{
		return [
			$this->request->getOrder()->getStatus(),
			$this->request->getOrder()->getSubStatus(),
		];
	}

	/** @deprecated */
	protected function releaseState()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();

		Market\Trading\State\OrderStatus::releaseValue($serviceKey, $orderId);
	}

	protected function saveData()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();
		$data = $this->makeData();

		Market\Trading\State\OrderData::setValues($serviceKey, $orderId, $data);
	}

	protected function makeData()
	{
		return $this->makeStatusData();
	}

	protected function makeStatusData()
	{
		return [
			'INCOMING_STATUS' => implode(':', [
				$this->request->getOrder()->getStatus(),
				$this->request->getOrder()->getSubStatus(),
			]),
		];
	}

	protected function collectSuccess()
	{
		if (Market\Config::getOption('ddos_guard', 'N') === 'Y')
		{
			$this->response->setField('ok', true);
		}
		else
		{
			$this->response->setRaw('');
		}
	}
}