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
		$address = $this->getRequestAddress();

		if ($address !== null)
		{
			$propertyValues = $this->getAddressProperties($address);

			$this->setMeaningfulPropertyValues($propertyValues);
		}
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

	protected function getRequestAddress()
	{
		$order = \method_exists($this->request, 'getOrder')
			? $this->request->getOrder()
			: $this->request->getCart();

		return $order->getDelivery()->getAddress();
	}

	protected function getConfiguredAddressParts()
	{
		$addressFields = TradingService\MarketplaceDbs\Model\Cart\Delivery\Address::getAddressFields();
		$configuredMap = $this->getConfiguredMeaningfulProperties($addressFields);

		return array_keys($configuredMap);
	}
}