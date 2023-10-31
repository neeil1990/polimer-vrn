<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\OrderStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\OrderStatus\Action
{
	use Market\Reference\Concerns\HasOnce;

	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

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
		catch (Main\SystemException $exception)
		{
			if (
				!($exception instanceof Main\InvalidOperationException)
				&& !($exception instanceof Main\ObjectNotFoundException)
			)
			{
				throw $exception;
			}

			$proxyException = new Market\Exceptions\Trading\NotRecoverable($exception->getMessage(), $exception->getCode(), '', 0, $exception);
			$status = $this->request->getOrder()->getStatus();
			$subStatus = $this->request->getOrder()->getSubStatus();

			if (
				$this->provider->getStatus()->isCanceled($status, $subStatus)
				|| $this->provider->getStatus()->isOrderDelivered($status)
			)
			{
				$proxyException->setLogLevel(Market\Logger\Level::DEBUG);
			}

			throw $proxyException;
		}
	}

	protected function fillOrder()
	{
		$this->fillItems();
		$this->fillCis();

		parent::fillOrder();
	}

	protected function makeStatusInSearchVariants($status)
	{
		$result = [
			$status,
		];

		if ($status === TradingService\Marketplace\Status::STATUS_PROCESSING)
		{
			if ($this->isPrepaid())
			{
				array_unshift($result, TradingService\Marketplace\Status::VIRTUAL_PAID);
				array_unshift($result, $status . '_PREPAID');
			}
			else
			{
				array_unshift($result, $status . '_POSTPAID');
			}
		}
		else if ($status === TradingService\Marketplace\Status::STATUS_DELIVERED && !$this->isPrepaid())
		{
			array_unshift($result, TradingService\Marketplace\Status::VIRTUAL_PAID);
			array_unshift($result, $status . '_POSTPAID');
		}

		return $result;
	}

	protected function statusConfiguredAction($status)
	{
		$options = $this->provider->getOptions();
		$result = parent::statusConfiguredAction($status);

		if ($result === null && $status === TradingService\Marketplace\Status::STATUS_PROCESSING . '_POSTPAID')
		{
			$result = $this->statusSubsidyAction();
		}

		if ($result === null && ($options->useSyncStatusOut() || $this->request->isDownload()))
		{
			$outgoingBase = TradingService\Marketplace\Status::STATUS_PROCESSING;
			$outgoingShorts = [
				TradingService\Marketplace\Status::STATE_READY_TO_SHIP,
				TradingService\Marketplace\Status::STATE_SHIPPED,
				TradingService\Marketplace\Status::STATE_SHOP_FAILED,
			];

			foreach ($outgoingShorts as $outgoingShort)
			{
				if ($status === $outgoingBase . '_' . $outgoingShort)
				{
					$result = $options->getStatusOutRaw($outgoingShort);
					break;
				}
			}

			if ($status === $outgoingBase . '_' . TradingService\Marketplace\Status::STATE_SHIPPED)
			{
				$result = $options->getShipmentStatus('CONFIRM');
			}
		}

		return $result;
	}

	protected function statusSubsidyAction()
	{
		if (!$this->provider->getOptions()->includeBasketSubsidy()) { return null; }

		$subsidyStatus = null;
		$prepaidMappings = [
			$this->statusConfiguredAction(TradingService\Marketplace\Status::VIRTUAL_PAID),
			$this->statusConfiguredAction(TradingService\Marketplace\Status::STATUS_PROCESSING . '_PREPAID')
		];

		foreach ($prepaidMappings as $configuredPrepaid)
		{
			if ($configuredPrepaid === null) { continue; }
			if ($this->environment->getStatus()->getMeaningful($configuredPrepaid) !== Market\Data\Trading\MeaningfulStatus::PAYED) { continue; }

			$meaningfulMap = $this->environment->getStatus()->getMeaningfulMap();

			if (!isset($meaningfulMap[Market\Data\Trading\MeaningfulStatus::SUBSIDY])) { continue; }

			$subsidyStatus = $meaningfulMap[Market\Data\Trading\MeaningfulStatus::SUBSIDY];
			break;
		}

		return is_array($subsidyStatus) ? reset($subsidyStatus) : $subsidyStatus;
	}

	protected function isPrepaid()
	{
		$paymentType = $this->request->getOrder()->getPaymentType();

		return $this->provider->getPaySystem()->isPrepaid($paymentType);
	}

	protected function fillProperties()
	{
		parent::fillProperties();
		$this->fillCourierProperties();
	}

	protected function fillUtilProperties()
	{
		$meaningfulValues = $this->request->getOrder()->getMeaningfulValues();

		if (!empty($meaningfulValues['DATE_SHIPMENT']))
		{
			$meaningfulValues['DATE_SHIPMENT'] = $this->extendShipmentDates($meaningfulValues['DATE_SHIPMENT']);
		}

		$this->setMeaningfulPropertyValues($meaningfulValues);
	}

	protected function extendShipmentDates(array $shipmentDates)
	{
		if ($shipmentDates[0] instanceof Main\Type\DateTime || !($shipmentDates[0] instanceof Main\Type\Date))
		{
			return $shipmentDates;
		}

		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();
		$previousShipmentDateString = (string)Market\Trading\State\OrderData::getValue($serviceKey, $orderId, 'SHIPMENT_DATE');

		if ($previousShipmentDateString === '')
		{
			return $shipmentDates;
		}

		$previousShipmentDate = new Main\Type\DateTime($previousShipmentDateString, Market\Data\DateTime::FORMAT_DEFAULT_FULL);

		if ($previousShipmentDate->format('Y-m-d') === $shipmentDates[0]->format('Y-m-d'))
		{
			$shipmentDates[0] = $previousShipmentDate;
		}

		return $shipmentDates;
	}

	protected function fillCourierProperties()
	{
		$order = $this->request->getOrder();

		if (!$order->hasDelivery()) { return; }

		$courier = $order->getDelivery()->getCourier();

		if ($courier === null) { return; }

		$meaningfulProperties = [];

		foreach ($courier->getMeaningfulValues() as $name => $value)
		{
			$meaningfulProperties['COURIER_' . $name] = $value;
		}

		$this->setMeaningfulPropertyValues($meaningfulProperties);
	}

	protected function fillItems()
	{
		$items = $this->request->getOrder()->getItems();
		$offerMap = $this->getOfferMap($items);
		$ratioMap = $this->getRatioMap($items, $offerMap);
		$basketMap = $this->getBasketMap($items, $offerMap);
		$basketMissing = $this->getBasketMissing($basketMap);

		if (count($basketMap) < $items->positiveCount())
		{
			$message = static::getLang('TRADING_ACTION_ORDER_STATUS_ITEMS_NOT_MATCHED_BASKET', [
				'#BASKET_COUNT#' => count($basketMap),
				'#ITEMS_COUNT#' => $items->count(),
			]);
			$this->provider->getLogger()->warning($message);
			return;
		}

		$this->updateItemsQuantity($items, $basketMap, $ratioMap);
		$this->deleteBasketMissing($basketMissing);
	}

	protected function updateItemsQuantity(Market\Api\Model\Order\ItemCollection $items, $basketMap, $ratioMap)
	{
		$changes = [];

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($items as $index => $item)
		{
			$basketCode = $basketMap[$item->getInternalId()];
			$basketResult = $this->order->getBasketItemData($basketCode);

			if (!$basketResult->isSuccess())
			{
				if ($item->getCount() > 0)
				{
					$message = static::getLang('TRADING_ACTION_ORDER_STATUS_ITEM_BASKET_ERROR', [
						'#ITEM_NAME#' => $this->getItemName($item),
						'#MESSAGE#' => implode(', ', $basketResult->getErrorMessages()),
					]);
					$this->provider->getLogger()->warning($message);
				}

				continue;
			}

			$basketData = $basketResult->getData();

			if (!isset($basketData['QUANTITY']))
			{
				$message = static::getLang('TRADING_ACTION_ORDER_STATUS_ITEM_BASKET_DATA_QUANTITY_MISSING', [
					'#ITEM_NAME#' => $this->getItemName($item),
					'#MESSAGE#' => implode(', ', $basketResult->getErrorMessages()),
				]);
				$this->provider->getLogger()->warning($message);
				continue;
			}

			$basketQuantity = (float)$basketData['QUANTITY'];
			$ratio = isset($ratioMap[$index]) ? $ratioMap[$index] : 1;
			$itemCount = $item->getCount() * $ratio;

			if (Market\Data\Quantity::equal($basketQuantity, $itemCount)) { continue; }

			if ($itemCount > 0)
			{
				$setResult = $this->order->setBasketItemQuantity($basketCode, $itemCount);
			}
			else
			{
				$setResult = $this->order->deleteBasketItem($basketCode);
			}

			if (!$setResult->isSuccess())
			{
				$message = static::getLang('TRADING_ACTION_ORDER_STATUS_ITEM_SET_QUANTITY_ERROR', [
					'#ITEM_NAME#' => $this->getItemName($item),
					'#MESSAGE#' => implode(', ', $setResult->getErrorMessages()),
				]);
				$this->provider->getLogger()->warning($message);
				continue;
			}

			$changes[] = [
				'BASKET_CODE' => $basketCode,
				'QUANTITY' => $itemCount,
			];
		}

		if (!empty($changes))
		{
			$this->pushChange('BASKET.QUANTITY', $changes);
		}
	}

	protected function deleteBasketMissing($basketCodes)
	{
		$changes = [];

		foreach ($basketCodes as $basketCode)
		{
			$deleteResult = $this->order->deleteBasketItem($basketCode);

			if (!$deleteResult->isSuccess())
			{
				$message = static::getLang('TRADING_ACTION_ORDER_STATUS_ITEM_DELETE_ERROR', [
					'#ITEM_NAME#' => $this->getBasketItemName($basketCode),
					'#MESSAGE#' => implode(', ', $deleteResult->getErrorMessages()),
				]);
				$this->provider->getLogger()->warning($message);
				continue;
			}

			$changes[] = [
				'BASKET_CODE' => $basketCode,
			];
		}

		if (!empty($changes))
		{
			$this->pushChange('BASKET.DELETE', $changes);
		}
	}

	protected function getBasketMap(Market\Api\Model\Order\ItemCollection $items, $offerMap = null)
	{
		$result = [];

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$basketCode = $this->getItemBasketCode($item, $offerMap);

			if ($basketCode === null) { continue; }

			$result[$item->getInternalId()] = $basketCode;
		}

		return $result;
	}

	protected function getBasketMissing($foundCodes)
	{
		$existsCodes = $this->order->getExistsBasketItemCodes();
		$notFoundCodes = array_diff($existsCodes, $foundCodes);
		$result = [];

		foreach ($notFoundCodes as $basketCode)
		{
			$basketData = $this->order->getBasketItemData($basketCode)->getData();

			if (isset($basketData['XML_ID']))
			{
				$match = $this->provider->getDictionary()->parseOrderItemXmlId($basketData['XML_ID']);

				if ($match === null) { continue; }
			}

			$result[] = $basketCode;
		}

		return $result;
	}

	protected function getOfferMap(Market\Api\Model\Order\ItemCollection $items)
	{
		return $this->once('loadOfferMap', [ $items->getOfferIds() ]);
	}

	/** @noinspection PhpUnused */
	protected function loadOfferMap(array $offerIds)
	{
		$command = new TradingService\Common\Command\OfferMap(
			$this->provider,
			$this->environment
		);

		return $command->make($offerIds);
	}

	protected function getRatioMap(Market\Api\Model\Cart\ItemCollection $items, $offerMap = null)
	{
		$productIds = $offerMap !== null ? array_values($offerMap) : $items->getOfferIds();
		$command = new TradingService\Common\Command\OfferPackRatio($this->provider, $this->environment);
		$result = [];

		$packRatio = $command->make($productIds);

		/** @var Market\Api\Model\Cart\Item $item */
		foreach ($items as $index => $item)
		{
			$productId = $item->mapProductId($offerMap);

			if ($productId === null) { continue; }
			if (!isset($packRatio[$productId])) { continue; }

			$result[$index] = $packRatio[$productId];
		}

		return $result;
	}

	protected function getItemBasketCode(Market\Api\Model\Order\Item $item, $offerMap = null)
	{
		$productId = $item->mapProductId($offerMap);
		$result = null;

		if ($item->getId() !== null)
		{
			$xmlId = $this->provider->getDictionary()->getOrderItemXmlId($item);
			$result = $this->order->getBasketItemCode($xmlId, 'XML_ID');
		}

		if ($result === null && $productId !== null)
		{
			$result = $this->order->getBasketItemCode($productId);
		}

		return $result;
	}

	protected function getItemName(Market\Api\Model\Cart\Item $item)
	{
		$offerName = $item->getOfferName();

		if ($offerName !== '')
		{
			$result = sprintf('[%s] %s', $item->getOfferId(), $item->getOfferName());
		}
		else
		{
			$offerId = $item->getOfferId();

			$result = static::getLang('TRADING_ACTION_ORDER_STATUS_ITEM_NAME_FALLBACK', [
				'#OFFER_ID#' => $offerId,
			], $offerId);
		}

		return $result;
	}

	protected function getBasketItemName($basketCode)
	{
		$basketData = $this->order->getBasketItemData($basketCode)->getData();
		$result = isset($basketData['NAME']) ? trim($basketData['NAME']) : '';

		if ($result === '')
		{
			$result = static::getLang('TRADING_ACTION_ORDER_STATUS_BASKET_ITEM_NAME_FALLBACK', [
				'#BASKET_CODE#' => $basketCode,
			], $basketCode);
		}

		return $result;
	}

	protected function fillCis()
	{
		if (!$this->request->isDownload() && !$this->provider->getOptions()->useSyncStatusOut()) { return; }

		$items = $this->request->getOrder()->getItems();
		$offerMap = $this->getOfferMap($items);
		$basketMarking = [];

		/** @var TradingService\Marketplace\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$instances = $item->getInstances();
			$itemMarking = [];

			if ($instances === null) { continue; }

			/** @var TradingService\Marketplace\Model\Order\Item\Instance $instance */
			foreach ($instances as $instance)
			{
				$code = (string)($instance->getCisFull() ?: $instance->getCis() ?: $instance->getUin());

				if ($code === '') { continue; }

				$itemMarking[] = $code;
			}

			if (empty($itemMarking)) { continue; }

			$basketCode = $this->getItemBasketCode($item, $offerMap);

			if ($basketCode === null) { continue; }

			$basketMarking[$basketCode] = $itemMarking;
		}

		$setResult = $this->order->fillMarking($basketMarking);
		$setData = $setResult->getData();

		if (!empty($setData['CHANGES']))
		{
			$this->pushChange('SHIPMENT.ITEM.STORE.MARKING_CODE', $setData['CHANGES']);
		}
	}

	protected function updateOrder()
	{
		$this->configureCashbox();
		parent::updateOrder();
	}

	protected function configureCashbox()
	{
		if ($this->getCashboxCheckRule() !== TradingService\Marketplace\PaySystem::CASHBOX_CHECK_ENABLED)
		{
			$this->order->resetCashbox();
		}
	}

	protected function getCashboxCheckRule()
	{
		return $this->provider->getOptions()->getCashboxCheck();
	}

	protected function makeData()
	{
		return
			$this->makeStatusData()
			+ $this->makeDeliveryData()
			+ $this->makeShipmentData()
			+ $this->makeItemsData();
	}

	protected function makeDeliveryData()
	{
		$order = $this->request->getOrder();

		if (!$order->hasDelivery()) { return []; }

		$delivery = $order->getDelivery();
		$shipment = $delivery->getShipments()->current();

		return [
			'SHIPMENT_ID' => $shipment !== false ? $shipment->getId() : null,
		];
	}

	protected function makeShipmentData()
	{
		$shipmentDates = $this->request->getOrder()->getMeaningfulShipmentDates();
		$shipmentDates = $this->extendShipmentDates($shipmentDates);

		return [
			'SHIPMENT_DATE' => !empty($shipmentDates)
				? $shipmentDates[0]->format(Market\Data\DateTime::FORMAT_DEFAULT_FULL)
				: null,
		];
	}

	protected function makeItemsData()
	{
		$items = $this->request->getOrder()->getItems();

		return [
			'ITEMS_TOTAL' => $items->getTotalCount(),
		];
	}
}