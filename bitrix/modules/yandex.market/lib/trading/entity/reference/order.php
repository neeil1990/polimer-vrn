<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Yandex\Market\Trading\Entity\Operation as EntityOperation;
use Bitrix\Main;

abstract class Order
{
	protected $internalOrder;
	protected $environment;
	protected $calculationMode = EntityOperation\PriceCalculation::DELIVERY | EntityOperation\PriceCalculation::PRODUCT;

	public function __construct(Environment $environment, $internalOrder)
	{
		$this->environment = $environment;
		$this->internalOrder = $internalOrder;
	}

	/**
	 * @return string
	 */
	public function getAdminEditUrl()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getAdminEditUrl');
	}

	/**
	 * @param int    $userId
	 * @param string $operation
	 *
	 * @return bool
	 */
	public function hasAccess($userId, $operation)
	{
		return true;
	}

	/**
	 * @return string|int
	 */
	public function getId()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getId');
	}

	/**
	 * @return int|null
	 */
	public function getUserId()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getUserId');
	}

	/**
	 * @return string
	 */
	public function getSiteId()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getSiteId');
	}

	/**
	 * @return string|int
	 */
	public function getAccountNumber()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getAccountNumber');
	}

	/**
	 * @return Main\Type\DateTime
	 */
	public function getCreationDate()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getCreationDate');
	}

	/**
	 * @return string
	 */
	public function getCurrency()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getCurrency');
	}

	/**
	 * @return string
	 */
	public function getReasonCanceled()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getReasonCanceled');
	}

	/**
	 * @return string|null
	 */
	public function getProfileName()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getProfileName');
	}

	/**
	 * @return array<int, mixed>
	 */
	public function getPropertyValues()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getPropertyValues');
	}

	/**
	 * @param int $propertyId
	 *
	 * @return mixed
	 */
	public function getPropertyValue($propertyId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getPropertyValue');
	}

	/**
	 * Изменение пользователя заказа после регистрации
	 *
	 * @param int $userId
	 *
	 * @return Main\Result
	 */
	public function setUserId($userId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setUserId');
	}

	/**
	 * @return int
	 */
	public function getPersonType()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getPersonType');
	}

	/**
	 * @param int $personType
	 *
	 * @return Main\Result
	 */
	public function setPersonType($personType)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setPersonType');
	}

	public function setCalculationMode($mode = null)
	{
		$this->calculationMode = $mode;
	}

	protected function needCalculate($operation)
	{
		return $operation & $this->calculationMode;
	}

	/**
	 * Подготовка заказа перед наполнением
	 */
	public function initialize()
	{
		// nothing by default
	}

	public function fillAccountNumber($accountNumber)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillAccountNumber');
	}

	/**
	 * @param int|null $externalId
	 * @param Platform $platform
	 */
	public function fillXmlId($externalId, Platform $platform)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillXmlId');
	}

	/**
	 * @param int $setupId
	 * @param Platform $platform
	 */
	public function fillTradingSetup($setupId, Platform $platform)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillTradingSetup');
	}

	/**
	 * @param array $values
	 * @param bool $onlyEmpty
	 *
	 * @return Main\Result
	 */
	public function fillProperties(array $values, $onlyEmpty = false)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillProperties');
	}

	/**
	 * @return Main\Result
	 */
	public function resetLocation()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'resetLocation');
	}

	/**
	 * @param int $locationId
	 *
	 * @return Main\Result
	 */
	public function setLocation($locationId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setLocation');
	}

	/**
	 * @param int|string $productId
	 * @param int        $count
	 * @param array|null $data
	 *
	 * @return Main\Result
	 */
	public function addProduct($productId, $count = 1, array $data = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'addProduct');
	}

	/**
	 * @param array $basketMarkings
	 *
	 * @return Main\Result
	 */
	public function fillMarking(array $basketMarkings)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillMarking');
	}

	/**
	 * @return string[]
	 */
	public function getExistsBasketItemCodes()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getExistsBasketItemCodes');
	}

	/**
	 * @param $basketCode
	 * @param array $expected
	 *
	 * @return array
	 */
	public function debugBasketItem($basketCode, array $expected = [])
	{
		return [];
	}

	/**
	 * @param $value
	 * @param $field
	 *
	 * @return string|null
	 */
	public function getBasketItemCode($value, $field = 'PRODUCT_ID')
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getBasketItemCode');
	}

	/**
	 * @param $basketCode
	 *
	 * @return Main\Result
	 */
	public function getBasketItemData($basketCode)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getBasketItemData');
	}

	/**
	 * @return float
	 */
	public function getBasketPrice()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getBasketPrice');
	}

	/**
	 * @param string $basketCode
	 * @param float $price
	 *
	 * @return Main\Result
	 */
	public function setBasketItemPrice($basketCode, $price)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setBasketItemPrice');
	}

	/**
	 * @param string $basketCode
	 * @param float $quantity
	 * @param bool $silent
	 *
	 * @return Main\Result
	 */
	public function setBasketItemQuantity($basketCode, $quantity, $silent = false)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setBasketItemQuantity');
	}

	/**
	 * @param array $stores
	 *
	 * @return Main\Result
	 */
	public function setBasketStore(array $stores)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setBasketStore');
	}

	/**
	 * @param string $basketCode
	 * @param string|int $storeId
	 *
	 * @return Main\Result
	 */
	public function setBasketItemStore($basketCode, $storeId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setBasketItemStore');
	}

	/**
	 * @param string $basketCode
	 *
	 * @return Main\Result
	 */
	public function deleteBasketItem($basketCode)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'deleteBasketItem');
	}

	/**
	 * @param int $deliveryId
	 * @param float|null $price
	 * @param array|null $data
	 *
	 * @return Main\Result
	 */
	public function createShipment($deliveryId, $price = null, array $data = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'createShipment');
	}

	/** @return int|null */
	public function getDeliveryId()
	{
		return null;
	}

	/**
	 * @param int $deliveryId
	 *
	 * @return float|null
	 */
	public function getShipmentPrice($deliveryId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getShipmentPrice');
	}

	/**
	 * @param int $deliveryId
	 * @param float $price
	 *
	 * @return Main\Result
	 */
	public function setShipmentPrice($deliveryId, $price)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setShipmentPrice');
	}

	/**
	 * @param int $deliveryId
	 * @param int|null $storeId
	 *
	 * @return Main\Result
	 */
	public function setShipmentStore($deliveryId, $storeId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setShipmentStore');
	}

	/** @return int|null */
	public function getPaySystemId()
	{
		return null;
	}

	/**
	 * @param int $paySystemId
	 * @param float|null $price
	 * @param array|null $data
	 *
	 * @return Main\Result
	 */
	public function createPayment($paySystemId, $price = null, array $data = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'createPayment');
	}

	/**
	 * @param string $notes
	 *
	 * @return Main\Result
	 */
	public function setNotes($notes)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setNotes');
	}

	/**
	 * @return array<int, int> contactType => contactId
	 */
	public function getContacts()
	{
		return [];
	}

	/**
	 * @param array<int, int> $contacts contactType => contactId
	 *
	 * @return Main\Result
	 */
	public function fillContacts(array $contacts)
	{
		return new Main\Result();
	}

	/**
	 * Актуализация заказа после наполнения
	 */
	public function finalize()
	{
		// nothing by default
	}

	/**
	 * Остановить автоматический пересчет
	 */
	public function freeze()
	{
		// nothing by default
	}

	/**
	 * Выполнить автоматический пересчет
	 */
	public function unfreeze()
	{
		// nothing by default
	}

	/**
	 * @param string $code
	 * @param string|null $condition
	 *
	 * @return bool
	 */
	public function isExistMarker($code, $condition = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'isExistMarker');
	}

	/**
	 * @param string $message
	 * @param string $code
	 *
	 * @return Main\Result
	 */
	public function addMarker($message, $code)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'addMarker');
	}

	/**
	 * @param string $code
	 *
	 * @return Main\Result
	 */
	public function removeMarker($code)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'removeMarker');
	}

	/**
	 * @return string[]
	 */
	public function getStatuses()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getStatuses');
	}

	/**
	 * @param string $status
	 * @param mixed $payload
	 *
	 * @return Main\Result
	 */
	public function setStatus($status, $payload = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'setStatus');
	}

	public function resetCashbox()
	{
		// nothing by default
	}

	/**
	 * @param string $externalId
	 * @param Platform $platform
	 *
	 * @return Main\Result
	 */
	public function add($externalId, Platform $platform)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'add');
	}

	/**
	 * @return Main\Result
	 */
	public function update()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'update');
	}
}