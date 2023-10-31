<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminView;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasLang;
	use Market\Reference\Concerns\HasOnce;
	use TradingService\Common\Concerns\Action\HasItemIdMatch;

	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var Request */
	protected $request;
	/** @var TradingService\Marketplace\Model\Order */
	protected $externalOrder;
	/** @var Market\Trading\Entity\Reference\Order */
	protected $bitrixOrder;

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
		$this->loadBitrixOrder();
		$this->checkViewAccess();

		$this->loadExternalOrder();

		$this->collectOrder();
		$this->collectWarnings();
		$this->collectProperties();
		$this->collectBuyer();
		$this->collectDelivery();
		$this->collectCourier();
		$this->collectBasketColumns();
		$this->collectBasketItems();
		$this->collectBasketSummary();
		$this->collectShipments();
		$this->collectOrderActions();
		$this->collectPrintReady();
	}

	protected function loadBitrixOrder()
	{
		$externalId = $this->request->getOrderId();
		$orderRegistry = $this->environment->getOrderRegistry();
		$platform = $this->getPlatform();
		$bitrixId = $orderRegistry->search($externalId, $platform, false);

		if ($bitrixId === null)
		{
			$message = static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_ORDER_NOT_EXISTS', [
				'#EXTERNAL_ID#' => $externalId,
			]);
			throw new Market\Exceptions\Trading\InvalidOperation($message);
		}

		$this->bitrixOrder = $orderRegistry->loadOrder($bitrixId);
	}

	protected function getOrder()
	{
		return $this->bitrixOrder;
	}

	protected function checkViewAccess()
	{
		if (!$this->hasRights(TradingEntity\Operation\Order::VIEW))
		{
			throw new Main\AccessDeniedException();
		}
	}

	protected function loadExternalOrder()
	{
		$orderId = $this->request->getOrderId();

		$this->flushCache();
		$this->fetchOrderByPrimary($orderId);
		$this->writeCache();
	}

	protected function getExternalOrder()
	{
		return $this->externalOrder;
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
			$logger = $this->provider->getLogger();
			$facadeClassName = $this->provider->getModelFactory()->getOrderFacadeClassName();

			$result = $facadeClassName::load($options, $primary, $logger);
		}

		$this->externalOrder = $result;
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
			Market\Trading\State\SessionCache::set(
				'order',
				$this->externalOrder->getId(),
				$this->externalOrder->getFields()
			);
		}
	}

	protected function collectOrder()
	{
		$this->response->setField('order', $this->getOrderRow());
	}

	protected function getOrderRow()
	{
		$statusService = $this->provider->getStatus();
		$tradingOptions = $this->provider->getOptions();

		return [
			'ID' => $this->externalOrder->getId(),
			'SERVICE_URL' => $this->externalOrder->getServiceUrl($tradingOptions),
			'ORDER_ID' => $this->bitrixOrder->getId(),
			'ACCOUNT_NUMBER' => $this->provider->getOptions()->useAccountNumberTemplate()
				? $this->bitrixOrder->getId()
				: $this->bitrixOrder->getAccountNumber(),
			'STATUS' => $this->externalOrder->getStatus(),
			'SUBSTATUS' => $this->externalOrder->getSubStatus(),
			'FAKE' => $this->externalOrder->isFake(),
			'PROCESSING' => $statusService->isProcessing($this->externalOrder->getStatus()),
			'EAC_TYPE' => $this->externalOrder->getDelivery()->getEacType(),
		];
	}

	protected function collectWarnings()
	{
		$prefix = $this->provider->getDictionary()->getErrorPrefix();
		$messages = $this->bitrixOrder->getMarkers($prefix);

		$this->response->setField('warnings', $messages);
	}

	protected function collectProperties()
	{
		foreach ($this->getPropertyFields() as $propertyName)
		{
			$propertyValue = $this->getPropertyValue($propertyName);

			if ($propertyValue === null) { continue; }

			$formattedValue = (string)$this->formatPropertyValue($propertyName, $propertyValue);

			if ($formattedValue === '') { continue; }

			$data = [
				'ID' => $propertyName,
				'NAME' => $this->getPropertyTitle($propertyName),
				'VALUE' => $formattedValue,
			];
			$data += $this->getPropertyData($propertyName);

			$this->response->pushField('properties', $data);
		}
	}

	protected function getPropertyFields()
	{
		$result = [
			'creationDate',
			'expiryDate',
			'shipmentDate',
			'fake',
			'cancelRequested',
			'status',
			'substatus',
			'paymentType',
			'paymentMethod',
			'electronicAcceptanceCertificate',
			'vehicleNumber',
			'notes',
		];

		if ($this->provider->getOptions()->useWarehouses())
		{
			array_splice($result, -4, 0, [
				'partnerWarehouse',
			]);
		}

		if (Market\Config::isExpertMode())
		{
			array_splice($result, -1, 0, [
				'taxSystem',
			]);
		}

		return $result;
	}

	protected function getPropertyTitle($propertyName)
	{
		$propertyNameUpper = Market\Data\TextString::toUpper($propertyName);

		return static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_PROPERTY_' . $propertyNameUpper, null, $propertyName);
	}

	protected function getPropertyValue($propertyName)
	{
		switch ($propertyName)
		{
			case 'creationDate':
				$result = $this->externalOrder->getCreationDate();
			break;

			case 'shipmentDate':
				$result = [];

				if ($this->externalOrder->hasDelivery())
				{
					/** @var Market\Api\Model\Order\Shipment $shipment */
					foreach ($this->externalOrder->getDelivery()->getShipments() as $shipment)
					{
						if (!$shipment->hasField('shipmentDate')) { continue; }

						$result[] = $shipment->getShipmentDate();
					}
				}
			break;

			case 'vehicleNumber':
				$result = Market\Trading\State\OrderData::getValue(
					$this->provider->getUniqueKey(),
					$this->externalOrder->getId(),
					'VEHICLE_NUMBER'
				);
			break;

			case 'partnerWarehouse':
				$result = $this->getPropertyPartnerWarehouseValue();
			break;

			default:
				$result = $this->externalOrder->getField($propertyName);
			break;
		}

		return $result;
	}

	protected function getPropertyPartnerWarehouseValue()
	{
		try
		{
			/** @var TradingService\Marketplace\Model\Order\Item $item */
			$item = $this->externalOrder->getItems()->offsetGet(0);

			if ($item === null) { return null; }

			$field = $this->provider->getOptions()->getWarehouseStoreField();
			$warehouseId = $item->getPartnerWarehouseId();
			$storeService = $this->environment->getStore();
			$stores = $storeService->findStores($field, $warehouseId);

			if (empty($stores)) { return null; }

			$storesMap = array_flip($stores);
			$result = [];

			foreach ($storeService->getEnum() as $storeOption)
			{
				if (!isset($storesMap[$storeOption['ID']])) { continue; }

				$result[] = $storeOption['VALUE'];
			}
		}
		catch (Market\Exceptions\Api\ObjectPropertyException $exception)
		{
			$result = null;
		}

		return $result;
	}

	protected function formatPropertyValue($propertyName, $propertyValue)
	{
		switch ($propertyName)
		{
			case 'fake':
			case 'cancelRequested':
				$result = (int)$propertyValue > 0
					? static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_BOOLEAN_YES')
					: static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_BOOLEAN_NO');
				break;

			case 'status':
			case 'substatus':
				$result = $this->provider->getStatus()->getTitle($propertyValue);
			break;

			case 'taxSystem':
				$result = $this->provider->getTaxSystem()->getTypeTitle($propertyValue);
			break;

			case 'paymentType':
				$result = $this->provider->getPaySystem()->getTypeTitle($propertyValue);
			break;

			case 'paymentMethod':
				$result = $this->provider->getPaySystem()->getMethodTitle($propertyValue);
			break;

			default:
				$result = is_array($propertyValue) ? implode(', ', $propertyValue) : $propertyValue;
			break;
		}

		return $result;
	}

	protected function getPropertyData($propertyName)
	{
		return [];
	}

	protected function collectBuyer()
	{
		$activities = $this->getBuyerActivities();
		$buyerFields = $this->getBuyerFields();

		foreach ($buyerFields as $name)
		{
			$value = $this->getBuyerValue($name);
			$activity = isset($activities[$name]) ? $activities[$name] : null;
			$valid = false;
			$formatted = '';

			if ($activity !== null) { $valid = true; }

			if ($value !== null)
			{
				$formatted = (string)$this->formatBuyerValue($name, $value);
				$valid = ($valid || $formatted !== '');
			}

			if (!$valid) { continue; }

			$this->response->pushField('buyer', [
				'ID' => $name,
				'NAME' => $this->getBuyerTitle($name),
				'VALUE' => $formatted,
				'ACTIVITY' => $activity,
			]);
		}
	}

	protected function getBuyerFields()
	{
		return [
			'type',
		];
	}

	protected function getBuyerActivities()
	{
		return [];
	}

	protected function getBuyerValue($name)
	{
		$actionMethod = 'getBuyer' . Market\Data\TextString::ucfirst($name) . 'Value';
		$getMethod = 'get' . Market\Data\TextString::ucfirst($name);
		$buyer = $this->externalOrder->getBuyer();

		if ($buyer === null) { return null; }

		if (method_exists($this, $actionMethod))
		{
			$result = $this->{$actionMethod}($buyer);
		}
		else if (method_exists($buyer, $getMethod))
		{
			$result = $buyer->{$getMethod}();
		}
		else
		{
			$result = $buyer->getField($name);
		}

		return $result;
	}

	protected function formatBuyerValue($name, $value)
	{
		$actionMethod = 'formatBuyer' . Market\Data\TextString::ucfirst($name) . 'Value';
		$buyer = $this->externalOrder->getBuyer();

		if (method_exists($this, $actionMethod))
		{
			$result = $this->{$actionMethod}($buyer, $value);
		}
		else
		{
			$result = is_array($value) ? implode(', ', $value) : (string)$value;
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function formatBuyerTypeValue(TradingService\Marketplace\Model\Order\Buyer $buyer, $type)
	{
		return $buyer::getTypeTitle($type);
	}

	protected function getBuyerTitle($name)
	{
		$nameUpper = Market\Data\TextString::toUpper($name);

		return static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_BUYER_' . $nameUpper, null, $name);
	}

	protected function collectDelivery()
	{
		if (!$this->externalOrder->hasDelivery()) { return; }

		$activities = $this->getDeliveryActivities();

		foreach ($this->getDeliveryFields() as $name)
		{
			$value = $this->getDeliveryValue($name);
			$activity = isset($activities[$name]) ? $activities[$name] : null;

			if ($value === null) { continue; }

			$formatted = (string)$this->formatDeliveryValue($name, $value);

			if ($formatted === '') { continue; }

			$data = [
				'ID' => $name,
				'NAME' => $this->getDeliveryTitle($name),
				'VALUE' => $formatted,
				'ACTIVITY' => $activity,
			];
			$data += $this->getDeliveryData($name);

			$this->response->pushField('delivery', $data);
		}
	}

	protected function getDeliveryFields()
	{
		return [
			'trackCode',
			'dates',
			'type',
			'region',
			'eacType',
			'eacCode',
		];
	}

	protected function getDeliveryActivities()
	{
		return [
			'eacType' => 'send/verifyEac',
		];
	}

	protected function getDeliveryValue($name)
	{
		$actionMethod = 'getDelivery' . Market\Data\TextString::ucfirst($name) . 'Value';
		$getMethod = 'get' . Market\Data\TextString::ucfirst($name);
		$delivery = $this->externalOrder->getDelivery();

		if (method_exists($this, $actionMethod))
		{
			$result = $this->{$actionMethod}($delivery);
		}
		else if (method_exists($delivery, $getMethod))
		{
			$result = $delivery->{$getMethod}();
		}
		else
		{
			$result = $delivery->getField($name);
		}

		return $result;
	}

	protected function formatDeliveryValue($name, $value)
	{
		$actionMethod = 'formatDelivery' . Market\Data\TextString::ucfirst($name) . 'Value';
		$delivery = $this->externalOrder->getDelivery();

		if (method_exists($this, $actionMethod))
		{
			$result = $this->{$actionMethod}($delivery, $value);
		}
		else
		{
			$result = is_array($value) ? implode(', ', $value) : (string)$value;
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function getDeliveryTrackCodeValue(Market\Api\Model\Order\Delivery $delivery)
	{
		$tracks = $delivery->getTracks();

		if ($tracks === null) { return null; }

		$result = [];

		/** @var Market\Api\Model\Order\Track $track */
		foreach ($tracks as $track)
		{
			$result[] = (string)$track->getTrackCode();
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function formatDeliveryPriceValue(Market\Api\Model\Order\Delivery $delivery, $price)
	{
		$vat = (string)$delivery->getVat();

		$result = Market\Data\Currency::format(
			$price,
			$this->externalOrder->getCurrency()
		);

		if ($vat !== '')
		{
			$result .= sprintf(' (%s)', Market\Data\Vat::getTitle($vat));
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function formatDeliveryDatesValue(Market\Api\Model\Order\Delivery $delivery, Market\Api\Model\Order\Dates $dates)
	{
		$period = array_filter([
			$dates->getFrom(),
			$dates->getTo(),
		]);
		$datesFormatted = array_map(static function(Main\Type\Date $date) { return Market\Data\Date::format($date); }, $period);
		$datesUnique = array_unique($datesFormatted);
		$timesFormatted = array_map(static function(Main\Type\Date $date) { return $date->format('H:i'); }, $period);
		$timesUnique = array_unique($timesFormatted);
		$useTime = (
			count($timesUnique) > 1
			|| (count($timesUnique) === 1 && reset($timesUnique) !== '00:00')
		);

		if (count($datesUnique) === 1)
		{
			$result =
				reset($datesUnique)
				. ($useTime ? ' ' . implode('-', $timesUnique) : '');
		}
		else
		{
			$parts = [];

			foreach ($datesFormatted as $key => $dateFormatted)
			{
				$timeFormatted = $timesFormatted[$key];

				$parts[] =
					$dateFormatted
					. ($useTime ? ' ' . $timeFormatted : '');
			}

			$result = implode(' - ', $parts);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function formatDeliveryTypeValue(Market\Api\Model\Order\Delivery $delivery, $type)
	{
		return $this->provider->getDelivery()->getTypeTitle($type);
	}

	/** @noinspection PhpUnused */
	protected function formatDeliveryRegionValue(Market\Api\Model\Order\Delivery $delivery, Market\Api\Model\Region $region)
	{
		$parts = [];
		$level = $region;

		do
		{
			$parts[] = $level->getName();
			$level = $level->getParent();
		}
		while ($level !== null);

		return implode(', ', $parts);
	}

	/** @noinspection PhpUnused */
	protected function formatDeliveryEacTypeValue(Market\Api\Model\Order\Delivery $delivery, $type)
	{
		return static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_DELIVERY_EACTYPE_' . $type, null, $type);
	}

	protected function getDeliveryTitle($name)
	{
		$nameUpper = Market\Data\TextString::toUpper($name);

		return static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_DELIVERY_' . $nameUpper, null, $name);
	}

	protected function getDeliveryData($name)
	{
		return [];
	}

	protected function collectCourier()
	{
		if (!$this->externalOrder->hasDelivery()) { return; }

		$courier = $this->externalOrder->getDelivery()->getCourier();

		if ($courier === null) { return; }

		foreach ($courier->getMeaningfulValues() as $name => $value)
		{
			if (Market\Utils\Value::isEmpty($value)) { continue; }

			$this->response->pushField('courier', [
				'ID' => $name,
				'NAME' => TradingService\Marketplace\Model\Order\Delivery\Courier::getMeaningfulFieldTitle($name),
				'VALUE' => $value,
			]);
		}
	}

	protected function collectBasketColumns()
	{
		$columns = [];

		foreach ($this->getBasketColumns() as $column)
		{
			$columns[$column] = static::getLang('TRADING_MARKETPLACE_ORDER_VIEW_BASKET_' . $column);
		}

		$this->response->setField('basket.columns', $columns);
	}

	protected function getBasketColumns()
	{
		$result = [
			'NAME',
			'PRICE',
			'SUBSIDY',
			'COUNT',
		];

		if ($this->provider->getFeature()->supportsCis())
		{
			array_splice($result, 1, 0, [ 'CIS' ]);
		}

		if (Market\Config::isExpertMode())
		{
			$result[] = 'VAT';
		}

		return $result;
	}

	protected function collectBasketItems()
	{
		$items = $this->externalOrder->getItems();
		$basketMap = $this->getBasketMap($items);
		$currency = $this->externalOrder->getCurrency();
		$isConfirmed = $this->isOrderConfirmed();

		/** @var TradingService\Marketplace\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$itemId = $item->getId();
			$instances = $item->getInstances();
			$basketCode = isset($basketMap[$itemId]) ? $basketMap[$itemId] : null;
			$basketData = $basketCode !== null
				? $this->bitrixOrder->getBasketItemData($basketCode)->getData()
				: [];

			$itemData = [
				'ID' => $itemId,
				'BASKET_CODE' => $basketCode,
				'OFFER_ID' => $item->getOfferId(),
				'NAME' => $item->getOfferName(),
				'COUNT' => $item->getCount(),
				'PRICE' => null,
				'PRICE_FORMATTED' => null,
				'CURRENCY' => $currency,
				'SUBSIDY' => null,
				'SUBSIDY_FORMATTED' => null,
				'VAT' => $item->getVat(),
				'VAT_FORMATTED' => Market\Data\Vat::getTitle($item->getVat()),
				'MARKING_GROUP' => isset($basketData['MARKING_GROUP']) && (string)$basketData['MARKING_GROUP'] !== ''
					? $basketData['MARKING_GROUP']
					: null,
				'MARKING_TYPE' => isset($basketData['MARKING_TYPE']) && (string)$basketData['MARKING_TYPE'] !== ''
					? $basketData['MARKING_TYPE']
					: null,
				'PROMOS' => [],
				'INSTANCES' => $instances !== null ? $this->getItemInstancesSummary($instances) : [],
				'INSTANCE_TYPES' => $item->getRequiredInstanceTypes(),
				'INTERNAL_INSTANCES' => isset($basketData['INSTANCES']) ? $basketData['INSTANCES'] : [],
			];

			if ($isConfirmed)
			{
				$itemData['PRICE'] = $item->getPrice();
				$itemData['PRICE_FORMATTED'] = Market\Data\Currency::format($item->getPrice(), $currency);

				$subsidy = $item->getSubsidy();

				if ($subsidy > 0)
				{
					$itemData['SUBSIDY'] = $subsidy;
					$itemData['SUBSIDY_FORMATTED'] = Market\Data\Currency::format($subsidy, $currency);
				}

				$promos = $item->getPromos();

				if ($promos !== null)
				{
					$itemData['PROMOS'] = $this->getItemPromosSummary($promos);
				}
			}

			$itemData = $this->applyBasketItemRules($itemData);

			$this->response->pushField('basket.items', $itemData);
		}
	}

	protected function getBasketMap(Market\Api\Model\Cart\ItemCollection $items)
	{
		if (!($items instanceof Market\Api\Model\Order\ItemCollection)) { return []; }

		return $this->once('getBasketMap', $items, function(Market\Api\Model\Order\ItemCollection $items) {
			return $this->getExternalItemsBasketMap($items);
		});
	}

	/** @deprecated */
	protected function getBasketMapByXmlId(Market\Api\Model\Cart\ItemCollection $items)
	{
		$dictionary = $this->provider->getDictionary();
		$result = [];

		/** @var TradingService\Marketplace\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$xmlId = $dictionary->getOrderItemXmlId($item);

			if ($xmlId === null) { continue; }

			$basketCode = $this->bitrixOrder->getBasketItemCode($xmlId, 'XML_ID');

			if ($basketCode !== null)
			{
				$result[$item->getId()] = $basketCode;
			}
		}

		return $result;
	}

	/** @deprecated */
	protected function getBasketMapByProductId(Market\Api\Model\Cart\ItemCollection $items)
	{
		if (!($items instanceof Market\Api\Model\Order\ItemCollection)) { return []; }

		$offerMap = $this->getOfferMap($items);
		$result = [];

		/** @var TradingService\Marketplace\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$productId = $this->getProductId($item->getOfferId(), $offerMap);

			if ($productId === null) { continue; }

			$basketCode = $this->bitrixOrder->getBasketItemCode($productId);

			if ($basketCode !== null)
			{
				$result[$item->getId()] = $basketCode;
			}
		}

		return $result;
	}

	/** @deprecated */
	protected function getProductId($offerId, $offerMap)
	{
		$result = null;

		if ($offerMap === null)
		{
			$result = $offerId;
		}
		else if (isset($offerMap[$offerId]))
		{
			$result = $offerMap[$offerId];
		}

		return $result;
	}

	protected function getItemPromosSummary(TradingService\Marketplace\Model\Order\Item\PromoCollection $promoCollection)
	{
		$promoEntity = $this->provider->getPromo();
		$visibleTypes = $promoEntity->getVisibleTypes();
		$result = [];

		/** @var TradingService\Marketplace\Model\Order\Item\Promo $promo*/
		foreach ($promoCollection as $promo)
		{
			if (!in_array($promo->getType(), $visibleTypes, true)) { continue; }

			$type = $promo->getType();
			$shopPromoId = $promo->getShopPromoId();

			$promoText = $promoEntity->getTitle($type);

			if ($shopPromoId !== null)
			{
				$promoText .= sprintf(' #%s', $shopPromoId);
			}

			$result[] = $promoText;
		}

		return $result;
	}

	protected function getItemInstancesSummary(TradingService\Marketplace\Model\Order\Item\InstanceCollection $instanceCollection)
	{
		$result = [];

		/** @var TradingService\Marketplace\Model\Order\Item\Instance $instance */
		foreach ($instanceCollection as $instance)
		{
			$result[] = [
				'CIS' => $instance->getCisFull() ?: $instance->getCis(),
				'UIN' => $instance->getUin(),
				'RNPT' => $instance->getRnpt(),
				'GTD' => $instance->getGtd(),
			];
		}

		return $result;
	}

	protected function applyBasketItemRules(array $itemData)
	{
		$itemData = $this->applyBasketItemInstanceRules($itemData);

		return $itemData;
	}

	protected function applyBasketItemInstanceRules(array $itemData)
	{
		if (
			isset($itemData['MARKING_GROUP'])
			&& empty($itemData['INSTANCES'])
			&& !empty($itemData['INTERNAL_INSTANCES'])
		)
		{
			$itemData['INSTANCES'] = $itemData['INTERNAL_INSTANCES'];
		}

		return $itemData;
	}

	protected function collectBasketSummary()
	{
		if (!$this->isOrderConfirmed())
		{
			$this->response->setField('basket.summary', []);
			return;
		}

		$isOrderPaid = $this->isOrderPaid();

		foreach ($this->getBasketSummaryValues() as $key => $value)
		{
			$langKey = 'TRADING_MARKETPLACE_ORDER_VIEW_BASKET_SUMMARY_' . $key;
			$title = $isOrderPaid ? static::getLang($langKey . '_PAID', null, '') : '';

			if (!$title)
			{
				$title = static::getLang($langKey, null, $key);
			}

			$this->response->pushField('basket.summary', [
				'NAME' => $title,
				'VALUE' => $value,
			]);
		}
	}

	protected function getBasketSummaryValues()
	{
		$currency = $this->externalOrder->getCurrency();
		$values = [];
		$itemsTotal = $this->externalOrder->getItemsTotal();
		$subsidyTotal = $this->externalOrder->getSubsidyTotal();

		if ($this->externalOrder->getSubsidyTotal() > 0)
		{
			$itemsTotalWithSubsidy = $itemsTotal + $subsidyTotal;

			$values['TOTAL'] = Market\Data\Currency::format($itemsTotalWithSubsidy, $currency);
			$values['SUBSIDY_TOTAL'] = Market\Data\Currency::format($subsidyTotal, $currency);
			$values['ITEMS_TOTAL'] = Market\Data\Currency::format($itemsTotal, $currency);
		}
		else
		{
			$values['ITEMS_TOTAL'] = Market\Data\Currency::format($itemsTotal, $currency);
		}

		return $values;
	}

	protected function collectShipments()
	{
		$delivery = $this->externalOrder->getDelivery();

		foreach ($delivery->getShipments() as $shipment)
		{
			$this->response->pushField('shipments', [
				'ID' => $shipment->getId(),
				'BOX' => $this->getShipmentBoxes($shipment),
			]);
		}
	}

	protected function getShipmentBoxes(Market\Api\Model\Order\Shipment $shipment)
	{
		$boxes = $shipment->getBoxes();

		if ($boxes === null || $boxes->count() === 0) { return $this->emulateShipmentBoxes(); }

		$result = [];

		/** @var Market\Api\Model\Order\Box $box*/
		foreach ($boxes as $box)
		{
			if ($boxes->count() === 1)
			{
				$this->copyShipmentBoxDefaults($box, $shipment);
			}

			$result[] = [
				'ID' => $box->getId(),
				'DIMENSIONS' => $this->getShipmentBoxDimensions($box),
			];
		}

		return $result;
	}

	protected function emulateShipmentBoxes()
	{
		$count = max(1, (int)Market\Trading\State\OrderData::getValue(
			$this->provider->getUniqueKey(),
			$this->request->getOrderId(),
			'BOX_COUNT'
		));
		$dimensionsEncoded = (string)Market\Trading\State\OrderData::getValue(
			$this->provider->getUniqueKey(),
			$this->request->getOrderId(),
			'BOX_DIMENSIONS'
		);

		$result = array_fill(0, $count, [
			'ID' => null,
			'DIMENSIONS' => [],
		]);

		if ($dimensionsEncoded === '') { return $result; }

		$dimensions = unserialize($dimensionsEncoded, [ 'allowed_classes' => false ]);

		if (!is_array($dimensions)) { return $result; }

		foreach ($dimensions as $index => $dimension)
		{
			if (!isset($result[$index])) { continue; }

			$emulatedBox = new Market\Api\Model\Order\Box($dimension);

			$result[$index]['DIMENSIONS'] = $this->getShipmentBoxDimensions($emulatedBox);
		}

		return $result;
	}

	protected function copyShipmentBoxDefaults(Market\Api\Model\Order\Box $box, Market\Api\Model\Order\Shipment $shipment)
	{
		$defaults = array_intersect_key($shipment->getFields(), [
			'weight' => true,
			'width' => true,
			'height' => true,
			'depth' => true,
		]);

		foreach ($defaults as $size => $default)
		{
			$value = $box->getField($size);

			if ($value !== null) { continue; }

			$box->setField($size, $default);
		}
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

	protected function collectOrderActions()
	{
		$actions = array_filter([
			TradingEntity\Operation\Order::ITEM => ($this->isOrderProcessing() && !$this->isOrderReadyToShip()),
			TradingEntity\Operation\Order::BOX => ($this->isOrderProcessing() && !$this->isOrderShipped()),
			TradingEntity\Operation\Order::CIS => ($this->isOrderProcessing() && !$this->isOrderShipped()),
		]);
		$actions = $this->filterOrderActionsByAccess($actions);

		$this->response->setField('orderActions', $actions);
	}

	protected function filterOrderActionsByAccess(array $actions)
	{
		foreach ($actions as $action => $dummy)
		{
			if (!$this->hasRights($action))
			{
				unset($actions[$action]);
			}
		}

		return $actions;
	}

	protected function collectPrintReady()
	{
		$result = $this->isOrderProcessing();

		$this->response->setField('printReady', $result);
	}

	protected function isPaymentPrepaid()
	{
		$paySystemService = $this->provider->getPaySystem();
		$type = $this->externalOrder->getPaymentType();

		return $paySystemService->isPrepaid($type);
	}

	protected function isOrderPaid()
	{
		return $this->isPaymentPrepaid()
			? $this->isOrderConfirmed()
			: $this->isOrderDelivered();
	}

	protected function isOrderDelivered()
	{
		$status = $this->externalOrder->getStatus();

		return $this->provider->getStatus()->isOrderDelivered($status);
	}

	protected function isOrderConfirmed()
	{
		$status = $this->externalOrder->getStatus();

		return $this->provider->getStatus()->isConfirmed($status);
	}

	protected function isOrderShipped()
	{
		$status = $this->externalOrder->getStatus();
		$subStatus = $this->externalOrder->getSubStatus();

		return $this->provider->getStatus()->isShipped($status, $subStatus);
	}

	protected function isOrderProcessing()
	{
		$status = $this->externalOrder->getStatus();

		return $this->provider->getStatus()->isProcessing($status);
	}

	protected function isOrderReadyToShip()
	{
		$statusService = $this->provider->getStatus();
		$subStatus = $this->externalOrder->getSubStatus();
		$subStatusOrder = $statusService->getSubStatusOrder($subStatus);

		return (
			$this->isOrderProcessing()
			&& $subStatusOrder >= $statusService->getSubStatusOrder(TradingService\Marketplace\Status::STATE_READY_TO_SHIP)
		);
	}

	protected function hasRights($operation)
	{
		if (!$this->request->needCheckAccess())
		{
			$result = true;
		}
		else
		{
			$userId = $this->request->getUserId();
			$result = $this->bitrixOrder->hasAccess($userId, $operation);
		}

		return $result;
	}
}