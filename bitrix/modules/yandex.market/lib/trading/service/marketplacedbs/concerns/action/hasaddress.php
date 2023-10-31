<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasMeaningfulProperties
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingService\MarketplaceDbs\Action\Cart\Request|TradingService\MarketplaceDbs\Action\OrderAccept\Request|TradingService\MarketplaceDbs\Action\OrderStatus\Request $request
 * @property TradingEntity\Reference\Order $order
 * @method setMeaningfulPropertyValues($propertyValues)
 * @method getConfiguredMeaningfulProperties($meaningfulNames)
 */
trait HasAddress
{
	protected function fillAddressProperties()
	{
		$delivery = $this->getRequestDelivery();
		$address = $this->getRequestAddress();

		if ($address === null) { return; }

		$propertyValues = $this->getAddressProperties($address);
		$propertyValues = $this->extendDeliveryProperties($delivery, $propertyValues);

		$this->setMeaningfulPropertyValues($propertyValues);
	}

	public function getAddressProperties(TradingService\MarketplaceDbs\Model\Cart\Delivery\Address $address)
	{
		$useDetails = $this->provider->getOptions()->useAddressDetails();
		$configuredAddressParts = $useDetails ? $this->getConfiguredAddressParts() : [];
		$result = [
			'ZIP' => $address->getMeaningfulZip(),
			'CITY' => $address->getMeaningfulCity(),
			'ADDRESS' => $address->getMeaningfulAddress($configuredAddressParts),
			'LAT' => $address->getLat(),
			'LON' => $address->getLon(),
		];

		if ($useDetails)
		{
			$result += $address->getAddressValues();
		}

		return $result;
	}

	protected function extendDeliveryProperties(Market\Api\Model\Cart\Delivery $delivery, $propertyValues)
	{
		if (!($delivery instanceof TradingService\MarketplaceDbs\Model\Order\Delivery)) { return $propertyValues; }

		/** @var Market\Api\Model\Cart $cart */
		$cart = $delivery->getParent();
		$liftValues = $this->getDeliveryLiftProperties($delivery, $cart->getCurrency());
		$liftIgnore = $this->getDeliveryLiftIgnored($cart->getCurrency());
		$dispatchTypeValues = $this->getDeliveryDispatchTypeProperties($delivery);
		$dispatchTypeIgnore = $this->getDeliveryDispatchTypeIgnored();
		$values = $liftValues + $dispatchTypeValues;
		$ignore = $liftIgnore + $dispatchTypeIgnore;
		$needConfigured = array_merge(
			$this->provider->getOptions()->useAddressDetails() ? array_keys($liftValues) : [],
			array_keys($dispatchTypeValues)
		);
		$configuredMap = $this->getConfiguredMeaningfulProperties($needConfigured);

		if (isset($values['LIFT_PRICE']) && !isset($configuredMap['LIFT_PRICE']) && $this->provider->getOptions()->includeLiftPrice())
		{
			unset($values['LIFT_PRICE']);
		}

		foreach ($values as $key => $value)
		{
			if (isset($configuredMap[$key]))
			{
				$propertyValues[$key] = $value;
			}
			else if ((string)$value !== '')
			{
				if (isset($ignore[$key]))
				{
					if (is_array($ignore[$key]))
					{
						$needIgnore = false;

						foreach ($ignore[$key] as $ignoreValue)
						{
							if ((string)$ignoreValue === (string)$value)
							{
								$needIgnore = true;
								break;
							}
						}
					}
					else
					{
						$needIgnore = (string)$ignore[$key] === (string)$value;
					}

					if ($needIgnore) { continue; }
				}

				$propertyValues['ADDRESS'] = $this->insertAddressValueAdditional($propertyValues['ADDRESS'], $value);
			}
		}

		return $propertyValues;
	}

	protected function getDeliveryLiftProperties(TradingService\MarketplaceDbs\Model\Order\Delivery $delivery, $currency)
	{
		if ($delivery->getLiftType() === null) { return []; }

		$deliveryService = $this->provider->getDelivery();

		return [
			'LIFT_TYPE' => new Market\Data\Type\EnumValue(
				$delivery->getLiftType(),
				$deliveryService->getLiftTitle($delivery->getLiftType(), 'ALONE')
			),
			'LIFT_PRICE' => Market\Data\Currency::format(
				$delivery->getLiftPrice(),
				$currency
			),
		];
	}

	protected function getDeliveryLiftIgnored($currency)
	{
		$deliveryService = $this->provider->getDelivery();

		return [
			'LIFT_TYPE' => new Market\Data\Type\EnumValue(
				TradingService\MarketplaceDbs\Delivery::LIFT_NOT_NEEDED,
				$deliveryService->getLiftTitle(TradingService\MarketplaceDbs\Delivery::LIFT_NOT_NEEDED, 'ALONE')
			),
			'LIFT_PRICE' => Market\Data\Currency::format(0, $currency),
		];
	}

	protected function getDeliveryDispatchTypeProperties(TradingService\MarketplaceDbs\Model\Order\Delivery $delivery)
	{
		if ($delivery->getDispatchType() === null) { return []; }

		$deliveryService = $this->provider->getDelivery();

		return [
			'DISPATCH_TYPE' => new Market\Data\Type\EnumValue(
				$delivery->getDispatchType(),
				$deliveryService->getDispatchTypeTitle($delivery->getDispatchType())
			),
		];
	}

	protected function getDeliveryDispatchTypeIgnored()
	{
		$deliveryService = $this->provider->getDelivery();

		return [
			'DISPATCH_TYPE' => [
				new Market\Data\Type\EnumValue(
					TradingService\MarketplaceDbs\Delivery::DISPATCH_TYPE_SHOP_OUTLET,
					$deliveryService->getDispatchTypeTitle(TradingService\MarketplaceDbs\Delivery::DISPATCH_TYPE_SHOP_OUTLET)
				),
				new Market\Data\Type\EnumValue(
					TradingService\MarketplaceDbs\Delivery::DISPATCH_TYPE_BUYER,
					$deliveryService->getDispatchTypeTitle(TradingService\MarketplaceDbs\Delivery::DISPATCH_TYPE_BUYER)
				),
			],
		];
	}

	protected function insertAddressValueAdditional($address, $additional)
	{
		if (preg_match('/^(.*?) \((.*)\)$/', $address, $matches))
		{
			$result = sprintf('%s (%s, %s)', $matches[1], $matches[2], $additional);
		}
		else
		{
			$result = $address . ' (' . $additional . ')';
		}

		return $result;
	}

	protected function getRequestDelivery()
	{
		$order = \method_exists($this->request, 'getOrder')
			? $this->request->getOrder()
			: $this->request->getCart();

		return $order->getDelivery();
	}

	protected function getRequestAddress()
	{
		return $this->getRequestDelivery()->getAddress();
	}

	protected function getConfiguredAddressParts()
	{
		$addressFields = TradingService\MarketplaceDbs\Model\Cart\Delivery\Address::getAddressFields();
		$configuredMap = $this->getConfiguredMeaningfulProperties($addressFields);

		return array_keys($configuredMap);
	}
}