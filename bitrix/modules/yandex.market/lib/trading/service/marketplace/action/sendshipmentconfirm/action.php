<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendShipmentConfirm;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 * @property Request $request
 */
class Action extends TradingService\Reference\Action\DataAction
	implements
		TradingService\Reference\Action\HasActivity
{
	use Market\Reference\Concerns\HasMessage;

	protected $orderMap;
	protected $orders;
	protected $changedOrders = [];

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::SEND_SHIPMENT_CONFIRM;
	}

	public function getActivity()
	{
		return new Activity($this->provider, $this->environment);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		$this->setStatus();
		$this->sendConfirm();
		$this->logConfirm();
		$this->saveStatus();
	}

	protected function setStatus()
	{
		$status = (string)$this->provider->getOptions()->getShipmentStatus('CONFIRM');

		if ($status === '') { return; }

		foreach ($this->getOrders() as $orderId => $order)
		{
			$setResult = $order->setStatus($status);

			if (!$setResult->isSuccess())
			{
				$errorMessage = implode(', ', $setResult->getErrorMessages());

				throw new Main\SystemException(self::getMessage('SET_STATUS_ERROR', [
					'#ORDER_ID#' => $order->getAccountNumber(),
					'#EXTERNAL_ID#' => $this->getOrderExternalId($orderId),
					'#MESSAGE#' => $errorMessage,
				], $errorMessage));
			}

			$setData = $setResult->getData();

			if (!empty($setData['CHANGES']))
			{
				$this->changedOrders[] = $orderId;
			}
		}
	}

	protected function sendConfirm()
	{
		$request = $this->buildRequest();
		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$message = self::getMessage('RESPONSE_FAIL', [
				'#MESSAGE#' => implode(PHP_EOL, $sendResult->getErrorMessages())
			]);
			throw new Market\Exceptions\Api\Request($message);
		}
	}

	protected function buildRequest()
	{
		$result = new TradingService\Marketplace\Api\ShipmentConfirm\Request();
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();

		$result->setLogger($logger);
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setCampaignId($options->getCampaignId());
		$result->setShipmentId($this->request->getShipmentId());
		$result->setExternalShipmentId($this->request->getExternalShipmentId());
		$result->setOrderIds($this->request->getOrderIds());

		return $result;
	}

	protected function logConfirm()
	{
		$logger = $this->provider->getLogger();
		$orderIds = $this->request->getOrderIds();
		$orderMap = $this->findOrderNumbers($orderIds);
		$orderMap += array_fill_keys($orderIds, null);

		foreach ($orderMap as $orderId => $orderNumber)
		{
			$message = self::getMessage('SEND_LOG', [
				'#SHIPMENT_ID#' => $this->request->getShipmentId(),
			]);

			$logger->info($message, [
				'AUDIT' => $this->getAudit(),
				'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
				'ENTITY_ID' => $orderNumber ?: $orderId,
			]);
		}
	}

	protected function getOrderNumbers(array $orderIds)
	{
		if (!$this->isLoadedOrders()) { return $this->findOrderNumbers($orderIds); }

		$result = [];

		foreach ($this->getOrders() as $order)
		{
			$externalId = $this->getOrderExternalId($order->getId());

			if (!$externalId) { continue; }

			$result[$externalId] = $order->getAccountNumber() ?: $order->getId();
		}

		return $result;
	}

	protected function findOrderNumbers(array $orderIds)
	{
		return $this->environment->getOrderRegistry()->searchList($orderIds, $this->getPlatform());
	}

	protected function saveStatus()
	{
		if (empty($this->changedOrders)) { return; }

		$orders = $this->getOrders();

		foreach ($this->changedOrders as $orderId)
		{
			$order = $orders[$orderId];

			$updateResult = $order->update();

			if (!$updateResult->isSuccess())
			{
				$errorMessage = implode(', ', $updateResult->getErrorMessages());

				throw new Main\SystemException(self::getMessage('SAVE_ORDER_ERROR', [
					'#ORDER_ID#' => $order->getAccountNumber(),
					'#EXTERNAL_ID#' => $this->getOrderExternalId($orderId),
					'#MESSAGE#' => $errorMessage,
				], $errorMessage));
			}
		}
	}

	protected function isLoadedOrders()
	{
		return $this->orders !== null;
	}

	protected function getOrders()
	{
		if ($this->orders === null)
		{
			$this->orders = $this->loadOrders();
		}

		return $this->orders;
	}

	protected function loadOrders()
	{
		$orderMap = $this->getOrderMap();

		return $this->environment->getOrderRegistry()->loadOrderList($orderMap);
	}

	protected function getOrderMap()
	{
		if ($this->orderMap === null)
		{
			$this->orderMap = $this->loadOrderMap();
		}

		return $this->orderMap;
	}

	protected function getOrderExternalId($orderId)
	{
		$orderMap = $this->getOrderMap();

		/** @noinspection TypeUnsafeArraySearchInspection */
		return array_search($orderId, $orderMap);
	}

	protected function loadOrderMap()
	{
		return $this->environment->getOrderRegistry()->searchList(
			$this->request->getOrderIds(),
			$this->getPlatform(),
			false
		);
	}
}