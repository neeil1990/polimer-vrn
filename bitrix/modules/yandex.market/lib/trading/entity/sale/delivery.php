<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Bitrix\Main;
use Bitrix\Sale;
use Sale\Handlers as SaleHandlers;

class Delivery extends Market\Trading\Entity\Reference\Delivery
{
	use Market\Reference\Concerns\HasLang;

	/** @var Environment */
	protected $environment;
	/** @var Sale\Delivery\Services\Base[] */
	protected $deliveryServices = [];
	/** @var array<string, bool> */
	protected $existsDeliveryDiscount = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function isRequired()
	{
		$saleVersion = Main\ModuleManager::getVersion('sale');

		return !CheckVersion($saleVersion, '17.0.0');
	}

	public function getEnum($siteId = null)
	{
		$deliveries = $this->loadActiveList();
		$deliveries = $this->filterBySite($deliveries, $siteId);

		return $deliveries;
	}

	protected function loadActiveList()
	{
		$listByParent = [];

		foreach (Sale\Delivery\Services\Manager::getActiveList(true) as $id => $fields)
		{
			if ($delivery = Sale\Delivery\Services\Manager::createObject($fields))
			{
				$name = $delivery->getName();
				$parent = $delivery->getParentService();
				$parentId = $parent ? $parent->getId() : 0;

				if (!isset($listByParent[$parentId]))
				{
					$listByParent[$parentId] = [];
				}

				$listByParent[$parentId][] = [
					'ID' => $id,
					'VALUE' => sprintf('[%s] %s', $id, $name),
					'TYPE' => $this->getDeliveryServiceType($delivery),
					'GROUP' => $parent ? $parent->getName() : null,
				];
			}
		}

		return !empty($listByParent) ? array_merge(...$listByParent) : [];
	}

