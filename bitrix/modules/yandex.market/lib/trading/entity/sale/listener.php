<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Listener extends Market\Trading\Entity\Reference\Listener
{
	const STATE_PROCESSING = 1;
	const STATE_SAVING = 2;

	protected static $statusEventQueue = [];
	protected static $eventOrderList = [];
	protected static $eventOrderState = [];
	protected static $tradingSetupCache = [];
	protected static $handledSaleOrderBeforeSaved = false;

	public static function onBeforeSaleOrderSetField(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');
		$result = null;

		if ($order instanceof Sale\Order && $order->getField($name) !== $value && static::isAdminRequest())
		{
			$submitStatus = null;

			if ($name === 'STATUS_ID')
			{
				$submitStatus = $value;
			}
			else if ($name === 'CANCELED' && $value === 'Y')
			{
				static::handleSaleOrderBeforeSaved();
			}

			if ($submitStatus !== null)
			{
				$result = static::sendStatusForEventBefore($order, $submitStatus);
			}
		}

		return $result;
	}

	public static function onBeforeSaleShipmentSetField(Main\Event $event)
	{
		$shipment = $event->getParameter('ENTITY');
		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');
		$result = null;

		if ($shipment instanceof Sale\Shipment && $shipment->getField($name) !== $value && static::isAdminRequest())
		{
			$submitStatus = null;

			if (
				($name === Status::STATUS_ALLOW_DELIVERY || $name === Status::STATUS_DEDUCTED)
				&& $value === 'Y'
				&& static::isShipmentSiblingsFilled($shipment, $name, $value)
			)
			{
				$submitStatus = $name;
			}

			if ($submitStatus !== null && ($order = static::getShipmentOrder($shipment)))
			{
				$result = static::sendStatusForEventBefore($order, $submitStatus);
			}
		}

		return $result;
	}

	public static function onBeforeSalePaymentSetField(Main\Event $event)
	{
		$payment = $event->getParameter('ENTITY');
		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');
		$result = null;

		if ($payment instanceof Sale\Payment && $payment->getField($name) !== $value && static::isAdminRequest())
		{
			$submitStatus = null;

			if (
				$name === 'PAID' && $value === 'Y'
				&& static::isPaymentOrderPaid($payment)
			)
			{
				$submitStatus = Status::STATUS_PAYED;
			}

			if ($submitStatus !== null && ($order = static::getPaymentOrder($payment)))
			{
				$result = static::sendStatusForEventBefore($order, $submitStatus);
			}
		}

		return $result;
	}

	public static function onSaleStatusOrderChange(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		$newStatus = $event->getParameter('VALUE');
		$oldStatus = $event->getParameter('OLD_VALUE');

		if ($newStatus !== $oldStatus && $order instanceof Sale\Order)
		{
			static::sendStatusForEventAfter($order, $newStatus);
		}
	}

	public static function onSaleOrderCanceled(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');

		if ($order instanceof Sale\Order && $order->isCanceled())
		{
			static::sendStatusForEventAfter($order, Status::STATUS_CANCELED);
		}
	}

	public static function onShipmentAllowDelivery(Main\Event $event)
	{
		$shipment = $event->getParameter('ENTITY');

		if ($shipment instanceof Sale\Shipment)
		{
			/** @var Sale\ShipmentCollection $shipmentCollection */
			$shipmentCollection = $shipment->getCollection();
			$order = $shipmentCollection->getOrder();

			if ($shipmentCollection->isAllowDelivery())
			{
				static::sendStatusForEventAfter($order, Status::STATUS_ALLOW_DELIVERY);
			}
		}
	}

	public static function onSaleOrderPaid(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');

		if ($order instanceof Sale\Order && $order->isPaid())
		{
			static::sendStatusForEventAfter($order, Status::STATUS_PAYED);
		}
	}

	public static function onShipmentDeducted(Main\Event $event)
	{
		$shipment = $event->getParameter('ENTITY');

		if ($shipment instanceof Sale\Shipment)
		{
			/** @var Sale\ShipmentCollection $shipmentCollection */
			$shipmentCollection = $shipment->getCollection();
			$order = $shipmentCollection->getOrder();

			if ($shipmentCollection->isShipped())
			{
				static::sendStatusForEventAfter($order, Status::STATUS_DEDUCTED);
			}
		}
	}

	public static function onSaleOrderBeforeSaved(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		$result = null;

		if ($order instanceof Sale\Order && $order->isCanceled())
		{
			$result = static::sendStatusForEventBefore($order, Status::STATUS_CANCELED);
		}

		return $result;
	}

	protected static function handleSaleOrderBeforeSaved()
	{
		if (static::$handledSaleOrderBeforeSaved) { return; }

		$eventManager = Main\EventManager::getInstance();
		$eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', [static::class, 'onSaleOrderBeforeSaved']);

		static::$handledSaleOrderBeforeSaved = true;
	}

	protected static function sendStatusForEventBefore(Sale\Order $order, $status)
	{
		$result = null;
		$orderId = $order->getId();
		$siteId = $order->getSiteId();

		if ($orderId > 0)
		{
			static::pushStatusQueue($orderId, $siteId, $status);
			static::holdOrder($orderId, $order, static::STATE_PROCESSING);

			$sendResult = static::sendStatus($order, $status, true);

			static::releaseOrder($orderId);

			if (!$sendResult->isSuccess())
			{
				$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());

				$result = new Main\EventResult(
					Main\EventResult::ERROR,
					new Sale\ResultError($errorMessage),
					Market\Config::getModuleName()
				);
			}
		}

		return $result;
	}

	protected static function sendStatusForEventAfter(Sale\Order $order, $status)
	{
		$orderId = $order->getId();
		$siteId = $order->getSiteId();

		if ($orderId > 0 && !static::popStatusQueue($orderId, $siteId, $status))
		{
			static::holdOrder($orderId, $order, static::STATE_SAVING);
			static::sendStatus($order, $status);
			static::releaseOrder($orderId);
		}
	}

	protected static function sendStatus(Sale\OrderBase $order, $status, $isImmediate = false)
	{
		$result = new Main\Result();
		$tradingInfo = static::getTradingInfo($order);

		if ($tradingInfo !== null)
		{
			$orderAccountNumber = OrderRegistry::getOrderAccountNumber($order);
			$procedure = new Market\Trading\Procedure\Runner(
				Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
				$orderAccountNumber
			);

			try
			{
				$procedure->run($tradingInfo['SETUP'], 'send/status', [
					'internalId' => $order->getId(),
					'orderId' => $tradingInfo['ORDER_ID'],
					'orderNum' => $orderAccountNumber,
					'status' => $status,
					'immediate' => $isImmediate,
				]);
			}
			catch (Market\Exceptions\Trading\NotImplementedAction $exception)
			{
				// nothing
			}
			catch (Market\Exceptions\Api\Request $exception)
			{
				if (!$isImmediate)
				{
					$procedure->clearRepeat();
					$procedure->createRepeat();

					$procedure->logException($exception);
				}

				$result->addError(new Main\Error($exception->getMessage()));
			}
			catch (\Exception $exception)
			{
				$procedure->logException($exception);

				$result->addError(new Main\Error($exception->getMessage()));
			}
		}

		return $result;
	}

	protected static function getTradingInfo(Sale\OrderBase $order)
	{
		$platformRow = OrderRegistry::searchPlatform($order->getId());

		if ($platformRow === null) { return null; }

		$orderInfo = $platformRow + [ 'SITE_ID' => $order->getSiteId() ];
		$setup = static::getTradingSetup($orderInfo);

		if ($setup === null) { return null; }

		return [
			'ORDER_ID' => $orderInfo['EXTERNAL_ORDER_ID'],
			'SETUP' => $setup,
		];
	}

	protected static function getTradingSetup(array $orderInfo)
	{
		$signValues = array_intersect_key($orderInfo, [
			'TRADING_PLATFORM_ID' => true,
			'SITE_ID' => true,
			'SETUP_ID' => true,
		]);
		$sign = implode(':', $signValues);

		if (!array_key_exists($sign, static::$tradingSetupCache))
		{
			static::$tradingSetupCache[$sign] = static::loadTradingSetup($orderInfo);
		}

		return static::$tradingSetupCache[$sign];
	}

	protected static function loadTradingSetup(array $orderInfo)
	{
		try
		{
			$result = Market\Trading\Setup\Model::loadByTradingInfo($orderInfo);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = null;
		}

		return $result;
	}

	public static function hasOrder($orderId)
	{
		return isset(static::$eventOrderList[$orderId]);
	}

	public static function getOrder($orderId)
	{
		return static::$eventOrderList[$orderId];
	}

	public static function getOrderState($orderId)
	{
		return isset(static::$eventOrderState[$orderId]) ? static::$eventOrderState[$orderId] : null;
	}

	public static function holdOrder($orderId, $order, $state)
	{
		static::$eventOrderList[$orderId] = $order;
		static::$eventOrderState[$orderId] = $state;
	}

	public static function releaseOrder($orderId)
	{
		unset(static::$eventOrderList[$orderId], static::$eventOrderState[$orderId]);
	}

	protected static function isAdminRequest()
	{
		global $USER;

		$request = Main\Context::getCurrent()->getRequest();
		$requestedPage = $request->getRequestedPage();
		$result = true;

		if (Market\Utils::isCli()) // is background process
		{
			$result = false;
		}
		else if (Market\Data\TextString::getPosition($requestedPage, BX_ROOT . '/admin/sale_order') !== 0) // not is order admin page
		{
			$result = false;
		}
		else if (!empty($_SESSION['BX_CML2_EXPORT'])) // is 1c exchange
		{
			$result = false;
		}
		else if (!$USER || !$USER->IsAuthorized()) // hasn't valid user
		{
			$result = false;
		}

		return $result;
	}

	protected static function isShipmentSiblingsFilled(Sale\Shipment $shipment, $name, $value)
	{
		$shipmentCollection = $shipment->getCollection();
		$result = false;

		if ($shipmentCollection)
		{
			$result = true;

			/** @var $otherShipment Sale\Shipment */
			foreach ($shipmentCollection as $otherShipment)
			{
				if ($otherShipment === $shipment)
				{
					// nothing
				}
				else if ($otherShipment->isSystem())
				{
					if (!$otherShipment->isEmpty())
					{
						$result = false;
						break;
					}
				}
				else if (!$otherShipment->isEmpty() && $otherShipment->getField($name) !== $value)
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	protected static function getShipmentOrder(Sale\Shipment $shipment)
	{
		/** @var Sale\ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();

		return $shipmentCollection ? $shipmentCollection->getOrder() : null;
	}

	protected static function isPaymentOrderPaid(Sale\Payment $payment)
	{
		/** @var Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payment->getCollection();
		$order = $paymentCollection ? $paymentCollection->getOrder() : null;
		$result = false;

		if ($paymentCollection && $order)
		{
			$paidSum = 0;

			/** @var Sale\Payment $otherPayment */
			foreach ($paymentCollection as $otherPayment)
			{
				if ($otherPayment === $payment || $otherPayment->isPaid())
				{
					$paidSum += $otherPayment->getSum();
				}
			}

			if (
				$paidSum >= 0
				&& static::roundPrice($order->getPrice()) <= static::roundPrice($paidSum)
			)
			{
				$result = true;
			}
		}

		return $result;
	}

	protected static function roundPrice($price)
	{
		if (method_exists('\Bitrix\Sale\PriceMaths', 'roundPrecision'))
		{
			$result = Sale\PriceMaths::roundPrecision($price);
		}
		else
		{
			$result = roundEx($price, 2);
		}

		return $result;
	}

	protected static function getPaymentOrder(Sale\Payment $payment)
	{
		/** @var Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payment->getCollection();

		return $paymentCollection ? $paymentCollection->getOrder() : null;
	}

	protected static function hasOrderActivePayment(Sale\Order $order)
	{
		$result = false;

		/** @var Sale\Payment $payment */
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->isPaid())
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected static function hasOrderActiveShipment(Sale\Order $order)
	{
		$result = false;

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isShipped())
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected static function pushStatusQueue($orderId, $statusId, $siteId)
	{
		$key = static::getStatusQueueKey($orderId, $statusId, $siteId);

		static::$statusEventQueue[$key] = true;
	}

	protected static function popStatusQueue($orderId, $statusId, $siteId)
	{
		$key = static::getStatusQueueKey($orderId, $statusId, $siteId);
		$result = false;

		if (isset(static::$statusEventQueue[$key]))
		{
			$result = true;
			unset(static::$statusEventQueue[$key]);
		}

		return $result;
	}

	protected static function getStatusQueueKey($orderId, $statusId, $siteId)
	{
		return $orderId . ':' . $statusId . ':' . $siteId;
	}

	protected function getEventHandlers()
	{
		return [
			[
				'module' => 'sale',
				'event' => 'OnBeforeSaleOrderSetField',
				'sort' => 200
			],
			[
				'module' => 'sale',
				'event' => 'OnBeforeSaleShipmentSetField',
				'sort' => 200
			],
			[
				'module' => 'sale',
				'event' => 'OnBeforeSalePaymentSetField',
				'sort' => 200
			],
			[
				'module' => 'sale',
				'event' => 'OnSaleStatusOrderChange'
			],
			[
				'module' => 'sale',
				'event' => 'OnSaleOrderCanceled'
			],
			[
				'module' => 'sale',
				'event' => 'OnShipmentAllowDelivery'
			],
			[
				'module' => 'sale',
				'event' => 'OnSaleOrderPaid'
			],
			[
				'module' => 'sale',
				'event' => 'OnShipmentDeducted'
			],
		];
	}
}