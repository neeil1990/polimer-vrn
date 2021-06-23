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
				/** @var Sale\Delivery\Services\Base $serviceClassName */
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

				$result[] = $serviceId;
			}
			catch (Main\SystemException $exception)
			{
				// silence
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

			$calculatableOrder->doFinalAction(true);

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

	protected function getDeliveryService($deliveryId)
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

	protected function matchDeliveryTypePickup(Sale\Delivery\Services\Base $deliveryService)
	{
		$deliveryId = $deliveryService->getId();
		$stores = Sale\Delivery\ExtraServices\Manager::getStoresList($deliveryId);

		return !empty($stores);
	}

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

	protected function testDeliveryTypePostByServiceType(Sale\Delivery\Services\Base $deliveryService)
	{
		$serviceCode = $deliveryService->getCode();

		return (Market\Data\TextString::getPositionCaseInsensitive($serviceCode, 'post') !== false);
	}

	protected function getSuggestImplementedDeliveryTypes()
	{
		return [
			'PICKUP',
			'POST',
		];
	}
}