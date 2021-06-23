<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\OrderAccept;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Marketplace\Action\OrderAccept\Action
{
	use TradingService\MarketplaceDbs\Concerns\Action\HasRegionHandler;
	use TradingService\MarketplaceDbs\Concerns\Action\HasDeliveryDates;
	use TradingService\MarketplaceDbs\Concerns\Action\HasAddress;

	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function sanitizeRegionMeaningfulValues($meaningfulValues)
	{
		if ($this->request->getOrder()->getDelivery()->getAddress() !== null)
		{
			$meaningfulValues = array_diff_key($meaningfulValues, [
				'LAT' => true,
				'LON' => true,
			]);
		}

		return $meaningfulValues;
	}

	protected function fillProperties()
	{
		$this->fillAddressProperties();
		$this->fillDeliveryDatesProperties();
		$this->fillUtilProperties();
	}

	protected function fillDelivery()
	{
		list($deliveryId, $price) = $this->resolveDelivery();

		if ((string)$deliveryId !== '')
		{
			$this->order->createShipment($deliveryId, $price);
		}
	}

	protected function resolveDelivery()
	{
		$deliveryRequest = $this->request->getOrder()->getDelivery();
		$partnerType = $deliveryRequest->getPartnerType();
		$price = null;

		if ($this->provider->getDelivery()->isShopDelivery($partnerType))
		{
			$deliveryId = $deliveryRequest->getShopDeliveryId();
			$price = $deliveryRequest->getPrice();
		}
		else
		{
			$deliveryId = $this->environment->getDelivery()->getEmptyDeliveryId();
		}

		return [$deliveryId, $price];
	}

	protected function fillOutlet()
	{
		$deliveryRequest = $this->request->getOrder()->getDelivery();
		$outletRequest = $deliveryRequest->getOutlet();
		$storeField = (string)$this->provider->getOptions()->getOutletStoreField();

		if ($outletRequest !== null && $storeField !== '')
		{
			$deliveryId = $deliveryRequest->getShopDeliveryId();
			$storeId = $this->environment->getStore()->findStore($storeField, $outletRequest->getCode());

			$this->order->setShipmentStore($deliveryId, $storeId);
		}
	}

	protected function resolvePaySystem()
	{
		$method = $this->request->getOrder()->getPaymentMethod();
		$compatibleIds = $this->getCompatiblePaySystems();
		$configuredIds = $this->getConfiguredPaySystemsForMethod($method);
		$matchedIds = array_intersect($compatibleIds, $configuredIds);
		$result = null;

		if (!empty($matchedIds))
		{
			$result = reset($matchedIds);
		}
		else if (!$this->provider->getOptions()->isPaySystemStrict())
		{
			$environmentPaySystem = $this->environment->getPaySystem();
			$servicePaySystem = $this->provider->getPaySystem();
			$meaningfulMap = $servicePaySystem->getMethodMeaningfulMap();

			if (!isset($meaningfulMap[$method])) { return null; }

			$meaningfulMethod = $meaningfulMap[$method];

			foreach ($compatibleIds as $compatibleId)
			{
				$suggestMethods = $environmentPaySystem->suggestPaymentMethod($compatibleId, $meaningfulMap);

				if (!empty($suggestMethods) && in_array($meaningfulMethod, $suggestMethods, true))
				{
					$result = $compatibleId;
					break;
				}
			}
		}

		return (string)$result;
	}

	protected function getCompatiblePaySystems()
	{
		$paySystem = $this->environment->getPaySystem();

		return $paySystem->getCompatible($this->order);
	}

	protected function getConfiguredPaySystemsForMethod($method)
	{
		$result = [];

		/** @var TradingService\MarketplaceDbs\Options\PaySystemOption $paySystemOption */
		foreach ($this->provider->getOptions()->getPaySystemOptions() as $paySystemOption)
		{
			if ($paySystemOption->getMethod() === $method)
			{
				$result[] = $paySystemOption->getPaySystemId();
			}
		}

		return $result;
	}

	protected function modifyPrice()
	{
		return Market\Result\Facade::merge([
			parent::modifyPrice(),
			$this->modifyDeliveryPrice()
		]);
	}

	protected function modifyDeliveryPrice()
	{
		list($deliveryId, $price) = $this->resolveDelivery();
		$result = new Market\Result\Base();

		if ((string)$deliveryId !== '' && $price !== null)
		{
			$setResult = $this->order->setShipmentPrice($deliveryId, $price);

			if (!$setResult->isSuccess())
			{
				$result->addErrors($setResult->getErrors());
			}
		}

		return $result;
	}
}