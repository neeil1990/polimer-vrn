<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Listener extends Market\Trading\Entity\Reference\Listener
{
	const STATE_PROCESSING = 1;
	const STATE_SAVING = 2;

	const STRICT_INTERNAL_CHANGE = 'yamarketStrictInternalChange';

	protected static $statusEventQueue = [];
	protected static $eventOrderList = [];
	protected static $eventOrderState = [];
	protected static $orderInfoCache = [];
	protected static $tradingSetupCache = [];
	protected static $watches = [];
	protected static $internalChanges = [];
	protected static $handledSaleOrderBeforeSaved = false;
	protected static $delayedActions = [];
	protected static $basketDeletedProducts = [];

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
				static::addWatch($order->getId(), [
					'FIELD' => 'ORDER.CANCELED',
					'TARGET' => 'Y',
					'PATH' => 'send/status',
					'PAYLOAD' => [
						'status' => Status::STATUS_CANCELED,
					],
				]);
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
			$order = static::getShipmentOrder($shipment);

			if ($order === null) { return null; }

			if (
				($name === Status::STATUS_ALLOW_DELIVERY || $name === Status::STATUS_DEDUCTED)
				&& $value === 'Y'
				&& static::isShipmentSiblingsFilled($shipment, $name, $value)
			)
			{
				$result = static::sendStatusForEventBefore($order, $name);
			}
			else if ($name === 'TRACKING_NUMBER' && !Market\Utils\Value::isEmpty($value))
			{
				static::addWatch($order->getId(), [
					'FIELD' => 'SHIPMENT.TRACKING_NUMBER',
					'TARGET' => $value,
					'PATH' => 'send/track',
					'PAYLOAD' => [
						'trackCode' => $value,
						'deliveryId' => $shipment->getDeliveryId(),
					],
				]);
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

	public static function onSaleBasketItemEntityDeleted(Main\Event $event)
	{
		$basketItem = $event->getParameter('ENTITY');
		$oldValues = $event->getParameter('VALUES');
		$quantity = isset($oldValues['QUANTITY']) ? $oldValues['QUANTITY'] : null;

		static::storeDeletedBasketItem($basketItem, $quantity);
	}

	protected static function signNewBasketItems(Sale\OrderBase $order)
	{
		$orderInternalId = $order->getInternalId();
		$basket = $order->getBasket();

		if ($basket === null || !isset(static::$basketDeletedProducts[$orderInternalId])) { return; }

		$deleted = static::$basketDeletedProducts[$orderInternalId];
		$signed = [];

		foreach ($deleted as $deletedData)
		{
			$matchPriority = null;
			$matchItem = null;

			/** @var Sale\BasketItemBase $basketItem */
			foreach ($basket as $basketItem)
			{
				$basketCode = $basketItem->getBasketCode();

				if (isset($signed[$basketCode]) || $basketItem->getId() > 0) { continue; }

				$comparePriority = static::compareBasketItemWithDeleted($basketItem, $deletedData);

				if ($comparePriority !== null && $comparePriority > $matchPriority)
				{
					$matchPriority = $comparePriority;
					$matchItem = $basketItem;
				}
			}

			if ($matchItem !== null && $matchItem->getField('XML_ID') !== $deletedData['XML_ID'])
			{
				$signXmlId = static::makeBasketItemXmlIdSign($deletedData['XML_ID'], $basket);
				$signed[$matchItem->getBasketCode()] = true;

				$matchItem->setField('XML_ID', $signXmlId);
			}
		}

		unset(static::$basketDeletedProducts[$orderInternalId]);
	}

	protected static function makeBasketItemXmlIdSign($xmlId, Sale\BasketBase $basket)
	{
		$xmlId = preg_replace('/_R\d+$/', '', $xmlId);

		do
		{
			$result = $xmlId . '_R' . mt_rand(0, 100);
			$hasMatch = false;

			/** @var Sale\BasketItemBase $basketItem */
			foreach ($basket as $basketItem)
			{
				if ($basketItem->getField('XML_ID') === $result)
				{
					$hasMatch = true;
					break;
				}
			}
		}
		while ($hasMatch);

		return $result;
	}

	protected static function compareBasketItemWithDeleted(Sale\BasketItemBase $basketItem, array $deletedData)
	{
		$result = null;
		list($parentField, $parentSign) = static::getBasketItemParentSign($basketItem);
		$currentData = [
			'PRODUCT_ID' => $basketItem->getProductId(),
			'BASKET_CODE' => $basketItem->getBasketCode(),
			'PARENT_FIELD' => $parentField,
			'PARENT_SIGN' => $parentSign,
			'PRICE' => (int)$basketItem->getPrice(),
			'QUANTITY' => (int)$basketItem->getQuantity(),
		];
		$methods = [
			[ 'BASKET_CODE' ],
			[ 'PRODUCT_ID', 'QUANTITY' ],
			[ 'PRODUCT_ID' ],
			[ 'PARENT_FIELD', 'PARENT_SIGN', 'QUANTITY' ],
			[ 'PARENT_FIELD', 'PARENT_SIGN' ],
			[ 'PRICE', 'QUANTITY' ],
		];
		$priority = count($methods);

		foreach ($methods as $fields)
		{
			$compare = array_intersect_key($currentData, array_flip($fields));
			$isMatch = true;

			foreach ($compare as $field => $value)
			{
				if (
					$value === null
					|| !isset($deletedData[$field])
					|| (string)$value !== (string)$deletedData[$field]
				)
				{
					$isMatch = false;
					break;
				}
			}

			if ($isMatch)
			{
				$result = $priority;
				break;
			}

			--$priority;
		}

		return $result;
	}

	protected static function storeDeletedBasketItem(Sale\BasketItemBase $basketItem, $quantity = null)
	{
		/** @var Sale\BasketBase $basket */
		$basket = $basketItem->getCollection();
		$xmlId = (string)$basketItem->getField('XML_ID');

		if ($basket === null || $xmlId === '') { return; }
		if (Market\Data\TextString::getPosition($xmlId, Market\Trading\Service\Reference\Dictionary::PREFIX_BASE) !== 0) { return; }

		$order = $basket->getOrder();

		if ($order === null) { return; }

		list($parentField, $parentSign) = static::getBasketItemParentSign($basketItem);
		$orderInternalId = $order->getInternalId();

		if (!isset(static::$basketDeletedProducts[$orderInternalId]))
		{
			static::$basketDeletedProducts[$orderInternalId] = [];
		}

		static::$basketDeletedProducts[$orderInternalId][] = [
			'XML_ID' => $xmlId,
			'BASKET_CODE' => $basketItem->getBasketCode(),
			'PARENT_FIELD' => $parentField,
			'PARENT_SIGN' => $parentSign,
			'PRODUCT_ID' => $basketItem->getProductId(),
			'PRICE' => (int)$basketItem->getPrice(),
			'QUANTITY' => $quantity !== null ? (int)$quantity : null,
		];
	}

	protected static function getBasketItemParentSign(Sale\BasketItemBase $basketItem)
	{
		$productXmlId = trim($basketItem->getField('PRODUCT_XML_ID'));
		$productId = $basketItem->getField('PRODUCT_ID');
		$productXmlHashPosition = Market\Data\TextString::getPosition($productXmlId, '#');
		$field = null;
		$value = null;

		if ($productXmlHashPosition > 0)
		{
			$field = 'XML_ID';
			$value = Market\Data\TextString::getSubstring($productXmlId, 0, $productXmlHashPosition);
		}
		else if (Main\Loader::includeModule('catalog'))
		{
			$skuInfo = \CCatalogSku::GetProductInfo($productId);

			if ($skuInfo !== false && isset($skuInfo['ID']))
			{
				$field = 'ID';
				$value = (int)$skuInfo['ID'];
			}
		}

		return [$field, $value];
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

	public static function onShipmentTrackingNumberChange(Main\Event $event)
	{
		$shipment = $event->getParameter('ENTITY');

		if (!($shipment instanceof Sale\Shipment)) { return; }

		$trackNumber = trim($shipment->getField('TRACKING_NUMBER'));
		$deliveryId = $shipment->getDeliveryId();
		$order = static::getShipmentOrder($shipment);

		if ($order === null || $trackNumber === '' || $deliveryId <= 0) { return; }

		static::sendTrackingNumber($order, $trackNumber, $deliveryId);
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

	/** @deprecated */
	protected static function handleSaleOrderBeforeSaved()
	{
		trigger_error(self::class . '::handleSaleOrderBeforeSaved is deprecated', E_USER_DEPRECATED);
	}

	public static function onSaleOrderBeforeSaved(Main\Event $event)
	{
		/** @var Sale\OrderBase $order */
		$order = $event->getParameter('ENTITY');
		$tradingInfo = static::getTradingInfo($order);

		if ($tradingInfo === null) { return null; }

		static::signNewBasketItems($order);

		$actions = static::makeOrderActions($order, $tradingInfo);
		list($immediateActions, $delayedActions) = static::splitOrderDelayedActions($actions);

		static::releaseWatches($tradingInfo->getInternalId());
		static::releaseInternalChanges($tradingInfo->getInternalId());

		if (!empty($delayedActions))
		{
			static::$delayedActions[$order->getId()] = $actions;
		}

		if (empty($immediateActions))
		{
			return null;
		}

		return static::processSaleOrderEventActions($order, $tradingInfo, $immediateActions, static::STATE_PROCESSING);
	}

	public static function onSaleOrderSaved(Main\Event $event)
	{
		if (static::isAdminRequest()) { return null; }

		/** @var Sale\OrderBase $order */
		$order = $event->getParameter('ENTITY');
		$orderId = $order->getId();

		if (!isset(static::$delayedActions[$orderId])) { return null; }

		$tradingInfo = static::getTradingInfo($order);
		$actions = static::$delayedActions[$orderId];

		if ($tradingInfo === null) { return null; }

		static::processSaleOrderEventActions($order, $tradingInfo, $actions, static::STATE_SAVING);

		unset(static::$delayedActions[$orderId]);
	}

	/** @deprecated */
	protected static function processSaleOrderChangeEvent(Sale\OrderBase $internalOrder, $state)
	{
		$result = new Main\EventResult(Main\EventResult::SUCCESS);
		$tradingInfo = static::getTradingInfo($internalOrder);

		if ($tradingInfo === null) { return $result; }

		$actions = static::makeOrderActions($internalOrder, $tradingInfo);
		$result = static::processSaleOrderEventActions($internalOrder, $tradingInfo, $actions, $state);

		static::releaseWatches($tradingInfo->getInternalId());
		static::releaseInternalChanges($tradingInfo->getInternalId());

		return $result;
	}

	protected static function processSaleOrderEventActions(Sale\OrderBase $internalOrder, Listener\TradingInfo $tradingInfo, array $actions, $state)
	{
		$result = new Main\EventResult(Main\EventResult::SUCCESS);

		foreach ($actions as $action)
		{
			$payload = static::makeProcedurePayload($action);

			if ($payload === null) { continue; }

			$procedureData = new Listener\ProcedureData($action['PATH'], $payload);
			$actionResult = static::processOrderAction($internalOrder, $tradingInfo, $procedureData, $state);

			if ($actionResult->getType() === Main\EventResult::ERROR)
			{
				$result = $actionResult;
				break;
			}
		}

		return $result;
	}

	protected static function makeOrderActions(Sale\OrderBase $internalOrder, Listener\TradingInfo $tradingInfo)
	{
		$setup = $tradingInfo->getSetup();
		$options = $setup->wakeupService()->getOptions();
		$fieldActions = array_merge(
			$options->getEnvironmentFieldActions($setup->getEnvironment()),
			static::getWatches($tradingInfo->getInternalId())
		);
		$usedFields = array_unique(array_column($fieldActions, 'FIELD'));
		$changedValues = static::collectOrderChanges($internalOrder, $usedFields);
		$changedValues = static::filterInternalChanges($tradingInfo->getInternalId(), $changedValues);

		return static::collectChangesActions($fieldActions, $changedValues);
	}

	protected static function collectOrderChanges(Sale\OrderBase $order, $usedFields)
	{
		$result = [];

		foreach ($usedFields as $usedField)
		{
			list($type, $name) = explode('.', $usedField, 2);

			if ($type === 'ORDER')
			{
				if (!static::isSaleEntityHasChange($order, $name)) { continue; }

				$result[$usedField] = $order->getField($name);
			}
			else if (!($order instanceof Sale\Order))
			{
				continue;
			}
			else if (Market\Data\TextString::getPosition($type, 'PROPERTY_') === 0)
			{
				$propertyId = (int)Market\Data\TextString::getSubstring(
					$type,
					Market\Data\TextString::getLength('PROPERTY_')
				);

				if ($propertyId <= 0) { continue; }

				$propertyCollection = $order->getPropertyCollection();
				$property = $propertyCollection->getItemByOrderPropertyId($propertyId);

				if ($property === null) { continue; }

				if (!static::isSaleEntityHasChange($property, $name)) { continue; }

				$result[$usedField] = $property->getField($name);
			}
			else if ($type === 'SHIPMENT')
			{
				list($hasChanges, $value) = static::collectShipmentValues($order, $name);

				if (!$hasChanges) { continue; }

				$result[$usedField] = $value;
			}
			else if ($type === 'BASKET')
			{
				list($hasChanges, $value) = static::collectBasketValues($order, $name);

				if (!$hasChanges) { continue; }

				$result[$usedField] = $value;
			}
			else if ($type === 'CASHBOX')
			{
				$result[$usedField] = (
					class_exists(Sale\Cashbox\Internals\Pool::class)
					&& !empty(Sale\Cashbox\Internals\Pool::getDocs($order->getInternalId()))
				);
			}
		}

		return $result;
	}

	protected static function collectShipmentValues(Sale\Order $order, $field)
	{
		list($type, $name) = explode('.', $field, 2);
		$hasChanges = false;
		$values = null;

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			if ($type === 'ITEM')
			{
				list($hasItemChanges, $itemValues) = static::collectShipmentItemValues($shipment, $name);

				$hasChanges = ($hasChanges || $hasItemChanges);

				$values = $values !== null
					? array_merge($values, $itemValues)
					: $itemValues;
			}
			else
			{
				$hasChanges = static::isSaleEntityHasChange($shipment, $type);
				$values = $shipment->getField($type);
			}
		}

		return [$hasChanges, $values];
	}

	protected static function collectShipmentItemValues(Sale\Shipment $shipment, $field)
	{
		list($type, $name) = explode('.', $field, 2);
		$hasChanges = false;
		$values = [];

		/** @var Sale\ShipmentItem $shipmentItem */
		foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();

			if ($basketItem === null) { continue; }

			if ($type === 'STORE')
			{
				list($hasStoreChanges, $storeValues) = static::collectShipmentItemStoreValues($shipmentItem, $name);

				$hasChanges = ($hasChanges || $hasStoreChanges);

				foreach ($storeValues as $storeValue)
				{
					$values[] = [
						'PRODUCT_ID' => $basketItem->getProductId(),
						'XML_ID' => $basketItem->getField('XML_ID'),
						'MARKING_GROUP' => method_exists($basketItem, 'getMarkingCodeGroup')
							? $basketItem->getMarkingCodeGroup()
							: null,
						'VALUE' => $storeValue,
					];
				}
			}
		}

		return [$hasChanges, $values];
	}

	protected static function collectShipmentItemStoreValues(Sale\ShipmentItem $shipmentItem, $field)
	{
		$hasChanges = false;
		$values = [];

		/** @var Sale\ShipmentItemStore $itemStore */
		foreach ($shipmentItem->getShipmentItemStoreCollection() as $itemStore)
		{
			$hasChanges = ($hasChanges || static::isSaleEntityHasChange($itemStore, $field));
			$values[] = $itemStore->getField($field);
		}

		return [$hasChanges, $values];
	}

	protected static function collectBasketValues(Sale\Order $order, $field)
	{
		$basket = $order->getBasket();

		if ($basket === null) { return [ false, null ]; }

		$hasChanges = (Compatible\EntityCollection::isAnyItemDeleted($basket) && static::getInternalChange($order->getId(), 'BASKET.DELETE') === null);
		$values = [];

		/** @var Sale\BasketItem $basketItem */
		foreach ($order->getBasket() as $basketItem)
		{
			$value = $basketItem->getField($field);

			if (!$hasChanges)
			{
				$hasSelfChanges = static::isSaleEntityHasChange($basketItem, $field);

				if ($field === 'QUANTITY' && $hasSelfChanges)
				{
					$originalValues = $basketItem->getFields()->getOriginalValues();

					$hasSelfChanges = !Market\Data\Quantity::equal($value, $originalValues[$field]);
				}

				if ($hasSelfChanges)
				{
					$internalField = sprintf('BASKET.%s.%s', $basketItem->getBasketCode(), $field);
					$internalChange = static::getInternalChange($order->getId(), $internalField);

					if ($internalChange === null)
					{
						// nothing
					}
					else if ($field === 'QUANTITY')
					{
						$hasSelfChanges = !Market\Data\Quantity::equal($value, $internalChange);
					}
					else
					{
						$hasSelfChanges = (string)$value !== (string)$internalChange;
					}
				}

				$hasChanges = $hasSelfChanges;
			}

			$values[] = [
				'PRODUCT_ID' => $basketItem->getProductId(),
				'XML_ID' => $basketItem->getField('XML_ID'),
				'VALUE' => $value,
			];
		}

		return [$hasChanges, $values];
	}

	protected static function isSaleEntityHasChange(Sale\Internals\Entity $entity, $field)
	{
		return in_array($field, $entity->getFields()->getChangedKeys(), true);
	}

	protected static function collectChangesActions($fieldActions, $changes)
	{
		$result = [];

		foreach ($fieldActions as $fieldAction)
		{
			if (!isset($changes[$fieldAction['FIELD']])) { continue; }

			$value = $changes[$fieldAction['FIELD']];

			if (isset($fieldAction['TARGET']) && $fieldAction['TARGET'] !== $value) { continue; }

			$result[] = $fieldAction + [
				'VALUE' => $value,
			];
		}

		return $result;
	}

	protected static function splitOrderDelayedActions(array $actions)
	{
		$defaultDelay = !static::isAdminRequest();
		$immediate = [];
		$delayed = [];

		foreach ($actions as $action)
		{
			$delay = isset($action['DELAY']) ? $action['DELAY'] : $defaultDelay;

			if ($delay)
			{
				$delayed[] = $action;
			}
			else
			{
				$immediate[] = $action;
			}
		}

		return [$immediate, $delayed];
	}

	protected static function makeProcedurePayload(array $action)
	{
		$result = null;

		if (isset($action['PAYLOAD']))
		{
			$result = $action['PAYLOAD'];
		}
		else if (isset($action['VALUE'], $action['PAYLOAD_MAP'][$action['VALUE']]))
		{
			$result = $action['PAYLOAD_MAP'][$action['VALUE']];
		}

		if (is_callable($result))
		{
			$result = $result($action);
		}

		if ($result === null) { return null; }

		return (array)$result;
	}

	protected static function sendStatusForEventBefore(Sale\Order $order, $status)
	{
		return static::sendStatus($order, $status, true);
	}

	protected static function sendStatusForEventAfter(Sale\Order $order, $status)
	{
		static::sendStatus($order, $status);
	}

	protected static function sendStatus(Sale\OrderBase $order, $status, $isImmediate = false)
	{
		$tradingInfo = static::getTradingInfo($order);

		if ($tradingInfo === null) { return new Main\EventResult(Main\EventResult::SUCCESS); }

		$state = ($isImmediate ? static::STATE_PROCESSING : static::STATE_SAVING);
		$procedureData = new Listener\ProcedureData(
			'send/status',
			[ 'status' => $status ]
		);

		return static::processOrderAction($order, $tradingInfo, $procedureData, $state);
	}

	protected static function sendTrackingNumber(Sale\OrderBase $order, $trackCode, $deliveryId, $isImmediate = false)
	{
		$tradingInfo = static::getTradingInfo($order);

		if ($tradingInfo === null) { return new Main\EventResult(Main\EventResult::SUCCESS); }

		$state = ($isImmediate ? static::STATE_PROCESSING : static::STATE_SAVING);
		$procedureData = new Listener\ProcedureData('send/track', [
			'trackCode' => $trackCode,
			'deliveryId' => $deliveryId,
		]);

		return static::processOrderAction($order, $tradingInfo, $procedureData, $state);
	}

	protected static function processOrderAction(Sale\OrderBase $internalOrder, Listener\TradingInfo $tradingInfo, Listener\ProcedureData $procedureData, $state)
	{
		$result = new Main\EventResult(Main\EventResult::SUCCESS);
		$immediate = ($state === static::STATE_PROCESSING);

		if ($immediate)
		{
			static::pushStatusQueue($tradingInfo->getInternalId(), $tradingInfo->getSiteId(), $procedureData);
		}
		else if (static::popStatusQueue($tradingInfo->getInternalId(), $tradingInfo->getSiteId(), $procedureData))
		{
			return $result;
		}

		static::holdOrder($internalOrder->getId(), $internalOrder, $state);

		$procedureResult = static::callProcedure($tradingInfo, $procedureData, $immediate);

		static::releaseOrder($internalOrder->getId());

		if (!$procedureResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $procedureResult->getErrorMessages());

			$result = new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError($errorMessage),
				Market\Config::getModuleName()
			);
		}

		return $result;
	}

	protected static function callProcedure(Listener\TradingInfo $tradingInfo, Listener\ProcedureData $action, $isImmediate = false)
	{
		$result = new Main\Result();
		$procedure = new Market\Trading\Procedure\Runner(
			Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
			$tradingInfo->getAccountNumber()
		);

		try
		{
			$procedure->run(
				$tradingInfo->getSetup(),
				$action->getPath(),
				$action->getPayload() + $tradingInfo->getProcedurePayload($isImmediate) + [
					'autoSubmit' => true,
				]
			);
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
		catch (Market\Exceptions\Trading\Validation $exception)
		{
			if (!$isImmediate)
			{
				$procedure->clearRepeat();
				$procedure->logException($exception);
			}

			$result->addError(new Main\Error($exception->getMessage()));
		}
		catch (\Exception $exception)
		{
			$procedure->logException($exception);

			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	/**
	 * @param Sale\OrderBase $order
	 *
	 * @return Listener\TradingInfo|null
	 */
	protected static function getTradingInfo(Sale\OrderBase $order)
	{
		$orderInfo = static::getOrderInfo($order);

		if ($orderInfo === null) { return null; }

		$setup = static::getTradingSetup($orderInfo);

		if ($setup === null || !$setup->isActive()) { return null; }

		return new Listener\TradingInfo(
			$setup,
			$orderInfo
		);
	}

	protected static function getOrderInfo(Sale\OrderBase $order)
	{
		if ($order->isNew()) { return null; }

		$orderId = $order->getId();
		$result = null;

		if (isset(static::$orderInfoCache[$orderId]))
		{
			$result = static::$orderInfoCache[$orderId];
		}
		else if (!array_key_exists($orderId, static::$orderInfoCache))
		{
			$platformRow = OrderRegistry::searchPlatform($order->getId());
			$result = null;

			if ($platformRow !== null)
			{
				$result = $platformRow + [
					'INTERNAL_ORDER_ID' => $order->getId(),
					'ACCOUNT_NUMBER' => OrderRegistry::getOrderAccountNumber($order),
					'SITE_ID' => $order->getSiteId(),
				];
			}

			static::$orderInfoCache[$orderId] = $result;
		}

		return $result;
	}

	/**
	 * @param array $orderInfo
	 *
	 * @return Market\Trading\Setup\Model|null
	 */
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
		else if (!static::isAdminPage($requestedPage) && !static::isAdminController($request)) // not is order admin page
		{
			$result = false;
		}
		else if (!empty($_SESSION['BX_CML2_EXPORT'])) // is 1c exchange
		{
			$result = false;
		}
		else if (!($USER instanceof \CUser) || !$USER->IsAuthorized()) // hasn't valid user
		{
			$result = false;
		}

		return $result;
	}

	protected static function isAdminPage($path)
	{
		return (Market\Data\TextString::getPosition($path, BX_ROOT . '/admin/sale_order') === 0);
	}

	protected static function isAdminController(Main\Request $request)
	{
		return false;
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

	protected static function addWatch($orderId, array $action)
	{
		if (!isset(static::$watches[$orderId]))
		{
			static::$watches[$orderId] = [];
		}

		static::$watches[$orderId][] = $action;
	}

	protected static function getWatches($orderId)
	{
		return isset(static::$watches[$orderId]) ? static::$watches[$orderId] : [];
	}

	protected static function releaseWatches($orderId)
	{
		if (isset(static::$watches[$orderId]))
		{
			unset(static::$watches[$orderId]);
		}
	}

	public static function filterInternalChanges($orderId, array $changes)
	{
		$internalChanges = static::getInternalChanges($orderId);
		$strictChanges = array_filter($internalChanges, static function($value) { return $value === Listener::STRICT_INTERNAL_CHANGE; });
		$valueChanges = array_diff_key($internalChanges, $strictChanges);

		$changes = array_diff_key($changes, $strictChanges);
		$changes = array_diff_assoc($changes, $valueChanges);

		return $changes;
	}

	public static function addInternalChange($orderId, $field, $value)
	{
		if ($orderId <= 0) { return; }

		if (!isset(static::$internalChanges[$orderId]))
		{
			static::$internalChanges[$orderId] = [];
		}

		static::$internalChanges[$orderId][$field] = $value;
	}

	protected static function getInternalChanges($orderId)
	{
		return isset(static::$internalChanges[$orderId]) ? static::$internalChanges[$orderId] : [];
	}

	protected static function getInternalChange($orderId, $field)
	{
		return isset(static::$internalChanges[$orderId][$field]) ? static::$internalChanges[$orderId][$field] : null;
	}

	protected static function releaseInternalChanges($orderId)
	{
		if (isset(static::$internalChanges[$orderId]))
		{
			unset(static::$internalChanges[$orderId]);
		}
	}

	protected static function pushStatusQueue($orderId, $siteId, $statusId)
	{
		$key = static::getStatusQueueKey($orderId, $siteId, $statusId);

		static::$statusEventQueue[$key] = true;
	}

	protected static function popStatusQueue($orderId, $siteId, $statusId)
	{
		$key = static::getStatusQueueKey($orderId, $siteId, $statusId);
		$result = false;

		if (isset(static::$statusEventQueue[$key]))
		{
			$result = true;
			unset(static::$statusEventQueue[$key]);
		}

		return $result;
	}

	protected static function getStatusQueueKey($orderId, $siteId, $statusId)
	{
		return $orderId . '|' . $siteId . '|' . $statusId;
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
				'event' => 'OnSaleOrderBeforeSaved',
				'sort' => 200,
			],
			[
				'module' => 'sale',
				'event' => 'OnSaleOrderSaved',
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
				'event' => 'OnShipmentTrackingNumberChange'
			],
			[
				'module' => 'sale',
				'event' => 'OnSaleOrderPaid'
			],
			[
				'module' => 'sale',
				'event' => 'OnShipmentDeducted'
			],
			[
				'module' => 'sale',
				'event' => 'OnSaleBasketItemEntityDeleted',
			],
		];
	}
}