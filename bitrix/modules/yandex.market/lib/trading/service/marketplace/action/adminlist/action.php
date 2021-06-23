<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminList;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasLang;

	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var Request */
	protected $request;
	/** @var Market\Api\Model\OrderCollection */
	protected $externalOrders;
	/** @var Market\Trading\Entity\Reference\Order[] */
	protected $bitrixOrders;
	/** @var array<string, int>*/
	protected $orderMap;
	/** @var array<string, int>*/
	protected $offerMap;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(TradingService\Marketplace\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		$this->loadExternalOrders();
		$this->loadOrderMap();
		$this->loadBitrixOrders();
		$this->loadOfferMap();

		$this->collectOrders();
		$this->collectPager();
	}

	protected function loadExternalOrders()
	{
		$this->flushCache();

		if ($this->request->hasPrimaries())
		{
			$this->loadExternalOrdersByPrimaries();
		}
		else
		{
			$this->loadExternalOrdersByList();
		}

		$this->writeCache();
	}

	protected function loadExternalOrdersByPrimaries()
	{
		$collection = new Market\Api\Model\OrderCollection();

		foreach ($this->request->getPrimaries() as $primary)
		{
			$order = $this->fetchOrderByPrimary($primary);

			$order->setCollection($collection);
			$collection->addItem($order);
		}

		$this->externalOrders = $collection;
	}

	protected function fetchOrderByPrimary($primary)
	{
		$useCache = $this->request->useCache();

		if ($useCache && Market\Trading\State\SessionCache::has('order', $primary))
		{
			$fields = Market\Trading\State\SessionCache::get('order', $primary);
			$result = TradingService\Marketplace\Model\Order::initialize($fields);
		}
		else
		{
			$options = $this->provider->getOptions();
			$logger = $this->provider->getLogger();

			$result = TradingService\Marketplace\Model\OrderFacade::load($options, $primary, $logger);
		}

		return $result;
	}

	protected function loadExternalOrdersByList()
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$parameters = $this->request->getParameters();
		$parameters = $this->convertExternalOrderParameters($parameters);

		if (!isset($parameters['status']) && $this->request->onlyPrintReady())
		{
			$parameters['status'] = TradingService\Marketplace\Status::STATUS_PROCESSING;
		}

		$this->externalOrders = TradingService\Marketplace\Model\OrderFacade::loadList($options, $parameters, $logger);
	}

	protected function flushCache()
	{
		if ($this->request->flushCache())
		{
			Market\Trading\State\SessionCache::releaseByType('order');
		}
	}

	protected function writeCache()
	{
		if ($this->request->useCache())
		{
			/** @var Market\Api\Model\Order $order */
			foreach ($this->externalOrders as $order)
			{
				if ($this->isPrintReady($order))
				{
					Market\Trading\State\SessionCache::set('order', $order->getId(), $order->getFields());
				}
			}
		}
	}

	protected function convertExternalOrderParameters($parameters)
	{
		if (isset($parameters['status']))
		{
			$serviceStatuses = $this->provider->getStatus();
			$complexParts = $serviceStatuses->splitComplex($parameters['status']);

			if ($complexParts !== null)
			{
				$overrides = array_intersect_key($complexParts, [
					'status' => true,
					'substatus' => true,
				]);

				$parameters = $overrides + $parameters;
			}
		}

		return $parameters;
	}

	protected function loadOrderMap()
	{
		$externalIds = $this->getOrderExternalIds();

		if (!empty($externalIds))
		{
			$platform = $this->getPlatform();
			$map = $this->environment->getOrderRegistry()->searchList($externalIds, $platform, false);
		}
		else
		{
			$map = [];
		}

		$this->orderMap = $map;
	}

	protected function getOrderExternalIds()
	{
		$result = [];

		foreach ($this->externalOrders as $order)
		{
			$result[] = $order->getId();
		}

		return $result;
	}

	protected function loadBitrixOrders()
	{
		if (empty($this->orderMap)) { return; }

		$orderIds = array_values($this->orderMap);

		$this->bitrixOrders = $this->environment->getOrderRegistry()->loadOrderList($orderIds);
	}

	protected function loadOfferMap()
	{
		$skuMap = $this->provider->getOptions()->getProductSkuMap();
		$offerMap = null;

		if (!empty($skuMap))
		{
			$product = $this->environment->getProduct();
			$ordersExistsInBitrix = $this->getOrdersExistsInBitrix();
			$offerIds = $this->getOrdersItemOfferIds($ordersExistsInBitrix);
			$offerMap = $product->getOfferMap($offerIds, $skuMap);
		}

		$this->offerMap = $offerMap;
	}

	protected function getOrdersExistsInBitrix()
	{
		$result = [];

		foreach ($this->externalOrders as $order)
		{
			$orderId = $order->getId();
			$bitrixOrder = $this->getBitrixOrder($orderId);

			if ($bitrixOrder !== null)
			{
				$result[] = $order;
			}
		}

		return $result;
	}

	protected function getOrdersItemOfferIds($orders)
	{
		$offerIds = [];

		/** @var Market\Api\Model\Order $order */
		foreach ($orders as $order)
		{
			/** @var Market\Api\Model\Order\Item $item */
			foreach ($order->getItems() as $item)
			{
				$offerId = $item->getOfferId();

				$offerIds[$offerId] = true;
			}
		}

		return array_keys($offerIds);
	}

	protected function getProductId($offerId)
	{
		$result = null;

		if ($this->offerMap === null)
		{
			$result = $offerId;
		}
		else if (isset($this->offerMap[$offerId]))
		{
			$result = $this->offerMap[$offerId];
		}

		return $result;
	}

	protected function collectOrders()
	{
		$rows = [];
		$onlyPrintReady = $this->request->onlyPrintReady();
		$needCheckAccess = $this->request->needCheckAccess();

		foreach ($this->externalOrders as $order)
		{
			$externalId = $order->getId();
			$bitrixOrder = $this->getBitrixOrder($externalId);

			$row = $this->getOrderRow($order, $bitrixOrder);
			$isMatchPrint = !$onlyPrintReady || $row['PRINT_READY'];
			$isMatchRights = !$needCheckAccess || $row['VIEW_ACCESS'];

			if ($isMatchPrint && $isMatchRights)
			{
				$rows[] = $row;
			}
		}

		$this->response->setField('orders', $rows);
	}

	protected function getBitrixOrder($externalId)
	{
		$result = null;

		if (isset($this->orderMap[$externalId]))
		{
			$orderId = $this->orderMap[$externalId];

			if (isset($this->bitrixOrders[$orderId]))
			{
				$result = $this->bitrixOrders[$orderId];
			}
		}

		return $result;
	}

	protected function getOrderRow(Market\Api\Model\Order $order, TradingEntity\Reference\Order $bitrixOrder = null)
	{
		/** @var \Yandex\Market\Trading\Service\Marketplace\Status $serviceStatuses */
		$tradingOptions = $this->provider->getOptions();
		$serviceStatuses = $this->provider->getStatus();
		$orderStatus = $order->getStatus();
		$orderSubStatus = $order->getSubStatus();

		$result = [
			'ID' => $order->getId(),
			'SERVICE_URL' => $order->getServiceUrl($tradingOptions),
			'ORDER_ID' => null,
			'ACCOUNT_NUMBER' => null,
			'EDIT_URL' => null,
			'DATE_CREATE' => $order->getCreationDate(),
			'DATE_SHIPMENT' => $this->getOrderShipmentDates($order),
			'TOTAL' => null,
			'SUBSIDY' => null,
			'CURRENCY' => Market\Data\Currency::getCurrency($order->getCurrency()),
			'STATUS' => $orderStatus,
			'STATUS_LANG' => $this->getOrderStatusLang($orderStatus, $orderSubStatus),
			'SUBSTATUS' => $orderSubStatus,
			'SUBSTATUS_LANG' => $this->getOrderSubStatusLang($orderStatus, $orderSubStatus),
			'FAKE' => $order->isFake(),
			'BASKET' => $this->getBasketFieldData($order, $bitrixOrder),
			'SHIPMENT' => $this->getShipmentFieldData($order, $bitrixOrder),
			'PRINT_READY' => $bitrixOrder !== null && $this->isPrintReady($order),
			'VIEW_ACCESS' => false,
		];

		if ($serviceStatuses->isConfirmed($orderStatus))
		{
			$result['TOTAL'] = $order->getItemsTotal();
			$result['SUBSIDY'] = $order->getSubsidyTotal();
		}

		if ($bitrixOrder !== null)
		{
			$userId = $this->request->getUserId();
			$needCheckAccess = $this->request->needCheckAccess();

			$result['ORDER_ID'] = $bitrixOrder->getId();
			$result['ACCOUNT_NUMBER'] = $bitrixOrder->getAccountNumber();
			$result['EDIT_URL'] = $bitrixOrder->getAdminEditUrl();
			$result['VIEW_ACCESS'] = $needCheckAccess
				? $bitrixOrder->hasAccess($userId, TradingEntity\Operation\Order::VIEW)
				: true;
		}

		return $result;
	}

	protected function getOrderShipmentDates(Market\Api\Model\Order $order)
	{
		return $order instanceof TradingService\Marketplace\Model\Order
			? $order->getMeaningfulShipmentDates()
			: [];
	}

	protected function getOrderStatusLang($status, $subStatus)
	{
		if ($status === TradingService\Marketplace\Status::STATUS_PROCESSING && (string)$subStatus !== '')
		{
			$langStatus = $subStatus;
		}
		else
		{
			$langStatus = $status;
		}

		return $this->provider->getStatus()->getTitle($langStatus, 'SHORT');
	}

	protected function getOrderSubStatusLang($status, $subStatus)
	{
		$result = '';

		if ((string)$subStatus !== '')
		{
			$result = $this->provider->getStatus()->getTitle($subStatus, 'SHORT');
		}

		return $result;
	}

	protected function getBasketFieldData(Market\Api\Model\Order $order, TradingEntity\Reference\Order $bitrixOrder = null)
	{
		$result = [];

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($order->getItems() as $item)
		{
			$offerId = $item->getOfferId();
			$productId = $this->getProductId($offerId);
			$itemRow = [
				'OFFER_ID' => $offerId,
				'OFFER_NAME' => $item->getOfferName(),
				'PRODUCT_ID' => $productId,
				'PRICE' => $item->getPrice(),
				'QUANTITY' => $item->getCount(),
				'URL' => null,
			];

			if ($bitrixOrder !== null && $productId !== null)
			{
				$basketCode = $bitrixOrder->getBasketItemCode($productId);

				if ($basketCode !== null)
				{
					$basketData = $bitrixOrder->getBasketItemData($basketCode)->getData();
					$itemRow['URL'] = isset($basketData['DETAIL_PAGE_URL'])
						? $basketData['DETAIL_PAGE_URL']
						: null;
				}
			}

			$result[] = $itemRow;
		}

		return $result;
	}

	protected function getShipmentFieldData(Market\Api\Model\Order $order, TradingEntity\Reference\Order $bitrixOrder = null)
	{
		$result = [];

		if ($order->hasDelivery())
		{
			foreach ($order->getDelivery()->getShipments() as $shipment)
			{
				$shipment = [
					'ID' => $shipment->getId(),
					'BOX' => $this->getShipmentBoxesData($shipment),
				];

				$result[] = $shipment;
			}
		}

		return $result;
	}

	protected function getShipmentBoxesData(Market\Api\Model\Order\Shipment $shipment)
	{
		$result = [];

		/** @var Market\Api\Model\Order\Box $box*/
		foreach ($shipment->getBoxes() as $box)
		{
			$result[] =
				[
					'ID' => $box->getId(),
					'FULFILMENT_ID' => $box->getFulfilmentId(),
				]
				+ $this->getShipmentBoxDimensions($box);
		}

		return $result;
	}

	protected function getShipmentBoxDimensions(Market\Api\Model\Order\Box $box)
	{
		$result = [];

		// weight

		$weightUnit = $box->getWeightUnit();

		$result['WEIGHT'] = [
			'VALUE' => $box->getWeight(),
			'UNIT' => $weightUnit,
		];

		// sizes

		$sizes = [
			'WIDTH' => $box->getWidth(),
			'HEIGHT' => $box->getHeight(),
			'DEPTH' => $box->getDepth(),
		];
		$sizeUnit = $box->getSizeUnit();

		foreach ($sizes as $sizeName => $sizeValue)
		{
			$result[$sizeName] = [
				'VALUE' => $sizeValue,
				'UNIT' => $sizeUnit,
			];
		}

		return $result;
	}

	protected function isPrintReady(Market\Api\Model\Order $order)
	{
		return $this->isOrderProcessing($order) && $this->hasSavedBoxes($order);
	}

	protected function isOrderProcessing(Market\Api\Model\Order $order)
	{
		$status = $order->getStatus();

		return $this->provider->getStatus()->isProcessing($status);
	}

	protected function hasSavedBoxes(Market\Api\Model\Order $order)
	{
		$result = false;

		if ($order->hasDelivery())
		{
			/** @var Market\Api\Model\Order\Shipment $shipment */
			foreach ($order->getDelivery()->getShipments() as $shipment)
			{
				if ($shipment->hasSavedBoxes())
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	protected function collectPager()
	{
		$pager = $this->externalOrders->getPager();

		if ($pager !== null)
		{
			$this->response->setField('totalCount', $pager->getTotal());

			if ($pager->hasNext())
			{
				$nextPage = $pager->getCurrentPage() + 1;

				$this->response->setField('nextPage', $nextPage);
			}
		}
	}
}