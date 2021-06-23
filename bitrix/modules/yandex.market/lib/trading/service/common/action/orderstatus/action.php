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

	/** @var Request */
	protected $request;
	/** @var TradingEntity\Reference\Order */
	protected $order;
	protected $changes = [];

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
			$this->finalizeStatus();
		}

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
	}

	protected function fillStatus()
	{
		$statuses = $this->getStatusIn();

		if (!empty($statuses) && $this->isStateChanged())
		{
			$this->saveState();
			$this->setStatus($statuses);
			$this->pushChange('STATUS', $statuses);
		}
	}

	protected function finalizeStatus()
	{
		$statusChange = $this->getChange('STATUS');

		if ($statusChange !== null)
		{
			$this->commitState();
			$this->logStatus();
		}
	}

	protected function getStatusIn()
	{
		$result = [];
		$options = $this->provider->getOptions();

		foreach ($this->getStatusInSearchVariants() as $variant)
		{
			$optionValue = (string)$options->getStatusIn($variant);

			if ($optionValue !== '')
			{
				$result[] = $optionValue;
			}
		}

		return $result;
	}

	protected function getStatusInSearchVariants()
	{
		return [
			$this->request->getOrder()->getStatus(),
		];
	}

	protected function setStatus($statuses)
	{
		$environmentStatus = $this->environment->getStatus();

		foreach ($statuses as $status)
		{
			$meaningfulStatus = $environmentStatus->getMeaningful($status);
			$payload = $meaningfulStatus !== null ? $this->makeStatusPayload($meaningfulStatus) : null;
			$statusResult = $this->order->setStatus($status, $payload);

			Market\Result\Facade::handleException($statusResult);
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

	protected function pushChange($key, $value)
	{
		$this->changes[$key] = $value;
	}

	protected function hasChanges()
	{
		return !empty($this->changes);
	}

	protected function getChange($key)
	{
		return isset($this->changes[$key]) ? $this->changes[$key] : null;
	}
}