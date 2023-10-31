<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Yandex\Market\Trading\Entity\Reference as EntityReference;
use Yandex\Market\Trading\Entity\Operation as EntityOperation;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Catalog;

class Order extends Market\Trading\Entity\Reference\Order
{
	use Market\Reference\Concerns\HasLang;

	const PAYMENT_SUBSIDY_PREFIX = 'MARKET_SUBSIDY_';

	/** @var int|null */
	protected $eventProcessing;
	/** @var array */
	protected $initialChangedValues;
	/** @var bool|null*/
	protected $isStartField;
	/** @var Environment */
	protected $environment;
	/** @var Sale\OrderBase */
	protected $internalOrder;
	/** @var Sale\OrderBase */
	protected $calculatable;
	/** @var Internals\BasketDataPreserver */
	protected $basketDataPreserver;
	/** @var Internals\AccountNumberSetter */
	protected $accountNumberSetter;
	/** @var int|null */
	protected $tradingSetupId;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Environment $environment, Sale\OrderBase $internalOrder, $eventProcessing = null)
	{
		parent::__construct($environment, $internalOrder);

		$this->eventProcessing = $eventProcessing;
		$this->initialChangedValues = $eventProcessing !== null ? $internalOrder->getFields()->getChangedValues() : [];
	}

	public function getAdminEditUrl()
	{
		return Market\Ui\Admin\Path::getPageUrl('sale_order_view', [
			'ID' => $this->getId(),
			'lang' => LANGUAGE_ID,
			'sale_order_view_active_tab' => 'YANDEX_MARKET_TRADING_ORDER_VIEW',
		]);
	}

	public function hasAccess($userId, $operation)
	{
		switch ($operation)
		{
			case Market\Trading\Entity\Operation\Order::VIEW:
				$result = $this->hasStatusRights($userId, 'view');
			break;

			case Market\Trading\Entity\Operation\Order::BOX:
			case Market\Trading\Entity\Operation\Order::CIS:
			case Market\Trading\Entity\Operation\Order::DIGITAL:
				$result =
					$this->hasStatusRights($userId, 'update')
					|| $this->hasShipmentRights($userId, ['update', 'delivery', 'deduction']);
			break;

			case Market\Trading\Entity\Operation\Order::ITEM:
				$result = $this->hasStatusRights($userId, 'update');
			break;

			default:
				throw new Main\ArgumentException('unknown operation');
			break;
		}

		return $result;
	}

	protected function hasStatusRights($userId, $saleActions)
	{
		/** @var OrderRegistry $orderRegistry */
		$orderRegistry = $this->environment->getOrderRegistry();
		$statusClass = $orderRegistry::getOrderStatusClassName();
		$status = $this->internalOrder->getField('STATUS_ID');
		$result = false;

		foreach ((array)$saleActions as $saleAction)
		{
			$allowedStatuses = $statusClass::getStatusesUserCanDoOperations($userId, [ $saleAction ]);

			if (in_array($status, $allowedStatuses, true))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function hasShipmentRights($userId, $saleActions)
	{
		/** @var OrderRegistry $orderRegistry */
		$shipment = $this->getNotSystemShipment();

		if ($shipment !== null)
		{
			$orderRegistry = $this->environment->getOrderRegistry();
			$statusClass = $orderRegistry::getDeliveryStatusClassName();
			$status = $shipment->getField('STATUS_ID');
			$result = false;

			foreach ((array)$saleActions as $saleAction)
			{
				$allowedStatuses = $statusClass::getStatusesUserCanDoOperations($userId, [ $saleAction ]);

				if (in_array($status, $allowedStatuses, true))
				{
					$result = true;
					break;
				}
			}
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	public function getId()
	{
		return $this->internalOrder->getId();
	}

	public function getUserId()
	{
		$userId = (int)$this->internalOrder->getUserId();

		return $userId > 0 ? $userId : null;
	}

	public function getAccountNumber()
	{
		return OrderRegistry::getOrderAccountNumber($this->internalOrder);
	}

	public function getCreationDate()
	{
		return $this->internalOrder->getDateInsert();
	}

	public function getCurrency()
	{
		return $this->internalOrder->getCurrency();
	}

	public function getReasonCanceled()
	{
		return (string)$this->internalOrder->getField('REASON_CANCELED');
	}

	public function getProfileName()
	{
		$property = $this->internalOrder->getPropertyCollection()->getProfileName();

		return $property !== null ? $property->getValue() : null;
	}

	public function getPropertyValues()
	{
		$propertyCollection = $this->internalOrder->getPropertyCollection();
		$result = [];

		/** @var Sale\PropertyValue $property */
		foreach ($propertyCollection as $property)
		{
			$propertyId = $property->getField('ORDER_PROPS_ID');

			$result[$propertyId] = $property->getValue();
		}

		return $result;
	}

	public function getPropertyValue($propertyId)
	{
		$propertyCollection = $this->internalOrder->getPropertyCollection();
		$property = $propertyCollection->getItemByOrderPropertyId($propertyId);

		return $property !== null ? $property->getValue() : null;
	}

	public function getSiteId()
	{
		return $this->internalOrder->getSiteId();
	}

	public function setUserId($userId)
	{
		$userId = (int)$userId;
		$result = new Main\Result();

		if ($userId <= 0)
		{
			$result->addError(new Main\Error('cant set empty userId'));
		}
		else
		{
			$basket = $this->internalOrder->getBasket();

			$this->internalOrder->setFieldNoDemand('USER_ID', $userId);

			if ($basket && $this->internalOrder->isNew())
			{
				$fuserId = Sale\Fuser::getIdByUserId($userId);
				$basket->setFUserId($fuserId);
			}
		}

		return $result;
	}

	public function getPersonType()
	{
		return $this->internalOrder->getPersonTypeId();
	}

	public function setPersonType($personType)
	{
		return $this->internalOrder->setPersonTypeId($personType);
	}

	public function initialize()
	{
		Sale\DiscountCouponsManager::init(Sale\DiscountCouponsManager::MODE_EXTERNAL);

		$this->freeze();
		$this->getBasket(); // initialize basket (fix clear shipmentCollection)
	}

	public function fillAccountNumber($accountNumber)
	{
		$this->accountNumberSetter = new Internals\AccountNumberSetter($this->internalOrder, $accountNumber);
		$this->accountNumberSetter->install();
	}

	public function fillXmlId($externalId, EntityReference\Platform $platform)
	{
		$xmlId = $platform->getOrderXmlId($externalId);

		$this->internalOrder->setField('XML_ID', $xmlId);
		$this->updateTradeBinding($externalId, $platform);
	}

	public function fillTradingSetup($setupId, EntityReference\Platform $platform)
	{
		$suffix = $platform->getOrderXmlIdSuffix($setupId);
		$xmlId = $this->internalOrder->getField('XML_ID');

		$this->tradingSetupId = $setupId;
		$this->internalOrder->setField('XML_ID', $xmlId . $suffix);
		$this->linkTradeBinding($setupId, $platform);
	}

	public function fillProperties(array $values, $onlyEmpty = false)
	{
		$propertyCollection = $this->internalOrder->getPropertyCollection();
		$result = new Main\Result();
		$changes = [];
		$filled = [];

		/** @var Sale\PropertyValue $property*/
		foreach ($propertyCollection as $property)
		{
			$propertyId = $property->getPropertyId();

			if ($propertyId === null || !array_key_exists($propertyId, $values)) { continue; }
			if ($onlyEmpty && !Market\Utils\Value::isEmpty($property->getValue())) { continue; }

			$value = $values[$propertyId];
			$sanitizedValue = $this->sanitizePropertyValue($property, $value);

			$property->setValue($sanitizedValue);

			if ($property->isChanged())
			{
				$changes[] = $propertyId;

				Listener::addInternalChange(
					$this->internalOrder->getId(),
					sprintf('PROPERTY_%s.VALUE', $propertyId),
					$property->getValue()
				);
			}

			$filled[] = $propertyId;
		}

		$result->setData([
			'CHANGES' => $changes,
			'FILLED' => $filled,
		]);

		return $result;
	}

	protected function sanitizePropertyValue(Sale\PropertyValue $property, $value)
	{
		$value = $this->sanitizePropertyValueByType($property, $value);
		$value = $this->sanitizePropertyValueMultiple($property, $value);
		$value = $this->sanitizePropertyValueOptions($property, $value);

		return $value;
	}

	protected function sanitizePropertyValueByType(Sale\PropertyValue $property, $value)
	{
		$isValueMultiple = is_array($value);
		$sanitizeValues = $isValueMultiple ? $value : [ $value ];
		$propertyFields = $property->getProperty();
		$propertyType = isset($propertyFields['TYPE']) ? $propertyFields['TYPE'] : null;

		foreach ($sanitizeValues as &$sanitizeValue)
		{
			// value

			if ($sanitizeValue instanceof Main\Type\DateTime)
			{
				$sanitizeValue = ConvertTimeStamp($sanitizeValue->getTimestamp(), 'FULL', $this->internalOrder->getSiteId());
			}
			else if ($sanitizeValue instanceof Main\Type\Date)
			{
				$sanitizeValue = ConvertTimeStamp($sanitizeValue->getTimestamp(), 'SHORT', $this->internalOrder->getSiteId());
			}
			else if ($value instanceof Market\Data\Type\EnumValue)
			{
				$propertyData = $property->getProperty();
				$propertyType = isset($propertyData['TYPE']) ? $propertyData['TYPE'] : 'STRING';

				$sanitizeValue = $propertyType === 'ENUM' ? $value->code : (string)$value;
			}

			// property

			if ($propertyType === 'NUMBER' && is_string($sanitizeValue) && preg_match('/^\s*(\d+)(?:[.,](\d+))?(\s|$)/', $sanitizeValue, $matches))
			{
				$sanitizeValue = (float)($matches[1] . (isset($matches[2]) && $matches[2] !== '' ? '.' . $matches[2] : ''));
			}
		}
		unset($sanitizeValue);

		return $isValueMultiple ? $sanitizeValues : reset($sanitizeValues);
	}

	protected function sanitizePropertyValueMultiple(Sale\PropertyValue $property, $value)
	{
		$propertyRow = $property->getProperty();
		$isPropertyMultiple = (isset($propertyRow['MULTIPLE']) && $propertyRow['MULTIPLE'] === 'Y');
		$isValueMultiple = is_array($value);

		if ($isPropertyMultiple === $isValueMultiple)
		{
			$result = $value;
		}
		else if ($isValueMultiple)
		{
			$result = $this->environment->getProperty()->joinPropertyMultipleValue($property, $value);
		}
		else if (!Market\Utils\Value::isEmpty($value))
		{
			$result = [ $value ];
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function sanitizePropertyValueOptions(Sale\PropertyValue $property, $value)
	{
		$propertyRow = $property->getProperty();

		if (!isset($propertyRow['TYPE']) || $propertyRow['TYPE'] !== 'ENUM') { return $value; }

		$options = $this->getPropertyValueOptions($property);

		if (isset($options[$value]))
		{
			$result = $value;
		}
		else
		{
			$key = array_search($value, $options, true);
			$result = $key !== false ? $key : null;
		}

		return $result;
	}

	protected function getPropertyValueOptions(Sale\PropertyValue $property)
	{
		$propertyRow = $property->getProperty();

		return method_exists($property, 'getPropertyObject')
			? $property->getPropertyObject()->getOptions()
			: Sale\PropertyValue::loadOptions($propertyRow['ID']);
	}

	public function resetLocation()
	{
		return $this->setLocationPropertyValue(null);
	}

	public function setLocation($locationId)
	{
		$locationCode = \CSaleLocation::getLocationCODEbyID($locationId);

		return $this->setLocationPropertyValue($locationCode);
	}

	protected function setLocationPropertyValue($locationCode = null)
	{
		$propertyCollection = $this->internalOrder->getPropertyCollection();
		$locationProperty = $propertyCollection->getDeliveryLocation();
		$result = new Main\Result();

		if ($locationProperty === null)
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_ORDER_HASNT_LOCATION_PROPERTY');
			$result->addError(new Main\Error($errorMessage));

			return $result;
		}

		$locationProperty->setValue($locationCode);

		return $result;
	}

	public function addProduct($productId, $count = 1, array $data = null)
	{
		$basket = $this->getBasket();
		$basketFields = $this->getProductBasketFields($productId, $count, $data);

		$result = $this->createBasketItem($basket, $basketFields);
		$addData = $result->getData();

		if (isset($addData['BASKET_ITEM']))
		{
			/** @var Sale\BasketItemBase $basketItem */
			$basketItem = $addData['BASKET_ITEM'];
			$basketCode = $basketItem->getBasketCode();
			$addData['BASKET_CODE'] = $basketCode;

			$result->setData($addData);

			if (!empty($data))
			{
				$basketHandler = $this->getBasketDataPreserver();
				$basketHandler->preserve($basketCode, $data);
			}
		}

		return $result;
	}

	protected function getProductBasketFields($productId, $count = 1, array $data = null)
	{
		$result = [
			'PRODUCT_ID' => $productId,
			'QUANTITY' => $count,
			'CURRENCY' => $this->internalOrder->getCurrency(), // required for bitrix17
			'MODULE' => 'catalog',
			'PRODUCT_PROVIDER_CLASS' => $this->getProductDefaultProvider(),
		];

		if ($data !== null)
		{
			$result = $data + $result; // user data priority
		}

		return $result;
	}

	protected function getProductDefaultProvider()
	{
		if (method_exists(Catalog\Product\Basket::class, 'getDefaultProviderName'))
		{
			$result = Catalog\Product\Basket::getDefaultProviderName();
		}
		else
		{
			$result = 'CCatalogProductProvider';
		}

		return $result;
	}

	protected function createBasketItem(Sale\BasketBase $basket, $basketFields)
	{
		/** @var Sale\BasketItemBase $basketItem */
		$result = new Main\Result();
		$basketItem = $basket->createItem($basketFields['MODULE'], $basketFields['PRODUCT_ID']);
		$settableFieldsMap = array_flip($basketItem::getSettableFields());
		$alreadySetFields = [
			'MODULE' => true,
			'PRODUCT_ID' => true,
		];

		// apply limits

		if (isset($basketFields['AVAILABLE_QUANTITY']))
		{
			$siblingsQuantity = $this->getBasketProductSiblingsFilledQuantity($basketItem);

			if ($siblingsQuantity > 0)
			{
				$basketFields['AVAILABLE_QUANTITY'] -= $siblingsQuantity;
			}

			if ($basketFields['AVAILABLE_QUANTITY'] < $basketFields['QUANTITY'])
			{
				$basketFields['QUANTITY'] = $basketFields['AVAILABLE_QUANTITY'];

				$result->addError(new Main\Error(static::getLang('TRADING_ENTITY_SALE_ENTITY_ORDER_BASKET_ITEM_INSUFFICIENT_QUANTITY', [
					'#REQUIRED#' => $basketFields['QUANTITY'],
					'#AVAILABLE#' => $basketFields['AVAILABLE_QUANTITY'],
				])));
			}
		}

		// properties

		$propertyCollection = $basketItem->getPropertyCollection();

		if (!empty($basketFields['PROPS']) && $propertyCollection)
		{
			$propertyCollection->setProperty($basketFields['PROPS']);
		}

		$alreadySetFields += [
			'PROPS' => true,
		];

		// set presets

		$presetsFields = [
			'PRODUCT_PROVIDER_CLASS' => true,
			'CALLBACK_FUNC' => true,
			'PAY_CALLBACK_FUNC' => true,
			'SUBSCRIBE' => true,
		];
		$presets = array_intersect_key($basketFields, $presetsFields);
		$presets = array_intersect_key($presets, $settableFieldsMap);

		$basketItem->setFields($presets);
		$alreadySetFields += $presetsFields;

		// get provider data

		$providerResult = $this->getBasketItemProviderData($basket, $basketItem, $basketFields);

		if ($providerResult->isSuccess())
		{
			$providerData = (array)$providerResult->getData();

			if (
				isset($providerData['AVAILABLE_QUANTITY'], $basketFields['AVAILABLE_QUANTITY'])
				&& $providerData['AVAILABLE_QUANTITY'] < $basketFields['AVAILABLE_QUANTITY']
			)
			{
				$basketFields['AVAILABLE_QUANTITY'] = $providerData['AVAILABLE_QUANTITY'];
			}

			$basketFields += $providerData;
		}
		else
		{
			$result->addErrors($providerResult->getErrors());
		}

		// set name

		if (isset($basketFields['NAME']))
		{
			$basketItem->setField('NAME', $basketFields['NAME']);

			if ($this->useBasketItemNameFromProvider())
			{
				$alreadySetFields['NAME'] = true;
			}
		}

		// set quantity

		$setQuantityResult = $basketItem->setField('QUANTITY', $basketFields['QUANTITY']);

		if (!$setQuantityResult->isSuccess())
		{
			$this->fillBasketItemAvailableQuantity($basketItem, $basketFields);

			$result->addErrors($setQuantityResult->getErrors());
		}

		$alreadySetFields += [
			'QUANTITY' => true,
		];

		// set left fields

		$leftFields = array_diff_key($basketFields, $alreadySetFields);
		$leftFields = array_intersect_key($leftFields, $settableFieldsMap);

		$setLeftFields = $basketItem->setFields($leftFields);

		if (!$setLeftFields->isSuccess())
		{
			$result->addErrors($setLeftFields->getErrors());
		}

		$result->setData([
			'BASKET_ITEM' => $basketItem,
		]);

		return $result;
	}

	protected function useBasketItemNameFromProvider()
	{
		return Market\Config::getOption('trading_basket_name_original') === 'Y';
	}

	protected function getBasketItemProviderData(Sale\BasketBase $basket, Sale\BasketItemBase $basketItem, $basketFields)
	{
		$result = new Main\Result();
		$initialQuantity = $basketItem->getField('QUANTITY');

		$basketItem->setFieldNoDemand('QUANTITY', $basketFields['QUANTITY']); // required for get available quantity

		$providerData = Sale\Provider::getProductData($basket, ['PRICE', 'AVAILABLE_QUANTITY'], $basketItem);
		$basketCode = $basketItem->getBasketCode();

		if (empty($providerData[$basketCode]))
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_ORDER_PRODUCT_NO_PROVIDER_DATA');
			$result->addError(new Main\Error($errorMessage));
		}
		else
		{
			// -- cache provider data to discount

			$discount = $basket->getOrder()->getDiscount();

			if ($discount instanceof Sale\Discount)
			{
				$basketFieldsDiscountData = array_intersect_key($basketFields, [
					'BASE_PRICE' => true,
					'DISCOUNT_PRICE' => true,
					'PRICE' => true,
					'CURRENCY' => true,
					'DISCOUNT_LIST' => true,
				]);
				$discountBasketData = $basketFieldsDiscountData + $providerData[$basketCode];

				$discount->setBasketItemData($basketCode, $discountBasketData);
			}

			// -- export data

			$result->setData($providerData[$basketCode]);
		}

		$basketItem->setFieldNoDemand('QUANTITY', $initialQuantity); // reset initial quantity

		return $result;
	}

	protected function fillBasketItemAvailableQuantity(Sale\BasketItemBase $basketItem, $fields)
	{
		$currentQuantity = (float)$basketItem->getQuantity();
		$requestedQuantity = (float)$fields['QUANTITY'];

		if ($currentQuantity < $requestedQuantity && isset($fields['AVAILABLE_QUANTITY']))
		{
			$siblingsQuantity = $this->getBasketProductSiblingsFilledQuantity($basketItem);
			$availableQuantity = (float)$fields['AVAILABLE_QUANTITY'] - $siblingsQuantity;

			if ($availableQuantity > $currentQuantity && $availableQuantity < $requestedQuantity)
			{
				$basketItem->setField('QUANTITY', $availableQuantity);
			}
		}
	}

	protected function getBasketProductSiblingsFilledQuantity(Sale\BasketItemBase $basketItem)
	{
		$result = 0;
		$searchProductId = (string)$basketItem->getProductId();

		if ($searchProductId !== '')
		{
			$basket = $basketItem->getCollection();

			/** @var Sale\BasketItemBase $basketItem*/
			foreach ($basket as $siblingItem)
			{
				if (
					$siblingItem !== $basketItem
					&& (string)$siblingItem->getProductId() === $searchProductId
					&& $siblingItem->canBuy()
				)
				{
					$result += $siblingItem->getQuantity();
				}
			}
		}

		return $result;
	}

	public function fillMarking(array $basketMarkings)
	{
		$result = new Main\Result();
		$allChanges = [];

		foreach ($basketMarkings as $basketCode => $itemMarkings)
		{
			$basketItem = $this->getBasket()->getItemByBasketCode($basketCode);

			if ($basketItem === null) { continue; }
			if (!method_exists($basketItem, 'isSupportedMarkingCode') || !$basketItem->isSupportedMarkingCode()) { continue; }

			$type = $this->basketItemMarkingType($basketItem) ?: Market\Data\Trading\MarkingRegistry::CIS;
			$filled = array_column($this->collectBasketItemInstances($basketItem), $type);
			$filled = array_filter($filled);
			$new = $type === Market\Data\Trading\MarkingRegistry::UIN
				? Market\Data\Trading\Uin::diff($itemMarkings, $filled)
				: Market\Data\Trading\Cis::diff($itemMarkings, $filled);
			$itemChanges = [];

			if (empty($new)) { continue; }

			/** @var Sale\Shipment $shipment */
			foreach ($this->internalOrder->getShipmentCollection() as $shipment)
			{
				if ($shipment->isSystem()) { continue; }

				$shipmentItem = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketItem->getBasketCode());

				if ($shipmentItem === null) { continue; }

				$storeItemCollection = $shipmentItem->getShipmentItemStoreCollection();

				if ($storeItemCollection === null) { continue; }

				/** @var Sale\ShipmentItemStore $shipment */
				foreach ($storeItemCollection as $storeItem)
				{
					if (!method_exists($storeItem, 'getMarkingCode')) { continue; }

					$storedCode = (string)$storeItem->getMarkingCode();

					if ($storedCode !== '') { continue; }

					$code = array_shift($new);

					$storeItem->setField('MARKING_CODE', $code);
					$itemChanges[] = $code;

					if (empty($new)) { break; }
				}

				foreach ($new as $code)
				{
					if ($storeItemCollection->count() >= $shipmentItem->getQuantity()) { break; }

					$itemChanges[] = $code;
					$shipmentItemStore = $storeItemCollection->createItem($basketItem);
					$shipmentItemStore->setFields([
						'MARKING_CODE' => $code,
						'QUANTITY' => 1,
					]);
				}
			}

			if (!empty($itemChanges))
			{
				$allChanges[$basketCode] = $itemChanges;
			}
		}

		if (!empty($allChanges))
		{
			Listener::addInternalChange(
				$this->internalOrder->getId(),
				'SHIPMENT.ITEM.STORE.MARKING_CODE',
				Listener::STRICT_INTERNAL_CHANGE
			);
		}

		$result->setData([
			'CHANGES' => $allChanges,
		]);

		return $result;
	}

	public function getExistsBasketItemCodes()
	{
		$basket = $this->getBasket();
		$result = [];

		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$result[] = $basketItem->getBasketCode();
		}

		return $result;
	}

	public function getBasketItemCode($value, $field = 'PRODUCT_ID')
	{
		$basket = $this->getBasket();
		$result = null;

		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$itemValue = (string)$basketItem->getField($field);

			if ($field === 'XML_ID' && $itemValue !== '')
			{
				$hashPosition = Market\Data\TextString::getPosition($itemValue, '_R');

				if ($hashPosition > 0)
				{
					$itemValue = Market\Data\TextString::getSubstring($itemValue, 0, $hashPosition);
				}
			}

			if ($itemValue === (string)$value)
			{
				$result = $basketItem->getBasketCode();
				break;
			}
		}

		return $result;
	}

	public function debugBasketItem($basketCode, array $expected = [])
	{
		$basketItem = $this->getBasket()->getItemByBasketCode($basketCode);

		if ($basketItem === null) { return []; }

		$result = [];
		$current = $basketItem->getFields()->getValues();
		$expected += [
			'PRODUCT_PROVIDER_CLASS' => $this->getProductDefaultProvider(),
		];

		foreach ($expected as $name => $expectedValue)
		{
			if (!isset($current[$name]) && !array_key_exists($name, $current)) { continue; }

			$currentValue = $current[$name];

			if ($name === 'QUANTITY')
			{
				$isEqual = Market\Data\Quantity::equal($expectedValue, $current['QUANTITY']);
			}
			else
			{
				$expectedSanitized = Market\Data\TextString::toLower($expectedValue);
				$currentSanitized = Market\Data\TextString::toLower($currentValue);

				if ($name === 'PRODUCT_PROVIDER_CLASS')
				{
					$expectedSanitized = ltrim($expectedSanitized, '\\');
					$currentSanitized = ltrim($currentSanitized, '\\');
				}

				$isEqual = ($expectedSanitized === $currentSanitized);
			}

			if (!$isEqual)
			{
				$result[$name] = $currentValue;
			}
		}

		return $result;
	}

	public function getBasketItemData($basketCode)
	{
		$result = new Main\Result();
		$basket = $this->getBasket();
		$basketItem = $basket->getItemByBasketCode($basketCode);

		if ($basketItem === null)
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_ENTITY_ORDER_BASKET_ITEM_NOT_FOUND');
			$result->addError(new Main\Error($errorMessage));
		}
		else
		{
			$result->setData([
				'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
				'NAME' => $basketItem->getField('NAME'),
				'PRICE' => $basketItem->getPrice(),
				'DISCOUNT_PRICE' => $basketItem->getDiscountPrice(),
				'QUANTITY' => $basketItem->canBuy() ? $basketItem->getQuantity() : 0,
				'XML_ID' => $basketItem->getField('XML_ID'),
				'MEASURE_NAME' => $basketItem->getField('MEASURE_NAME'),
				'DETAIL_PAGE_URL' => $basketItem->getField('DETAIL_PAGE_URL'),
				'VAT_RATE' => $basketItem->getVatRate() * 100,
				'INSTANCES' => $this->collectBasketItemInstances($basketItem),
				'MARKING_GROUP' => method_exists($basketItem, 'getMarkingCodeGroup')
					? $basketItem->getMarkingCodeGroup()
					: null,
				'MARKING_TYPE' => $this->basketItemMarkingType($basketItem),
			]);
		}

		return $result;
	}

	protected function collectBasketItemInstances(Sale\BasketItemBase $basketItem)
	{
		$basketItemCode = $basketItem->getBasketCode();
		$markingType = $this->basketItemMarkingType($basketItem) ?: Market\Data\Trading\MarkingRegistry::CIS;
		$result = [];

		/** @var Sale\Shipment $shipment */
		foreach ($this->internalOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$shipmentItem = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketItemCode);

			if ($shipmentItem === null) { continue; }

			/** @var Sale\ShipmentItemStore $storeItem */
			foreach ($shipmentItem->getShipmentItemStoreCollection() as $storeItem)
			{
				$markingCode = method_exists($storeItem, 'getMarkingCode')
					? (string)$storeItem->getMarkingCode()
					: '';

				$result[] = [
					$markingType => $markingCode !== '' ? $markingCode : null,
				];
			}
		}

		return $result;
	}

	protected function basketItemMarkingType(Sale\BasketItemBase $basketItem)
	{
		if (!method_exists($basketItem, 'getMarkingCodeGroup')) { return null; }

		return $this->environment->getProduct()->getMarkingGroupType(
			$basketItem->getMarkingCodeGroup()
		);
	}

	public function setBasketItemPrice($basketCode, $price)
	{
		$result = new Main\Result();
		$basket = $this->getBasket();
		$basketItem = $basket->getItemByBasketCode($basketCode);

		if ($basketItem === null)
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_BASKET_ITEM_NOT_FOUND');
			$result->addError(new Main\Error($errorMessage));
		}
		else
		{
			$setResult = $basketItem->setFields([
				'CUSTOM_PRICE' => 'Y',
				'PRICE' => $price
			]);

			if (!$setResult->isSuccess())
			{
				$result->addErrors($setResult->getErrors());
			}
			else if ((float)$basketItem->getField('BASE_PRICE') < $price)
			{
				$basketItem->setField('BASE_PRICE', $price);
			}
		}

		return $result;
	}

	public function setBasketItemQuantity($basketCode, $quantity, $silent = false)
	{
		$result = new Main\Result();
		$basket = $this->getBasket();
		$basketItem = $basket->getItemByBasketCode($basketCode);

		if ($basketItem === null)
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_BASKET_ITEM_NOT_FOUND');
			$result->addError(new Main\Error($errorMessage));
		}
		else if (Market\Data\Quantity::round($quantity) === Market\Data\Quantity::round($basketItem->getQuantity()))
		{
			// nothing
		}
		else if ($silent)
		{
			$basketItem->setFieldNoDemand('QUANTITY', $quantity);

			Listener::addInternalChange(
				$this->internalOrder->getId(),
				sprintf('BASKET.%s.QUANTITY', $basketCode),
				$quantity
			);

			if ($this->supportsShipmentItemOverheadQuantity())
			{
				$this->syncShipmentItemQuantity($basketItem, true);
			}
		}
		else
		{
			$setResult = $basketItem->setField('QUANTITY', $quantity);

			if ($setResult->isSuccess())
			{
				Listener::addInternalChange(
					$this->internalOrder->getId(),
					sprintf('BASKET.%s.QUANTITY', $basketCode),
					$quantity
				);

				$this->syncShipmentItemQuantity($basketItem);
			}
			else
			{
				$result->addErrors($setResult->getErrors());
			}
		}

		return $result;
	}

	public function setBasketStore(array $stores)
	{
		$result = new Main\Result();
		$storeId = reset($stores);

		if (!is_numeric($storeId) || count($stores) !== 1 || !Sale\Configuration::useStoreControl())
		{
			return $result;
		}

		/** @var Sale\BasketItem $basketItem */
		foreach ($this->getBasket() as $basketItem)
		{
			$itemResult = $this->setBasketItemStore($basketItem->getBasketCode(), $storeId);

			if (!$itemResult->isSuccess())
			{
				$result->addErrors($itemResult->getErrors());
			}
		}

		return $result;
	}

	public function setBasketItemStore($basketCode, $storeId)
	{
		$result = new Main\Result();

		try
		{
			$basketItem = $this->getBasket()->getItemByBasketCode($basketCode);

			if ($basketItem === null)
			{
				throw new Main\SystemException(static::getLang('TRADING_ENTITY_SALE_ENTITY_ORDER_BASKET_ITEM_NOT_FOUND'));
			}

			$shipment = $this->resolveBasketItemStoreShipment($basketItem, $storeId);

			if ($shipment === null)
			{
				throw new Main\SystemException(static::getLang('TRADING_ENTITY_SALE_ENTITY_ORDER_BASKET_ITEM_SHIPMENT_MISSING'));
			}

			$basketItemCode = $basketItem->getBasketCode();
			$shipmentItem = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketItemCode);

			if ($shipmentItem === null)
			{
				$shipmentItem = $this->moveBasketItemToShipment($basketItem, $shipment);
			}

			if ($shipmentItem === null)
			{
				throw new Main\SystemException(static::getLang('TRADING_ENTITY_SALE_ENTITY_ORDER_BASKET_ITEM_SHIPMENT_ITEM_MISSING'));
			}

			if (Sale\Configuration::useStoreControl())
			{
				if (method_exists($basketItem, 'isSupportedMarkingCode') && $basketItem->isSupportedMarkingCode())
				{
					foreach (range(1, $basketItem->getQuantity()) as $unused)
					{
						$storeItem = $shipmentItem->getShipmentItemStoreCollection()->createItem($basketItem);

						$storeItem->setField('STORE_ID', $storeId);
						$storeItem->setField('QUANTITY', 1);
					}
				}
				else
				{
					$storeItem = $shipmentItem->getShipmentItemStoreCollection()->createItem($basketItem);

					$storeItem->setField('STORE_ID', $storeId);
					$storeItem->setField('QUANTITY', $basketItem->getQuantity());
				}
			}
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	protected function resolveBasketItemStoreShipment(Sale\BasketItemBase $basketItem, $storeId)
	{
		$basketItemShipment = $this->getBasketItemShipment($basketItem);

		if ($basketItemShipment === null)
		{
			$result = $this->getStoreShipment($storeId) ?: $this->makeStoreShipment($storeId);
		}
		else if ($basketItemShipment->getStoreId() <= 0)
		{
			$basketItemShipment->setStoreId($storeId);
			$result = $basketItemShipment;
		}
		else if ($basketItemShipment->getStoreId() !== (int)$storeId)
		{
			$result = $this->getStoreShipment($storeId) ?: $this->makeStoreShipment($storeId);
		}
		else
		{
			$result = $basketItemShipment;
		}

		if ($result !== $basketItemShipment)
		{
			$this->moveBasketItemToShipment($basketItem, $result, $basketItemShipment);
		}

		return $result;
	}

	protected function getBasketItemShipment(Sale\BasketItemBase $basketItem)
	{
		if (!($this->internalOrder instanceof Sale\Order)) { return null; }

		$result = null;
		$basketCode = $basketItem->getBasketCode();

		/** @var Sale\Shipment $shipment */
		foreach ($this->internalOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$shipmentItem = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketCode);

			if ($shipmentItem !== null)
			{
				$result = $shipment;
				break;
			}
		}

		return $result;
	}

	protected function getStoreShipment($storeId)
	{
		if (!($this->internalOrder instanceof Sale\Order)) { return null; }

		$storeId = (int)$storeId;
		$result = null;

		/** @var Sale\Shipment $shipment */
		foreach ($this->internalOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			if ($shipment->getStoreId() === $storeId)
			{
				$result = $shipment;
				break;
			}
		}

		return $result;
	}

	protected function makeStoreShipment($storeId)
	{
		$originalShipment = $this->getNotSystemShipment();

		if ($originalShipment === null) { return null; }

		if ($originalShipment->getStoreId() > 0)
		{
			/** @var Sale\ShipmentCollection $shipmentCollection */
			$shipmentCollection = $originalShipment->getCollection();
			$result = $shipmentCollection->createItem();
			$originalValues = $originalShipment->getFields()->getValues();
			$originalValues = array_intersect_key(
				$originalValues,
				array_flip(Sale\Shipment::getAvailableFields())
			);

			$result->setFields($originalValues);
		}
		else
		{
			$result = $originalShipment;
		}

		$result->setStoreId($storeId);

		return $result;
	}

	protected function moveBasketItemToShipment(Sale\BasketItemBase $basketItem, Sale\Shipment $to, Sale\Shipment $from = null)
	{
		if (!($basketItem instanceof Sale\BasketItem)) { return null; }

		$basketCode = $basketItem->getBasketCode();

		// remove from

		if ($from !== null)
		{
			$fromShipmentItem = $from->getShipmentItemCollection()->getItemByBasketCode($basketCode);

			if ($fromShipmentItem !== null)
			{
				$fromShipmentItem->setField('QUANTITY', 0);
				$fromShipmentItem->delete();
			}
		}

		// add to

		$toShipmentCollection = $to->getShipmentItemCollection();
		$shipmentItem = $toShipmentCollection->createItem($basketItem);

		if ($shipmentItem === null) { return null; }

		$shipmentItem->setField('QUANTITY', $basketItem->getQuantity());

		return $shipmentItem;
	}

	public function deleteBasketItem($basketCode)
	{
		$result = new Main\Result();
		$basket = $this->getBasket();
		$basketItem = $basket->getItemByBasketCode($basketCode);

		if ($basketItem === null)
		{
			$errorMessage = static::getLang('TRADING_ENTITY_SALE_BASKET_ITEM_NOT_FOUND');
			$result->addError(new Main\Error($errorMessage));
		}
		else
		{
			$deleteResult = $basketItem->delete();

			if ($deleteResult->isSuccess())
			{
				Listener::addInternalChange($this->internalOrder->getId(), 'BASKET.DELETE', $basketCode);
			}
			else
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public function getBasketPrice()
	{
		return $this->getBasket()->getPrice();
	}

	/** @return Sale\OrderBase */
	public function getInternal()
	{
		return $this->internalOrder;
	}

	/** @return Sale\OrderBase */
	public function getCalculatable()
	{
		if ($this->calculatable === null)
		{
			$this->calculatable = $this->createCalculatable();
		}

		return $this->calculatable;
	}

	protected function createCalculatable()
	{
		$order = method_exists($this->internalOrder, 'createClone')
			? $this->internalOrder->createClone()
			: $this->internalOrder;

		$order->isStartField();

		$shipment = $this->getNotSystemShipment($order) ?: $this->initOrderShipment($order);

		if ($shipment !== null)
		{
			$shipment->setField('CUSTOM_PRICE_DELIVERY', 'N');
		}

		return $order;
	}

	public function getDeliveryId()
	{
		$shipment = $this->getNotSystemShipment();

		return $shipment !== null ? $shipment->getDeliveryId() : null;
	}

	public function createShipment($deliveryId, $price = null, array $data = null)
	{
		$shipmentCollection = $this->internalOrder->getShipmentCollection();

		$this->clearOrderShipment($shipmentCollection);
		$shipment = $this->buildOrderShipment($shipmentCollection, $deliveryId, $data);

		$this->fillShipmentPrice($shipment, $price);
		$this->fillShipmentBasket($shipment);

		return new Main\Result();
	}

	protected function initOrderShipment(Sale\OrderBase $order = null)
	{
		if ($order === null) { $order = $this->internalOrder; }

		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();
		$shipment->setField('CURRENCY', $order->getCurrency());

		$this->fillShipmentBasket($shipment);

		return $shipment;
	}

	protected function clearOrderShipment(Sale\ShipmentCollection $shipmentCollection)
	{
		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if (!$shipment->isSystem())
			{
				$shipment->delete();
			}
		}
	}

	protected function buildOrderShipment(Sale\ShipmentCollection $shipmentCollection, $deliveryId, array $data = null)
	{
		$shipment = $shipmentCollection->createItem();

		if ((int)$deliveryId > 0)
		{
			$delivery = Sale\Delivery\Services\Manager::getObjectById($deliveryId);

			if ($delivery !== null)
			{
				$deliveryName = $delivery->getNameWithParent();
			}
			else
			{
				$deliveryName = 'Not found (' . $deliveryId . ')';
			}

			$shipment->setField('DELIVERY_ID', $deliveryId);
			$shipment->setField('DELIVERY_NAME', $deliveryName);
		}

		if (!empty($data))
		{
			$settableFields = array_flip($shipment::getAvailableFields());
			$settableData = array_intersect_key($data, $settableFields);

			$shipment->setFields($settableData);
		}

		return $shipment;
	}

	protected function fillShipmentPrice(Sale\Shipment $shipment, $price = null)
	{
		if ($price !== null)
		{
			$result = $shipment->setFields([
				'CUSTOM_PRICE_DELIVERY' => 'Y',
				'BASE_PRICE_DELIVERY' => $price,
				'PRICE_DELIVERY' => $price,
			]);
		}
		else
		{
			$result = new Sale\Result();
		}

		return $result;
	}

	protected function fillShipmentBasket(Sale\Shipment $shipment)
	{
		/** @var Sale\BasketItem $basketItem */
		/** @var Sale\ShipmentItem $shipmentItem */
		$basket = $this->getBasket();
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		foreach ($basket as $basketItem)
		{
			$shipmentItem = $shipmentItemCollection->createItem($basketItem);

			if ($shipmentItem)
			{
				$shipmentItem->setQuantity($basketItem->getQuantity());
			}
		}
	}

	public function getShipmentPrice($deliveryId)
	{
		$shipment = $this->getDeliveryShipment($deliveryId);

		return $shipment !== null ? $shipment->getPrice() : null;
	}

	public function setShipmentPrice($deliveryId, $price)
	{
		$shipment = $this->getDeliveryShipment($deliveryId);

		if ($shipment === null)
		{
			$result = new Main\Result();
			$result->addError(new Main\Error(
				sprintf('cant find shipment with delivery service %s', $deliveryId)
			));
		}
		else
		{
			$result = $this->fillShipmentPrice($shipment, $price);
		}

		return $result;
	}

	public function setShipmentStore($deliveryId, $storeId)
	{
		$shipment = $this->getDeliveryShipment($deliveryId);
		$result = new Main\Result();

		if ($shipment === null)
		{
			$result->addError(new Main\Error(
				sprintf('cant find shipment with delivery service %s', $deliveryId)
			));

			return $result;
		}

		// always set for event handlers

		$shipment->setStoreId($storeId);

		// test delivery support

		$storeExtraService = Sale\Delivery\ExtraServices\Manager::getStoresFields($shipment->getDeliveryId());

		if (empty($storeExtraService))
		{
			$result->addError(new Main\Error(
				sprintf('delivery service %s hasn\'t support for store selection', $deliveryId)
			));
		}

		return $result;
	}

	protected function getDeliveryShipment($deliveryId)
	{
		$deliveryId = (int)$deliveryId;
		$result = null;

		/** @var Sale\Shipment $shipment */
		foreach ($this->internalOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			if ((int)$shipment->getDeliveryId() === $deliveryId)
			{
				$result = $shipment;
				break;
			}
		}

		return $result;
	}

	protected function supportsShipmentItemOverheadQuantity()
	{
		$saleVersion = Main\ModuleManager::getVersion('sale');

		return $saleVersion !== false && CheckVersion($saleVersion, '17.0.0');
	}

	protected function syncShipmentItemQuantity(Sale\BasketItemBase $basketItem, $silent = false)
	{
		if (!($basketItem instanceof Sale\BasketItem)) { return; }

		$shipment = $this->getBasketItemShipment($basketItem) ?: $this->getNotSystemShipment();

		if ($shipment === null) { return; }

		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$shipmentItem = $shipmentItemCollection->createItem($basketItem);

		if ($shipmentItem === null) { return; }

		if ($silent)
		{
			$shipmentItem->setFieldNoDemand('QUANTITY', $basketItem->getQuantity());
		}
		else
		{
			$shipmentItem->setField('QUANTITY', $basketItem->getQuantity());
		}
	}

	protected function getNotSystemShipment(Sale\OrderBase $order = null)
	{
		if ($order === null) { $order = $this->internalOrder; }

		$shipmentCollection = $order->getShipmentCollection();
		$result = null;

		/** @var \Bitrix\Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if (!$shipment->isSystem())
			{
				$result = $shipment;
				break;
			}
		}

		return $result;
	}

	public function getPaySystemId()
	{
		$paymentCollection = $this->internalOrder->getPaymentCollection();
		$payments = $this->filterPaymentCollection($paymentCollection, [
			'SUBSIDY' => false,
		]);

		if (empty($payments)) { return null; }

		$firstPayment = reset($payments);

		return $firstPayment->getPaymentSystemId();
	}

	public function createPayment($paySystemId, $price = null, array $data = null)
	{
		$paymentCollection = $this->internalOrder->getPaymentCollection();

		$payment = $this->buildOrderPayment($paymentCollection, $paySystemId, $data);
		$this->fillPaymentPrice($payment, $price);

		return new Main\Result();
	}

	protected function clearOrderPayment(Sale\PaymentCollection $paymentCollection)
	{
		/** @var Sale\Payment $payment*/
		foreach ($paymentCollection as $payment)
		{
			if ($payment->isPaid())
			{
				$payment->setPaid('N');
			}

			$payment->delete();
		}
	}

	protected function buildOrderPayment(Sale\PaymentCollection $paymentCollection, $paySystemId, array $data = null)
	{
		$payment = $paymentCollection->createItem();

		if ((int)$paySystemId > 0)
		{
			$paySystem = Sale\PaySystem\Manager::getById($paySystemId);

			if ($paySystem !== false)
			{
				$paySystemName = $paySystem['NAME'];
			}
			else
			{
				$paySystemName = 'Not found (' . $paySystemId . ')';
			}

			$payment->setField('PAY_SYSTEM_ID', $paySystemId);
			$payment->setField('PAY_SYSTEM_NAME', $paySystemName);
		}

		if (!empty($data))
		{
			$settableFields = array_flip($payment::getAvailableFields());
			$settableData = array_intersect_key($data, $settableFields);

			$payment->setFields($settableData);
		}

		if (isset($data['SUBSIDY']) && $data['SUBSIDY'] === true)
		{
			$xmlId = $this->makeSubsidyPaymentXmlId($data);

			$payment->setField('XML_ID', $xmlId);
		}

		return $payment;
	}

	protected function fillPaymentPrice(Sale\Payment $payment, $price = null)
	{
		if ($price === null)
		{
			$orderPrice = $this->internalOrder->getPrice();
			$paymentsSum = $this->internalOrder->getPaymentCollection()->getSum();
			$selfSum = $payment->getSum();

			$price = $orderPrice - ($paymentsSum - $selfSum);
		}

		$payment->setField('SUM', $price);
	}

	protected function isSubsidyPayment(Sale\Payment $payment)
	{
		$xmlId = (string)$payment->getField('XML_ID');

		return (Market\Data\TextString::getPosition($xmlId, static::PAYMENT_SUBSIDY_PREFIX) === 0);
	}

	protected function makeSubsidyPaymentXmlId(array $data)
	{
		$orderId = isset($data['ORDER_ID']) ? $data['ORDER_ID'] : 'CART';

		return static::PAYMENT_SUBSIDY_PREFIX . $orderId;
	}

	public function setNotes($notes)
	{
		return $this->internalOrder->setField('USER_DESCRIPTION', $notes);
	}

	public function finalize()
	{
		$basket = $this->getBasket();
		$basketHandler = $this->getBasketDataPreserver();
		$basketHandler->install();

		$this->internalOrder->setMathActionOnly(false);
		$result = $basket->refreshData();

		$basketHandler->release();

		if ($result->isSuccess())
		{
			$unfreezeResult = $this->unfreeze();

			if (!$unfreezeResult->isSuccess())
			{
				$result->addErrors($unfreezeResult->getErrors());
			}
		}

		return $result;
	}

	public function freeze()
	{
		$this->isStartField = $this->internalOrder->isStartField();
		$this->internalOrder->setMathActionOnly(true);
	}

	public function unfreeze()
	{
		$result = new Main\Result();
		$needCalculate = (
			$this->needCalculate(EntityOperation\PriceCalculation::PRODUCT)
			|| $this->needCalculate(EntityOperation\PriceCalculation::DELIVERY)
		);

		$this->internalOrder->setMathActionOnly(false);

		if ($this->isStartField && $needCalculate)
		{
			$hasMeaningfulFields = $this->internalOrder->hasMeaningfulField();
			$finalActionResult = $this->internalOrder->doFinalAction($hasMeaningfulFields);

			if (!$finalActionResult->isSuccess())
			{
				$result->addErrors($finalActionResult->getErrors());
			}
		}

		return $result;
	}

	public function isExistMarker($code, $condition = null)
	{
		$marker = $this->environment->getMarker();

		if ($this->internalOrder->getField('MARKED') !== 'Y')
		{
			$result = false;
		}
		else if ($marker->hasExternalEntity())
		{
			$result = ($marker->getMarkerId($this->getId(), $code, $condition) !== null);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public function getMarkers($codePrefix)
	{
		if ($this->internalOrder->getField('MARKED') !== 'Y') { return []; }

		$marker = $this->environment->getMarker();
		$result = [];

		if ($marker->hasExternalEntity())
		{
			foreach ($marker->getActive($this->internalOrder->getId()) as $row)
			{
				if (Market\Data\TextString::getPosition($row['CODE'], $codePrefix) !== 0) { continue; }

				$result[] = $row['MESSAGE'];
			}
		}
		else
		{
			$result[] = $this->internalOrder->getField('REASON_MARKED');
		}

		return $result;
	}

	public function addMarker($message, $code)
	{
		$message = $this->truncateMarkerText($message);
		$reason = $message;
		$marker = $this->environment->getMarker();

		if ($marker->hasExternalEntity())
		{
			if (!$marker->hasSameMarker($this->internalOrder, $message))
			{
				$marker->addMarker($this->internalOrder, $this->internalOrder, $message, $code);
			}
		}
		else
		{
			$previousReason = (string)$this->internalOrder->getField('REASON_MARKED');

			if ($previousReason !== '')
			{
				$reason = $previousReason . '<br>' . $reason;
				$reason = $this->truncateMarkerText($reason);
			}
		}

		return $this->internalOrder->setFields([
			'MARKED' => 'Y',
			'REASON_MARKED' => $reason,
		]);
	}

	protected function truncateMarkerText($text)
	{
		$result = $text;
		$maxLength = 250;

		if (Market\Data\TextString::getLength($result) > $maxLength)
		{
			$dots = '...';
			$dotsLength = Market\Data\TextString::getLength($dots);

			$result =
				Market\Data\TextString::getSubstring($result, 0, $maxLength - $dotsLength)
				. $dots;
		}

		return $result;
	}

	public function removeMarker($code)
	{
		$result = new Main\Result();
		$marker = $this->environment->getMarker();
		$hasLeftMarkers = false;

		if ($marker->hasExternalEntity())
		{
			$markerId = $marker->getMarkerId($this->getId(), $code);

			if ($markerId !== null)
			{
				$deleteResult = $marker->delete($markerId);

				if ($deleteResult->isSuccess())
				{
					$hasLeftMarkers = $marker->hasMarkers($this->getId());
				}
				else
				{
					$result->addErrors($deleteResult->getErrors());
				}
			}
			else
			{
				$hasLeftMarkers = $marker->hasMarkers($this->getId());
			}
		}

		if (!$hasLeftMarkers)
		{
			$this->internalOrder->setFields([
				'MARKED' => 'N',
				'REASON_MARKED' => '',
			]);
		}

		return $result;
	}

	public function getStatuses()
	{
		$result = [
			(string)$this->internalOrder->getField('STATUS_ID'),
		];

		if ($this->internalOrder->isCanceled())
		{
			$result[] = Status::STATUS_CANCELED;
		}

		if ($this->internalOrder->isPaid())
		{
			$result[] = Status::STATUS_PAYED;
		}

		if ($this->internalOrder->isAllowDelivery())
		{
			$result[] = Status::STATUS_ALLOW_DELIVERY;
		}

		if ($this->internalOrder->getField('DEDUCTED') === 'Y')
		{
			$result[] = Status::STATUS_DEDUCTED;
		}

		return $result;
	}

	public function setStatus($status, $payload = null)
	{
		switch ($status)
		{
			case Status::STATUS_CANCELED:
				$result = $this->cancelOrder($payload);
			break;

			case Status::STATUS_SUBSIDY:
				$result = $this->setPaid(true, [ 'SUBSIDY' => true ]);
			break;

			case Status::STATUS_PAYED:
				$result = $this->setPaid(true, $payload);
			break;

			case Status::STATUS_ALLOW_DELIVERY:
				$result = $this->allowDelivery();
			break;

			case Status::STATUS_DEDUCTED:
				$result = $this->deduct();
			break;

			default:
				$result = $this->fillStatus($status);
			break;
		}

		return $result;
	}

	protected function allowDelivery()
	{
		$shipmentCollection = $this->internalOrder->getShipmentCollection();
		$result = new Main\Result();
		$changes = [];

		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem() || $shipment->isAllowDelivery()) { continue; }

			$allowResult = $shipment->allowDelivery();

			if ($allowResult->isSuccess())
			{
				$changes[] = $shipment->getId();
			}
			else
			{
				$result->addErrors($allowResult->getErrors());
			}
		}

		$result->setData([
			'CHANGES' => $changes,
		]);

		return $result;
	}

	protected function deduct()
	{
		$shipmentCollection = $this->internalOrder->getShipmentCollection();
		$result = new Main\Result();
		$changes = [];

		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem() || $shipment->isShipped()) { continue; }

			$deductResult = $shipment->setField('DEDUCTED', 'Y');

			if ($deductResult->isSuccess())
			{
				$changes[] = $shipment->getId();
			}
			else
			{
				$result->addErrors($deductResult->getErrors());
			}
		}

		$result->setData([
			'CHANGES' => $changes,
		]);

		return $result;
	}

	protected function cancelOrder($reason = null)
	{
		$result = new Main\Result();

		if ($this->internalOrder->isCanceled()) { return $result; }

		$this->cancelShipment($this->internalOrder);
		$this->cancelPayment($this->internalOrder);

		$setResult = $this->internalOrder->setField('CANCELED', 'Y');

		if ($setResult->isSuccess())
		{
			$changes = [
				'CANCELED',
			];
			
			Listener::addInternalChange($this->internalOrder->getId(), 'ORDER.CANCELED', 'Y');

			if ((string)$reason !== '')
			{
				$changes[] = 'REASON_CANCELED';
				$this->internalOrder->setField('REASON_CANCELED', $reason);
			}

			$result->setData([
				'CHANGES' => $changes,
			]);
		}
		else
		{
			$result->addErrors($setResult->getErrors());
		}

		return $result;
	}

	protected function cancelShipment(Sale\OrderBase $order)
	{
		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isShipped())
			{
				if ($shipment->isSystem())
				{
					$shipment->setFieldNoDemand('DEDUCTED', 'N');
				}
				else
				{
					$shipment->setField('DEDUCTED', 'N');
				}
			}

			if ($shipment->isAllowDelivery())
			{
				if ($shipment->isSystem())
				{
					$shipment->setFieldNoDemand('ALLOW_DELIVERY', 'N');
				}
				else
				{
					$shipment->disallowDelivery();
				}
			}
		}
	}

	protected function cancelPayment(Sale\OrderBase $order)
	{
		/** @var Sale\Payment $payment */
		foreach ($order->getPaymentCollection() as $payment)
		{
			if (!$payment->isPaid()) { continue; }

			$payment->setPaid('N');
		}
	}

	protected function setPaid($value, $payload = null)
	{
		$paymentCollection = $this->internalOrder->getPaymentCollection();
		$result = new Sale\Result();
		$value = (bool)$value;
		$changes = [];
		$needSyncPayment = false;

		foreach ($this->filterPaymentCollection($paymentCollection, $payload) as $payment)
		{
			if ((bool)$payment->isPaid() === $value) { continue; }

			$paymentValue = $value ? 'Y' : 'N';

			if ($paymentValue === 'Y' && $this->isPaymentSystemInner($payment))
			{
				$needSyncPayment = true;
				$payment->setFieldNoDemand('PAID', $paymentValue);
				$paymentResult = new Sale\Result();
			}
			else
			{
				$paymentResult = $payment->setPaid($paymentValue);
			}

			if ($paymentResult->isSuccess())
			{
				$changes[] = $payment->getId();
			}
			else
			{
				$result->addErrors($paymentResult->getErrors());
			}
		}

		if ($needSyncPayment)
		{
			$this->syncOrderPaidSum();
		}

		$result->setData([
			'CHANGES' => $changes,
		]);

		return $result;
	}

	/**
	 * @param Sale\PaymentCollection $paymentCollection
	 * @param array|float|null  $payload
	 *
	 * @return Sale\Payment[]
	 */
	protected function filterPaymentCollection(Sale\PaymentCollection $paymentCollection, $payload)
	{
		$payments = iterator_to_array($paymentCollection->getIterator());
		$payload = $this->sanitizePaymentCollectionPayload($payload);

		$payments = $this->filterPaymentCollectionExclude($payments, $payload['EXCLUDE']);
		$payments = $this->filterPaymentCollectionSubsidy($payments, $payload['SUBSIDY']);
		$payments = $this->filterPaymentCollectionPaySystem($payments, $payload['PAY_SYSTEM_ID']);
		$payments = $this->filterPaymentCollectionSum($payments, $payload['SUM']);

		return $payments;
	}

	protected function sanitizePaymentCollectionPayload($payload)
	{
		$result = [
			'SUM' => null,
			'PAY_SYSTEM_ID' => null,
			'SUBSIDY' => null,
			'EXCLUDE' => null,
		];

		if (is_array($payload))
		{
			if (isset($payload['SUM']) && is_numeric($payload['SUM']))
			{
				$result['SUM'] = (float)$payload['SUM'];
			}

			if (isset($payload['PAY_SYSTEM_ID']) && (string)$payload['PAY_SYSTEM_ID'] !== '')
			{
				$result['PAY_SYSTEM_ID'] = (int)$payload['PAY_SYSTEM_ID'];
			}

			if (isset($payload['SUBSIDY']))
			{
				$result['SUBSIDY'] = (bool)$payload['SUBSIDY'];
			}

			if (isset($payload['EXCLUDE']))
			{
				$exclude = $this->sanitizePaymentCollectionPayload($payload['EXCLUDE']);
				$excludeFilled = array_filter($exclude, static function($value) { return $value !== null; });

				if (!empty($excludeFilled))
				{
					$result['EXCLUDE'] = $exclude;
				}
			}
		}
		else if ($payload !== null && is_numeric($payload))
		{
			$result['SUM'] = (float)$payload;
		}

		return $result;
	}

	protected function filterPaymentCollectionExclude(array $payments, $payloadExclude)
	{
		if ($payloadExclude === null) { return $payments; }

		$exclude = $this->filterPaymentCollectionSubsidy($payments, $payloadExclude['SUBSIDY']);
		$exclude = $this->filterPaymentCollectionPaySystem($exclude, $payloadExclude['PAY_SYSTEM_ID']);
		$exclude = $this->filterPaymentCollectionSum($exclude, $payloadExclude['SUM']);

		foreach ($exclude as $excludePayment)
		{
			$index = array_search($excludePayment, $payments, true);

			if ($index !== false)
			{
				array_splice($payments, $index, 1);
			}
		}

		return $payments;
	}

	protected function filterPaymentCollectionSubsidy(array $payments, $payloadSubsidy)
	{
		if ($payloadSubsidy === null) { return $payments; }

		return array_filter($payments, function(Sale\Payment $payment) use ($payloadSubsidy) {
			return ($this->isSubsidyPayment($payment) === $payloadSubsidy);
		});
	}

	protected function filterPaymentCollectionPaySystem(array $payments, $payloadPaySystem)
	{
		if ($payloadPaySystem === null) { return $payments; }

		$result = [];

		/** @var Sale\Payment $payment */
		foreach ($payments as $payment)
		{
			$paySystemId = (int)$payment->getPaymentSystemId();

			if ($paySystemId === $payloadPaySystem)
			{
				$result[] = $payment;
			}
		}

		return $result;
	}

	protected function filterPaymentCollectionSum(array $payments, $payloadSum)
	{
		if ($payloadSum === null) { return $payments; }

		$threshold = 1;
		$result = [];

		foreach ($payments as $payment)
		{
			$paymentDiff = abs($payment->getSum() - $payloadSum);

			if ($paymentDiff < $threshold)
			{
				$result[] = $payment;
				break;
			}
		}

		return $result;
	}

	protected function isPaymentSystemInner(Sale\Payment $payment)
	{
		$paySystem = $payment->getPaySystem();

		return ($paySystem !== null && $paySystem->getField('ACTION_FILE') === 'inner');
	}

	protected function fillStatus($status)
	{
		$result = new Sale\Result();

		if ($this->internalOrder->getField('STATUS_ID') === $status) { return $result; }

		$setResult = $this->internalOrder->setField('STATUS_ID', $status);

		if ($setResult->isSuccess())
		{
			$result->setData([
				'CHANGES' => [ 'STATUS_ID' ],
			]);
		}
		else
		{
			$result->addErrors($setResult->getErrors());
		}

		return $result;
	}

	public function resetCashbox()
	{
		if (!class_exists(Sale\Cashbox\Internals\Pool::class)) { return; }

		$internalId = $this->internalOrder->getInternalId();

		// immediate reset for new bitrix

		Sale\Cashbox\Internals\Pool::resetDocs($internalId);

		// delayed reset for old bitrix

		if (!CheckVersion(Main\ModuleManager::getVersion('sale'), '20.0.675'))
		{
			Main\EventManager::getInstance()->addEventHandler('sale', 'OnSalePaymentEntitySaved', static function(Main\Event $event) use ($internalId) {
				/** @var Sale\Payment $payment */
				/** @var Sale\PaymentCollection $paymentCollection */
				$payment = $event->getParameter('ENTITY');
				$paymentCollection = $payment->getCollection();
				$order = $paymentCollection ? $paymentCollection->getOrder() : null;

				if (!$order || $order->getInternalId() !== $internalId) { return; }

				Sale\Cashbox\Internals\Pool::resetDocs($internalId);
			});
		}
	}

	public function add($externalId, EntityReference\Platform $platform)
	{
		$result = new Main\Result();

		$this->syncOrderPrice();
		$this->syncOrderPaymentSum();

		$orderResult = $this->internalOrder->save();

		if (!$orderResult->isSuccess())
		{
			$result->addErrors($orderResult->getErrors());
		}
		else
		{
			if ($this->accountNumberSetter !== null)
			{
				$this->accountNumberSetter->release();
			}

			$orderId = $orderResult->getId();
			$tradingResult = $this->addTradingTable($orderId, $externalId, $platform);

			if (!$tradingResult->isSuccess())
			{
				$result->addErrors($tradingResult->getErrors());
			}
			else
			{
				$orderExportId = $orderId;
				$orderAccountNumber = (string)$this->internalOrder->getField('ACCOUNT_NUMBER');

				if ($orderAccountNumber !== '' && OrderRegistry::useAccountNumber())
				{
					$orderExportId = $orderAccountNumber;
				}

				$result->setData([
					'ID' => $orderExportId
				]);
			}
		}

		return $result;
	}

	protected function syncOrderPaidSum()
	{
		$paymentCollection = $this->internalOrder->getPaymentCollection();

		if ($paymentCollection === null) { return; }

		$calculated = $paymentCollection->getPaidSum();
		$current = $this->internalOrder->getSumPaid();

		if (Market\Data\Price::round($calculated) !== Market\Data\Price::round($current))
		{
			$this->internalOrder->setFieldNoDemand('SUM_PAID', $calculated);
		}
	}

	protected function syncOrderPrice()
	{
		$currentPrice = $this->internalOrder->getPrice();
		$calculatedPrice = $this->getCalculatedOrderPrice();

		if (Market\Data\Price::round($currentPrice) !== Market\Data\Price::round($calculatedPrice))
		{
			$this->internalOrder->setField('PRICE', $calculatedPrice);
		}
	}

	protected function syncOrderPaymentSum()
	{
		$paymentCollection = $this->internalOrder->getPaymentCollection();

		if ($paymentCollection)
		{
			$lastPayment = null;
			$orderSum = $this->internalOrder->getPrice();
			$paymentSum = 0;

			/** @var Sale\Payment $payment*/
			foreach ($paymentCollection as $payment)
			{
				$paymentSum += $payment->getSum();

				if (!$payment->isPaid() && !$this->isPaymentSystemInner($payment) && !$this->isSubsidyPayment($payment))
				{
					$lastPayment = $payment;
				}
			}

			if (
				$lastPayment !== null
				&& Market\Data\Price::round($orderSum) !== Market\Data\Price::round($paymentSum)
			)
			{
				$newPaymentSum = $orderSum - ($paymentSum - $lastPayment->getSum());

				$payment->setField('SUM', $newPaymentSum);
			}
		}
	}

	protected function getCalculatedOrderPrice()
	{
		$result = 0;
		$basket = $this->internalOrder->getBasket();
		$shipmentCollection = $this->internalOrder->getShipmentCollection();

		if ($basket)
		{
			$result += $basket->getPrice();
		}

		if (!$this->internalOrder->isUsedVat())
		{
			$result += (float)$this->internalOrder->getField('TAX_PRICE');
		}

		if ($shipmentCollection)
		{
			$result += $shipmentCollection->getPriceDelivery();
		}

		return $result;
	}

	public function update()
	{
		if ($this->eventProcessing === Listener::STATE_PROCESSING) // will be stored in handlers
		{
			$result = new Main\Result();
		}
		else if ($this->eventProcessing === Listener::STATE_SAVING) // on after order row saved
		{
			$result = Market\Result\Facade::merge([
				$this->updateOrderRow(),
				$this->updateOrderProperties(),
			]);
		}
		else
		{
			$result = $this->internalOrder->save();
		}

		return $result;
	}

	protected function updateOrderRow()
	{
		if (!($this->internalOrder instanceof Sale\Order)) { return new Main\Result(); }

		$changedValues = $this->internalOrder->getFields()->getChangedValues();
		$diffValues = array_diff($changedValues, $this->initialChangedValues);
		$saveValues = array_intersect_key(
			$diffValues,
			Sale\Internals\OrderTable::getEntity()->getFields()
		);

		if (empty($saveValues)) { return new Main\Result(); }

		return Sale\OrderTable::update($this->internalOrder->getId(), $saveValues);
	}

	protected function updateOrderProperties()
	{
		$propertyCollection = $this->internalOrder->getPropertyCollection();

		if (!$propertyCollection->isChanged()) { return new Main\Result(); }

		return $propertyCollection->save();
	}

	protected function supportsTradeBinding(EntityReference\Platform $platform)
	{
		return (
			method_exists($this->internalOrder, 'getTradeBindingCollection')
			&& $platform instanceof Platform
			&& $platform->isInstalled()
		);
	}

	protected function updateTradeBinding($externalId, EntityReference\Platform $platform)
	{
		if (!$this->supportsTradeBinding($platform)) { return; }

		$binding =
			$this->searchTradeBinding($platform)
			?: $this->createTradeBinding($platform);

		if ($externalId !== null)
		{
			$binding->setField('EXTERNAL_ORDER_ID', $externalId);
		}
	}

	protected function searchTradeBinding(EntityReference\Platform $platform)
	{
		if (!$this->supportsTradeBinding($platform)) { return null; }

		$platformId = (int)$platform->getId();
		$bindingCollection = $this->internalOrder->getTradeBindingCollection();
		$result = null;

		/** @var Sale\TradeBindingEntity $binding */
		foreach ($bindingCollection as $binding)
		{
			$bindingPlatformId = (int)$binding->getField('TRADING_PLATFORM_ID');

			if ($platformId === $bindingPlatformId)
			{
				$result = $binding;
				break;
			}
		}

		return $result;
	}

	protected function createTradeBinding(EntityReference\Platform $platform)
	{
		if (!$this->supportsTradeBinding($platform)) { return null; }

		$salePlatform = $platform->getSalePlatform();
		$bindingCollection = $this->internalOrder->getTradeBindingCollection();

		return $bindingCollection->createItem($salePlatform);
	}

	protected function linkTradeBinding($setupId, EntityReference\Platform $platform)
	{
		if (!$this->supportsTradeBinding($platform)) { return; }

		$binding = $this->searchTradeBinding($platform);

		if ($binding === null) { return; }

		$existsParams = $binding->getField('PARAMS');

		if (!is_array($existsParams)) { $existsParams = []; }

		$binding->setField('PARAMS', [ 'SETUP_ID' => $setupId ] + $existsParams);
	}

	protected function addTradingTable($orderId, $externalId, Market\Trading\Entity\Reference\Platform $platform)
	{
		if ($this->supportsTradeBinding($platform) && $this->searchTradeBinding($platform) !== null)
		{
			return new Main\Entity\AddResult(); // save handled inside binding entity
		}

		return Sale\TradingPlatform\OrderTable::add([
			'ORDER_ID' => $orderId,
			'EXTERNAL_ORDER_ID' => $externalId,
			'TRADING_PLATFORM_ID' => $platform->getId(),
			'PARAMS' => [
				'SETUP_ID' => $this->tradingSetupId,
			],
		]);
	}

	protected function getBasket()
	{
		$order = $this->internalOrder;
		$basket = $order->getBasket();

		if ($basket === null || $basket === false)
		{
			$basket = $this->createBasket($order);
			$order->setBasket($basket);
		}

		return $basket;
	}

	protected function createBasket(Sale\OrderBase $order)
	{
		$siteId = $order->getSiteId();
		$userId = $order->getUserId();
		$fUserId = null;

		if ($userId > 0)
		{
			$fUserId = Sale\Fuser::getIdByUserId($userId);
		}

		$basketClassName = OrderRegistry::getBasketClassName();
		$basket = $basketClassName::create($siteId);
		$basket->setFUserId($fUserId);

		return $basket;
	}

	protected function getBasketDataPreserver()
	{
		if ($this->basketDataPreserver === null)
		{
			$this->basketDataPreserver = new Internals\BasketDataPreserver();
		}

		return $this->basketDataPreserver;
	}
}