<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class DeliveryPickupFinder
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

		$found = $this->mapAddress($configured);

		if ($found !== null) { return $found; }

		$resolved = $this->resolved(
			array_diff($restrictedDeliveries, $deliveryOptions->getServiceIds()),
			$deliveryService
		);

		return $this->mapAddress($resolved);
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
			if ($deliveryOption->getType() !== TradingService\MarketplaceDbs\Delivery::TYPE_PICKUP) { continue; }

			$outletType = $deliveryOption->getOutletType();

			if ($outletType === $deliveryOption::OUTLET_TYPE_MANUAL) { continue; }

			$environmentOutlet = $this->environment->getOutletRegistry()->getOutlet($outletType);

			if (
				$environmentOutlet instanceof TradingEntity\Reference\OutletInvertible
				&& $environmentOutlet->isMatchService($deliveryService)
			)
			{
				$result[$deliveryId] = $environmentOutlet;
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
			$environmentOutlet = $this->environment->getOutletRegistry()->resolveOutlet($deliveryId);

			if (
				$environmentOutlet instanceof TradingEntity\Reference\OutletInvertible
				&& $environmentOutlet->isMatchService($deliveryService)
			)
			{
				$result[$deliveryId] = $environmentOutlet;
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

	/**
	 * @param array<int, TradingEntity\Reference\OutletInvertible> $environmentOutlets
	 *
	 * @return array{int, TradingEntity\Reference\Outlet}|null
	 */
	protected function mapAddress(array $environmentOutlets)
	{
		$incomingAddress = $this->delivery->getAddress();
		$incomingOutlet = $this->delivery->getOutlet();
		$incomingCode = $incomingOutlet !== null && $incomingOutlet->hasField('code') ? $incomingOutlet->getCode() : null;
		$result = null;

		foreach ($environmentOutlets as $deliveryId => $environmentOutlet)
		{
			$outlet = null;

			if ($incomingCode !== null)
			{
				$outlet = $environmentOutlet->searchByCode($this->order, $this->delivery->getRegion(), $incomingCode);
			}

			if ($incomingAddress !== null && $outlet === null)
			{
				$outlet = $environmentOutlet->searchByAddress($this->order, $this->delivery->getRegion(), $incomingAddress);
			}

			if ($outlet === null) { continue; }

			$result = [ $deliveryId, $environmentOutlet, $outlet ];
			break;
		}

		return $result;
	}
}