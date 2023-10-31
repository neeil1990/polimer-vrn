<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class DeliveryCourierFinder
{
	protected $provider;
	protected $environment;
	protected $order;
	protected $delivery;

	public function __construct(
		TradingService\MarketplaceDbs\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		TradingEntity\Reference\Order $order,
		TradingService\MarketplaceDbs\Model\Order\Delivery $delivery
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
		$this->order = $order;
		$this->delivery = $delivery;
	}

	/** @return array{int, TradingEntity\Reference\Outlet, Api\Model\Outlet}|null */
	public function execute()
	{
		$deliveryService = $this->deliveryService();
		$restrictedDeliveries = $this->restrictedDeliveries();
		$deliveryOptions = $this->provider->getOptions()->getDeliveryOptions();
		$configured = $this->configured($deliveryOptions, $restrictedDeliveries, $deliveryService);

		if (!empty($configured)) { return reset($configured); }

		$resolved = $this->resolved(
			array_diff($restrictedDeliveries, $deliveryOptions->getServiceIds()),
			$deliveryService
		);

		return !empty($resolved) ? reset($resolved) : null;
	}

	protected function restrictedDeliveries()
	{
		return $this->environment->getDelivery()->getRestricted($this->order);
	}

	protected function configured(
		TradingService\MarketplaceDbs\Options\DeliveryOptions $deliveryOptions,
		array $restricted,
		Api\Delivery\Services\Model\DeliveryService $deliveryService = null
	)
	{
		if ($deliveryService === null) { return []; }

		$result = [];
		$restrictedMap = array_flip($restricted);

		/** @var TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption */
		foreach ($deliveryOptions as $deliveryOption)
		{
			$deliveryId = $deliveryOption->getServiceId();

			if (!isset($restrictedMap[$deliveryId])) { continue; }
			if ($deliveryOption->getType() !== TradingService\MarketplaceDbs\Delivery::TYPE_DELIVERY) { continue; }

			$environmentCourier = $this->environment->getCourierRegistry()->resolveCourier($deliveryId);

			if (
				$environmentCourier instanceof TradingEntity\Reference\CourierInvertible
				&& $environmentCourier->isMatchService($deliveryService)
			)
			{
				$result[] = $deliveryId;
			}
		}

		return $result;
	}

	protected function resolved(array $restricted, Api\Delivery\Services\Model\DeliveryService $deliveryService = null)
	{
		if ($deliveryService === null) { return []; }

		$result = [];

		foreach ($restricted as $deliveryId)
		{
			$environmentCourier = $this->environment->getCourierRegistry()->resolveCourier($deliveryId);

			if (
				$environmentCourier instanceof TradingEntity\Reference\CourierInvertible
				&& $environmentCourier->isMatchService($deliveryService)
			)
			{
				$result[] = $deliveryId;
			}
		}

		return $result;
	}

	protected function deliveryService()
	{
		try
		{
			$serviceId = (int)$this->delivery->getServiceId();

			if ($serviceId === TradingService\MarketplaceDbs\Delivery::SHOP_SERVICE_ID) { return null; }

			/** @var Api\Delivery\Services\Model\DeliveryService $result */
			$deliveryServices = Api\Delivery\Services\Facade::load($this->provider->getOptions(), $this->provider->getLogger());
			$result = $deliveryServices->getItemById($serviceId);
		}
		catch (Main\SystemException $exception)
		{
			$this->provider->getLogger()->warning($exception);
			$result = null;
		}

		return $result;
	}
}