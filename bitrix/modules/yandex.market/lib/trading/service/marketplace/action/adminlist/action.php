<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminList;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;

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
		$pageNumber = $this->request->getPage();
		$pageSize = $this->request->getPageSize();
		$usePager = ($pageSize !== null);
		$primaries = $this->request->getPrimaries();

		if ($usePager)
		{
			$pageNumber = max(1, (int)$pageNumber);
			$pageSize = min(20, max(1, (int)$pageSize));

			$pagePrimaries = array_slice($primaries, ($pageNumber - 1) * $pageSize, $pageSize);
		}
		else
		{
			$pagePrimaries = $primaries;
		}

		foreach ($pagePrimaries as $primary)
		{
			try
			{
				$order = $this->fetchOrderByPrimary($primary);

				$order->setCollection($collection);
				$collection->addItem($order);
			}
			catch (Market\Exceptions\Api\Request $exception)
			{
				if (!$this->request->suppressErrors())
				{
					throw $exception;
				}
			}
		}

		if ($usePager)
		{
			$pager = $this->makeExternalOrderByPrimariesPager($primaries, $pageNumber, $pageSize);
			$collection->setPager($pager);
		}

		$this->externalOrders = $collection;
	}

	protected function makeExternalOrderByPrimariesPager(array $primaries, $current, $size)
	{
		$total = count($primaries);

		return new Market\Api\Model\Pager([
			'currentPage' => $current,
			'pagesCount' => ceil($total / $size),
			'pageSize' => $size,
			'total' => $total,
		]);
	}

	protected function fetchOrderByPrimary($primary)
	{
		$useCache = $this->request->useCache();

		if ($useCache && Market\Trading\State\SessionCache::has('order', $primary))
		{
			$fields = Market\Trading\State\SessionCache::get('order', $primary);
			$orderClassName = $this->provider->getModelFactory()->getOrderClassName();

			$result = $orderClassName::initialize($fields);
		}
		else
		{
			$options = $this->provider->getOptions();
			$facadeClassName = $this->provider->getModelFactory()->getOrderFacadeClassName();

			$result = $facadeClassName::load($options, $primary);
		}

		return $result;
	}

	protected function loadExternalOrdersByList()
	{
		$options = $this->provider->getOptions();
		$parameters = $this->request->getParameters();
		$parameters = $this->convertExternalOrderParameters($parameters);
		$facadeClassName = $this->provider->getModelFactory()->getOrderFacadeClassName();

		if (!isset($parameters['status']) && $this->request->onlyPrintReady())
		{
			$parameters['status'] = TradingService\Marketplace\Status::STATUS_PROCESSING;
		}

		$this->externalOrders = $facadeClassName::loadList($options, $parameters);
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
		if (!$this->request->useCache()) { return; }

		/** @var Market\Api\Model\Order $order */
		foreach ($this->externalOrders as $order)
		{
			if (!$this->useOrderCache($order)) { continue; }

			Market\Trading\State\SessionCache::set('order', $order->getId(), $order->getFields());
		}
	}

	protected function useOrderCache(Market\Api\Model\Order $order)
	{
		return $this->isOrderProcessing($order);
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
		/** @var \Yandex\Market\Trading\Service\Marketplace\Model\Order $order */
		/** @var \Yandex\Market\Trading\Service\Marketplace\Status $serviceStatuses */
		$tradingOptions = $this->provider->getOptions();
		$serviceStatuses = $this->provider->getStatus();
		$orderStatus = $order->getStatus();
		$orderSubStatus = $order->getSubStatus();
		$buyer = $order->getBuyer();

		$result = [
			'ID' => $order->getId(),
			'SERVICE_URL' => $order->getServiceUrl($tradingOptions),
			'ORDER_ID' => null,
			'ACCOUNT_NUMBER' => null,
			'EDIT_URL' => null,
			'DATE_CREATE' => $order->getCreationDate(),
			'DATE_SHIPMENT' => $this->getOrderShipmentDates($order),
			'DATE_DELIVERY' => $this->getOrderDeliveryDates($order),
			'EXPIRY_DATE' => $order->getExpiryDate(),
			'BUYER_TYPE' => $buyer !== null ? $buyer->getType() : null,
			'EAC_TYPE' => $order->getDelivery()->getEacType(),
			'EAC_CODE' => $order->getDelivery()->getEacCode(),
			'TOTAL' => null,
			'SUBSIDY' => null,
			'CURRENCY' => Market\Data\Currency::getCurrency($order->getCurrency()),
			'STATUS' => $orderStatus,
			'STATUS_LANG' =>
				$this->getOrderStatusLang($orderStatus, $orderSubStatus)
				. ($order->isCancelRequested() && !$serviceStatuses->isCanceled($orderStatus) ? self::getMessage('CANCEL_REQUESTED_STATUS_SUFFIX') : ''),
			'SUBSTATUS' => $orderSubStatus,
			'SUBSTATUS_LANG' => $this->getOrderSubStatusLang($orderStatus, $orderSubStatus),
			'FAKE' => $order->isFake(),
			'BASKET' => $this->getBasketFieldData($order, $bitrixOrder),
			'SHIPMENT' => $this->getShipmentFieldData($order, $bitrixOrder),
			'BOX_COUNT' => $this->getShipmentBoxCount($order),
			'PROCESSING' => $this->isOrderProcessing($order),
			'STATUS_READY' => $bitrixOrder !== null && $this->isStatusReady($order),
			'STATUS_ALLOW' => $bitrixOrder !== null ? $this->makeAllowedStatuses($order) : [],
			'CANCEL_ALLOW' => $bitrixOrder !== null && $this->isCancelAllowed($order),
			'PRINT_READY' => $bitrixOrder !== null && $this->isPrintReady($order),
			'VIEW_ACCESS' => false,
		];

		if ($serviceStatuses->isConfirmed($orderStatus))
		{
			$result['TOTAL'] = $order->getItemsTotal() + $order->getSubsidyTotal();
			$result['SUBSIDY'] = $order->getSubsidyTotal();
		}

		if ($bitrixOrder !== null)
		{
			$userId = $this->request->getUserId();
			$needCheckAccess = $this->request->needCheckAccess();

			$result['ORDER_ID'] = $bitrixOrder->getId();
			$result['ACCOUNT_NUMBER'] = $this->provider->getOptions()->useAccountNumberTemplate()
				? $bitrixOrder->getId()
				: $bitrixOrder->getAccountNumber();
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

	protected function getOrderDeliveryDates(Market\Api\Model\Order $order)
	{
		if (!$order->hasDelivery()) { return null; }

		$dates = $order->getDelivery()->getDates();

		if ($dates === null) { return null; }

		return [
			'FROM' => $dates->getFrom(),
			'TO' => $dates->getTo(),
		];
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
				$xmlId = $this->provider->getDictionary()->getOrderItemXmlId($item);
				$basketCode =
					$bitrixOrder->getBasketItemCode($xmlId, 'XML_ID')
					?: $bitrixOrder->getBasketItemCode($productId);

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
					'WEIGHT' => [
						'VALUE' => $shipment->getWeight(),
						'UNIT' => $shipment->getWeightUnit(),
					],
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
					'VIRTUAL' => !$shipment->hasSavedBoxes(),
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

	protected function getShipmentBoxCount(Market\Api\Model\Order $order)
	{
		if (!$order->hasDelivery()) { return 0; }

		$result = 0;

		/** @var Market\Api\Model\Order\Shipment $shipment */
		foreach ($order->getDelivery()->getShipments() as $shipment)
		{
			if (!$shipment->hasSavedBoxes()) { continue; }

			$boxes = $shipment->getBoxes();

			if ($boxes === null) { continue; }

			$result += $boxes->count();
		}

		return $result > 0 ? $result : null;
	}

	protected function isStatusReady(Market\Api\Model\Order $order)
	{
		return $this->isOrderProcessing($order) && $this->hasSavedBoxes($order);
	}

	protected function makeAllowedStatuses(Market\Api\Model\Order $order)
	{
		if (!$this->isOrderProcessing($order)) { return []; }

		$substatus = $order->isCancelRequested() ? TradingService\Marketplace\Status::STATE_SHOP_FAILED : $order->getSubStatus();
		$substatusOrder = $this->provider->getStatus()->getSubStatusProcessOrder();

		if (!isset($substatusOrder[$substatus]))
		{
			$result = array_keys($substatusOrder);
		}
		else
		{
			$currentOrder = $substatusOrder[$substatus];
			$result = [];

			foreach ($substatusOrder as $processSubstatus => $processOrder)
			{
				if (
					$processOrder === $currentOrder + 1
					|| (
						$processOrder > $currentOrder
						&& $processSubstatus === TradingService\Marketplace\Status::STATE_SHOP_FAILED
					)
				)
				{
					$result[] = $processSubstatus;
				}
			}
		}

		return $result;
	}

	protected function isCancelAllowed(Market\Api\Model\Order $order)
	{
		return
			$this->isOrderProcessing($order)
			&& $order->getSubStatus() !== TradingService\Marketplace\Status::STATE_SHIPPED
			&& !$order->isCancelRequested();
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