	protected function getDeliveryServiceType(Sale\Delivery\Services\Base $deliveryService)
	{
		if ((int)$deliveryService->getId() === $this->getEmptyDeliveryId())
		{
			$result = Market\Data\Trading\Delivery::EMPTY_DELIVERY;
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	/** @noinspection PhpInternalEntityUsedInspection */
	protected function filterBySite($deliveryServices, $siteId)
	{
		$result = [];

		if ($siteId === null)
		{
			$result = $deliveryServices;
		}
		else if (!empty($deliveryServices))
		{
			$deliveryIds = array_column($deliveryServices, 'ID');

			if (count($deliveryIds) === 1) // if only one then result boolean
			{
				$deliveryIds[] = -1;
			}

			$validServices = Sale\Delivery\Services\Manager::checkServiceRestriction(
				$deliveryIds,
				$siteId,
				'\Bitrix\Sale\Delivery\Restrictions\BySite'
			);

			if (is_array($validServices))
			{
				$validServicesMap = array_flip($validServices);
			}
			else // is older version
			{
				$validServicesMap = [];

				foreach ($deliveryServices as $delivery)
				{
					$isValid = Sale\Delivery\Services\Manager::checkServiceRestriction(
						$delivery['ID'],
						$siteId,
						'\Bitrix\Sale\Delivery\Restrictions\BySite'
					);

					if ($isValid)
					{
						$validServicesMap[$delivery['ID']] = true;
					}
				}
			}

			foreach ($deliveryServices as $deliveryService)
			{
				if (isset($validServicesMap[$deliveryService['ID']]))
				{
					$result[] = $deliveryService;
				}
			}
		}

		return $result;
	}

	public function filterServicesWithoutPeriod(array $deliveryIds)
	{
		if (empty($deliveryIds)) { return []; }

		$result = [];
		$query = Sale\Delivery\Services\Table::getList([
			'filter' => [ '=ID' => $deliveryIds ],
		]);

		while ($deliveryRow = $query->fetch())
		{
			if ($this->hasServicePeriod($deliveryRow)) { continue; }

			$result[] = $deliveryRow['ID'];
		}

		return $result;
	}

	protected function hasServicePeriod(array $deliveryRow)
	{
		if (!isset($deliveryRow['CLASS_NAME'])) { return false; }

		$deliveryClassName = ltrim($deliveryRow['CLASS_NAME'], '\\');
		$deliveryClassName = Market\Data\TextString::toLower($deliveryClassName);

		$result = false;
		/** @noinspection PhpUndefinedClassInspection */
		/** @noinspection PhpUndefinedNamespaceInspection */
		$types = [
			'configurable' => Sale\Delivery\Services\Configurable::class,
			'automatic' => Sale\Delivery\Services\AutomaticProfile::class,
			'additional' => \Sale\Handlers\Delivery\AdditionalProfile::class,
			'ruspost' => \Sale\Handlers\Delivery\RussianpostProfile::class,
			'dostavista' => \Sale\Handlers\Delivery\DostavistaHandler::class,
			'yandexTaxi' => \Sale\Handlers\Delivery\YandextaxiProfile::class,
			'ipolOzonCourier' => \Ipol\Ozon\Bitrix\Handler\DeliveryHandlerCourier::class,
			'ipolOzonPickup' => \Ipol\Ozon\Bitrix\Handler\DeliveryHandlerPickup::class,
			'ipolOzonPostamat' => \Ipol\Ozon\Bitrix\Handler\DeliveryHandlerPostamat::class,
		];

		foreach ($types as $type => $className)
		{
			$className = ltrim($className, '\\');
			$className = Market\Data\TextString::toLower($className);

			if ($className !== $deliveryClassName) { continue; }

			$method = 'has' . ucfirst($type) . 'ServicePeriod';

			if (method_exists($this, $method))
			{
				$result = $this->{$method}($deliveryRow);
			}
			else
			{
				$result = true;
			}

			break;
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function hasConfigurableServicePeriod(array $deliveryRow)
	{
		if (!isset($deliveryRow['CONFIG']['MAIN']['PERIOD'])) { return false; }

		$result = false;
		$configPeriod = $deliveryRow['CONFIG']['MAIN']['PERIOD'];
		$keys = [
			'FROM',
			'TO',
		];

		foreach ($keys as $key)
		{
			if (isset($configPeriod[$key]) && (string)$configPeriod[$key] !== '')
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function hasAutomaticServicePeriod(array $deliveryRow)
	{
		if (empty($deliveryRow['CODE'])) { return false; }

		$result = false;
		$matched = [
			'rus_post',
			'ipolh_dpd',
			'boxberry',
			'sdek',
		];

		foreach ($matched as $code)
		{
			if (Market\Data\TextString::getPosition($deliveryRow['CODE'], $code) === 0)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public function getEmptyDeliveryId()
	{
		return (int)Sale\Delivery\Services\Manager::getEmptyDeliveryServiceId();
	}

	public function getRestricted(TradingEntity\Reference\Order $order)
	{
		$result = [];
		$calculatableOrder = $this->getOrderCalculatable($order);
		$shipment = $this->getCalculatableShipment($calculatableOrder);
		$services = Sale\Delivery\Services\Manager::getRestrictedList(
			$shipment,
			Sale\Delivery\Restrictions\Manager::MODE_CLIENT
		);

		foreach ($services as $serviceParameters)
		{
			try
			{
				/** @var class-string<Sale\Delivery\Services\Base> $serviceClassName */
				$serviceClassName = $serviceParameters['CLASS_NAME'];
				$serviceId = (int)$serviceParameters['ID'];

				if (
					$serviceId <= 0
					|| !class_exists($serviceClassName)
					|| $serviceClassName::canHasProfiles()
					|| (
						is_callable($serviceClassName . '::canHasChildren')
						&& $serviceClassName::canHasChildren()
					)
				)
				{
					continue;
				}

				$service = $this->getDeliveryService($serviceId);

				if (
					!$service
					|| $this->getDeliveryServiceType($service) === Market\Data\Trading\Delivery::EMPTY_DELIVERY
				)
				{
					continue;
				}

				if (
					!$this->isOrderLocationFilled($calculatableOrder)
					&& $this->hasDeliveryLocationRestriction($serviceId)
				)
				{
					continue;
				}

				$result[] = $serviceId;
			}
			catch (Main\SystemException $exception)
			{
				// silence
			}
		}

		return $result;
	}

	protected function isOrderLocationFilled(Sale\OrderBase $order)
	{
		$propertyCollection = $order->getPropertyCollection();

		if ($propertyCollection === null) { return false; }

		$locationProperty = $propertyCollection->getDeliveryLocation();

		if ($locationProperty === null) { return false; }

		return (string)$locationProperty->getValue() !== '';
	}

	protected function hasDeliveryLocationRestriction($serviceId)
	{
		$result = false;
		$restrictions = Sale\Delivery\Restrictions\Manager::getRestrictionsList($serviceId);

		foreach ($restrictions as $restriction)
		{
			if (
				$restriction['CLASS_NAME'] === '\Bitrix\Sale\Delivery\Restrictions\ByLocation'
				|| $restriction['CLASS_NAME'] === '\Bitrix\Sale\Delivery\Restrictions\ExcludeLocation'
			)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public function isCompatible($deliveryId, TradingEntity\Reference\Order $order)
	{
		try
		{
			$calculatableOrder = $this->getOrderCalculatable($order);
			$shipment = $this->getCalculatableShipment($calculatableOrder);
			$deliveryService = $this->getDeliveryService($deliveryId);

			$result = $deliveryService->isCompatible($shipment);
		}
		catch (Main\SystemException $exception)
		{
			$result = false;
		}

		return $result;
	}

	public function calculate($deliveryId, TradingEntity\Reference\Order $order)
	{
		$result = new TradingEntity\Reference\Delivery\CalculationResult();

		try
		{
			$calculatableOrder = $this->getOrderCalculatable($order);
			$shipment = $this->getCalculatableShipment($calculatableOrder);
			$deliveryService = $this->getDeliveryService($deliveryId);
			$currency = $shipment->getCurrency();

			$calculatableOrder->isStartField();

			$shipment->setDeliveryService($deliveryService);

			if ($currency !== $deliveryService->getCurrency())
			{
				$deliveryService->getExtraServices()->setOperationCurrency($currency);
			}

			$calculationResult = $shipment->calculateDelivery();

			if ($calculationResult->isSuccess())
			{
				$shipment->setField('BASE_PRICE_DELIVERY', $calculationResult->getPrice());
			}

			if ($this->hasDeliveryDiscount($calculatableOrder))
			{
				$calculatableOrder->doFinalAction(true);
			}

			Delivery\CalculationFacade::mergeCalculationResult($result, $calculationResult);
			Delivery\CalculationFacade::mergeDeliveryService($result, $deliveryService);
			Delivery\CalculationFacade::mergeOrderData($result, $calculatableOrder);
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Market\Error\Base(
				$exception->getMessage(),
				$exception->getCode()
			));
		}

		return $result;
	}

	public function configureShipment(Order $order, $deliveryId)
	{
		$calculatableOrder = $this->getOrderCalculatable($order);
		$shipment = $this->getCalculatableShipment($calculatableOrder);
		$deliveryService = $this->getDeliveryService($deliveryId);

		$shipment->setDeliveryService($deliveryService);
	}

	public function getDeliveryService($deliveryId)
	{
		if (!isset($this->deliveryServices[$deliveryId]))
		{
			$this->deliveryServices[$deliveryId] = $this->loadDeliveryService($deliveryId);
		}

		return $this->deliveryServices[$deliveryId];
	}

	protected function loadDeliveryService($deliveryId)
	{
		$deliveryService = Sale\Delivery\Services\Manager::getObjectById($deliveryId);

		if ($deliveryService === null)
		{
			$message = static::getLang('TRADING_ENTITY_SALE_DELIVERY_SERVICE_NOT_FOUND', [
				'#ID#' => $deliveryId,
			]);
			throw new Main\SystemException($message);
		}

		return $deliveryService;
	}

	/**
	 * @param Order $order
	 *
	 * @return Sale\Order
	 */
	protected function getOrderCalculatable(TradingEntity\Reference\Order $order)
	{
		if (!($order instanceof Order))
		{
			throw new Main\NotSupportedException('only Sale\Order calculation supported');
		}

		return $order->getCalculatable();
	}

	protected function getCalculatableShipment(Sale\Order $order)
	{
		$result = null;

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if (!$shipment->isSystem())
			{
				$result = $shipment;
				break;
			}
		}

		if ($result === null)
		{
			$message = static::getLang('TRADING_ENTITY_SALE_DELIVERY_CALCULATED_SHIPMENT_NOT_FOUND');
			throw new Main\SystemException($message);
		}

		return $result;
	}

	protected function hasDeliveryDiscount(Sale\Order $order)
	{
		$siteId = $order->getSiteId();
		$userGroups = Market\Data\UserGroup::getUserGroups($order->getUserId());
		$cacheKey = $siteId . '|' . implode('.', $userGroups);

		if (!isset($this->existsDeliveryDiscount[$cacheKey]))
		{
			$this->existsDeliveryDiscount[$cacheKey] = $this->searchDeliveryDiscount($siteId, $userGroups);
		}

		return $this->existsDeliveryDiscount[$cacheKey];
	}

	protected function searchDeliveryDiscount($siteId, $userGroups)
	{
		if (!method_exists('CSaleActionCtrlDelivery', 'GetControlID')) { return false; }

		// query discounts

		$queryDiscounts = Sale\Internals\DiscountTable::getList([
			'filter' => [
				'=LID' => $siteId,
				'=ACTIVE' => 'Y',
				[
					'LOGIC' => 'OR',
					'ACTIVE_FROM' => null,
					'>=ACTIVE_FROM' => new Main\Type\DateTime(),
				],
				[
					'LOGIC' => 'OR',
					'ACTIVE_TO' => null,
					'<=ACTIVE_TO' => new Main\Type\DateTime(),
				],
				[
					'LOGIC' => 'OR',
					'%ACTIONS' => serialize(\CSaleActionCtrlDelivery::GetControlID()),
					'%APPLICATION' => '::applyToDelivery(',
				],
			],
			'select' => [ 'ID' ],
		]);

		$discounts = $queryDiscounts->fetchAll();

		if (empty($discounts)) { return false; }

		// test user group access

		$queryAccess = Sale\Internals\DiscountGroupTable::getList(array(
			'select' => ['DISCOUNT_ID'],
			'filter' => [
				'=DISCOUNT_ID' => array_column($discounts, 'ID'),
				'=GROUP_ID' => $userGroups,
				'=ACTIVE' => 'Y',
			],
			'limit' => 1,
		));

		return (bool)$queryAccess->fetch();
	}

	public function suggestDeliveryType($deliveryId, array $supportedTypes = null)
	{
		$implementedTypes = $this->getSuggestImplementedDeliveryTypes();
		$processTypes = ($supportedTypes === null)
			? $implementedTypes
			: array_intersect($supportedTypes, $implementedTypes);

		if (empty($processTypes)) { return null; }

		$deliveryService = $this->getDeliveryService($deliveryId);
		$result = null;

		foreach ($processTypes as $type)
		{
			if ($this->matchDeliveryType($deliveryService, $type))
			{
				$result = $type;
				break;
			}
		}

		return $result;
	}

	protected function matchDeliveryType(Sale\Delivery\Services\Base $deliveryService, $type)
	{
		$methodName = 'matchDeliveryType' . ucfirst($type);
		$result = false;

		if (method_exists($this, $methodName))
		{
			$result = (bool)$this->{$methodName}($deliveryService);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function matchDeliveryTypeDelivery(Sale\Delivery\Services\Base $deliveryService)
	{
		if ($deliveryService instanceof SaleHandlers\Delivery\SimpleHandler)
		{
			$result = true;
		}
		else if ($deliveryService instanceof Sale\Delivery\Services\Configurable)
		{
			$stores = Sale\Delivery\ExtraServices\Manager::getStoresList($deliveryService->getId());
			$result = empty($stores);
		}
		else
		{
			$courier = $this->environment->getCourierRegistry()->resolveCourier($deliveryService->getId());
			$result = ($courier !== null);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function matchDeliveryTypePickup(Sale\Delivery\Services\Base $deliveryService)
	{
		$deliveryId = $deliveryService->getId();
		$stores = Sale\Delivery\ExtraServices\Manager::getStoresList($deliveryId);

		if (!empty($stores)) { return true; }

		$outlet = $this->environment->getOutletRegistry()->resolveOutlet($deliveryId);

		return $outlet !== null;
	}

	/** @noinspection PhpUnused */
	protected function matchDeliveryTypePost(Sale\Delivery\Services\Base $deliveryService)
	{
		$result = false;
		$conditions = [
			'code',
			'serviceType',
		];

		foreach ($conditions as $condition)
		{
			$method = 'testDeliveryTypePostBy' . ucfirst($condition);

			if ($this->{$method}($deliveryService))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected function testDeliveryTypePostByCode(Sale\Delivery\Services\Base $deliveryService)
	{
		if (!($deliveryService instanceof SaleHandlers\Delivery\AdditionalProfile)) { return false; }

		$parentService = $deliveryService->getParentService();
		$parentConfig = $parentService && method_exists($parentService, 'getConfigValues') ? $parentService->getConfigValues() : null;

		return (
			isset($parentConfig['MAIN']['SERVICE_TYPE'])
			&& Market\Data\TextString::getPositionCaseInsensitive($parentConfig['MAIN']['SERVICE_TYPE'], 'post') !== false
		);
	}

	/** @noinspection PhpUnused */
	protected function testDeliveryTypePostByServiceType(Sale\Delivery\Services\Base $deliveryService)
	{
		$serviceCode = $deliveryService->getCode();

		return (Market\Data\TextString::getPositionCaseInsensitive($serviceCode, 'post') !== false);
	}

	protected function getSuggestImplementedDeliveryTypes()
	{
		return [
			'DELIVERY',
			'PICKUP',
			'POST',
		];
	}

	public function debugData($deliveryId)
	{
		$result = [];

		try
		{
			$deliveryId = (int)$deliveryId;

			if ($deliveryId <= 0) { return []; }

			$service = $this->getDeliveryService($deliveryId);
			$result['name'] = $service::getClassTitle();
			$result['code'] = $service->getCode();

			if (
				$service instanceof Sale\Delivery\Services\Configurable
				&& method_exists($service, 'getConfigValues')
			)
			{
				$config = $service->getConfigValues();

				$result['period'] = [
					'from' => $config['MAIN']['PERIOD']['FROM'],
					'to' => $config['MAIN']['PERIOD']['TO'],
					'unit' => $config['MAIN']['PERIOD']['TYPE'],
				];
			}
		}
		catch (\Exception $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}
		/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
		catch (\Throwable $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}

		return $result;
	}
}