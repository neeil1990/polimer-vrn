<?php

namespace Yandex\Market\Trading\Entity\Sale\Outlet\IpolSdek;

use Bitrix\Sale;
use Yandex\Market;
use Yandex\Market\Api;
use Yandex\Market\Data\TextString;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class Skeleton extends TradingEntity\Sale\Outlet
	implements
		TradingEntity\Reference\OutletSelectable,
		TradingEntity\Reference\OutletInvertible
{
	use Market\Reference\Concerns\HasOnceStatic;

	const PREFIX = 'SDEK_';
	const MARKET_SERVICE_ID = 51;
	const NEAREST_DISTANCE_MISTAKE = 20;
	const NEAREST_DISTANCE_LIMIT = 500;

	protected function requiredModules()
	{
		return [
			'ipol.sdek' => '3.5.0',
		];
	}

	public function getOutlets(TradingEntity\Reference\Order $order, $deliveryId, Api\Model\Region $region)
	{
		/** @var TradingEntity\Sale\Order $order */
		Assert::typeOf($order, TradingEntity\Sale\Order::class, 'order');

		$locationCode = $this->orderLocation($order);

		if ($locationCode === '') { return []; }

		$sdekCity = \sdekHelper::getCity($locationCode, true);

		if (empty($sdekCity['SDEK_ID'])) { return []; }

		$list = \CDeliverySDEK::getListFile();
		$pickupType = $this->pickupType();

		if (empty($list[$pickupType][$sdekCity['NAME']])) { return []; }

		$outlets = $this->filterOutlets($list[$pickupType]);

		if (empty($outlets[$sdekCity['NAME']]) || !is_array($outlets[$sdekCity['NAME']])) { return []; }

		$prefix = $this->getPrefix();
		$codes = array_keys($outlets[$sdekCity['NAME']]);
		$codes = array_map(static function($code) use ($prefix) { return $prefix . $code; }, $codes);

		return $codes;
	}

	abstract protected function filterOutlets(array $outlets);

	public function selectOutlet(TradingEntity\Reference\Order $order, $deliveryId, $code)
	{
		/** @var TradingEntity\Sale\Order $order */
		Assert::typeOf($order, TradingEntity\Sale\Order::class, 'order');

		$propertyCode = (string)\Ipolh\SDEK\option::get('pvzPicker');
		$code = TextString::getSubstring($code, TextString::getLength($this->getPrefix()));
		$outlet = $this->findOutlet($code);

		if ($propertyCode === '' || $outlet === null) { return; }

		/** @var Sale\PropertyValue $propertyValue */
		foreach ($order->getInternal()->getPropertyCollection() as $propertyValue)
		{
			if ($propertyValue->getField('CODE') === $propertyCode)
			{
				$propertyValue->setValue(sprintf('%s, %s #S%s', $outlet['City'], $outlet['Address'], $code));
			}
			else if ($propertyValue->getField('CODE') === 'IPOLSDEK_CNTDTARIF')
			{
				$propertyValue->setValue($this->tariffId());
			}
		}
	}

	abstract protected function tariffId();

	public function outletDetails($deliveryId, $code)
	{
		$code = TextString::getSubstring($code, TextString::getLength($this->getPrefix()));
		$point = $this->findOutlet($code);

		if ($point === null) { return null; }

		return $this->buildOutlet($code, $point);
	}

	public function isMatchService(Market\Api\Delivery\Services\Model\DeliveryService $deliveryService)
	{
		return $deliveryService->getId() === static::MARKET_SERVICE_ID;
	}

	public function searchByCode(TradingEntity\Reference\Order $order, Market\Api\Model\Region $region, $code)
	{
		$point = $this->findOutlet($code);

		if ($point === null) { return null; }

		return $this->buildOutlet($code, $point);
	}

	public function searchByAddress(
		TradingEntity\Reference\Order $order,
		Market\Api\Model\Region $region,
		TradingService\MarketplaceDbs\Model\Order\Delivery\Address $address
	)
	{
		if ($address->getLat() === null || $address->getLon() === null) { return null; }

		$nearestOutlets = static::nearestOutlets($address->getLat(), $address->getLon());

		if (empty($nearestOutlets)) { return null; }

		$pickupType = $this->pickupType();
		$nearestDistance = min(array_column($nearestOutlets, 'DISTANCE'));
		$matchedOutlets = array_filter($nearestOutlets, static function(array $item) use ($nearestDistance, $pickupType) {
			return (
				$item['TYPE'] === $pickupType
 				&& $item['DISTANCE'] < $nearestDistance + static::NEAREST_DISTANCE_MISTAKE
			);
		});

		if (empty($matchedOutlets)) { return null; }

		$matchedOutlet = reset($matchedOutlets);

		return $this->buildOutlet($matchedOutlet['CODE'], $matchedOutlet['OUTLET']);
	}

	protected static function nearestOutlets($lat, $lon)
	{
		return static::onceStatic('nearestOutlets', [$lat, $lon], static function($lat, $lon) {
			$list = \CDeliverySDEK::getListFile();

			if (!is_array($list)) { return []; }

			$hasBelowMistake = false;
			$hasAboveMistake = false;
			$aboveDistance = null;
			$result = [];

			foreach ($list as $pickupType => $outlets)
			{
				foreach ($outlets as $cityOutlets)
				{
					foreach ($cityOutlets as $sdekCode => $sdekOutlet)
					{
						if (!isset($sdekOutlet['cX'], $sdekOutlet['cY'])) { continue; }
						if (abs($lat - $sdekOutlet['cY']) > 0.01) { continue; } // simplify calculation
						if (abs($lon - $sdekOutlet['cX']) > 0.01) { continue; } // simplify calculation

						$distance = Market\Data\Coordinates::distance(
							$lat,
							$lon,
							$sdekOutlet['cY'],
							$sdekOutlet['cX']
						);

						if ($distance <= static::NEAREST_DISTANCE_MISTAKE)
						{
							if ($hasAboveMistake)
							{
								$result = [];
								$hasAboveMistake = false;
							}

							$hasBelowMistake = true;
						}
						else if ($hasBelowMistake || $distance > static::NEAREST_DISTANCE_LIMIT)
						{
							continue;
						}
						else if ($aboveDistance === null)
						{
							$hasAboveMistake = true;
							$aboveDistance = $distance;
						}
						else if ($distance < $aboveDistance + static::NEAREST_DISTANCE_MISTAKE)
						{
							$hasAboveMistake = true;

							if (abs($aboveDistance - $distance) > static::NEAREST_DISTANCE_MISTAKE)
							{
								$result = [];
								$aboveDistance = $distance;
							}
						}
						else
						{
							continue;
						}

						$result[] = [
							'DISTANCE' => $distance,
							'CODE' => $sdekCode,
							'OUTLET' => $sdekOutlet,
							'TYPE' => $pickupType,
						];
					}

					if ($hasBelowMistake) { break; }
				}

				if ($hasBelowMistake) { break; }
			}

			return $result;
		});
	}

	protected function orderLocation(TradingEntity\Sale\Order $order)
	{
		$locationProperty = $order->getInternal()->getPropertyCollection()->getDeliveryLocation();

		return $locationProperty !== null ? (string)$locationProperty->getValue() : '';
	}

	protected function findOutlet($code)
	{
		$list = \CDeliverySDEK::getListFile();
		$pickupType = $this->pickupType();

		if (empty($list[$pickupType])) { return null; }

		$result = null;

		foreach ($list[$pickupType] as $city => $cityOutlets)
		{
			if (!isset($cityOutlets[$code])) { continue; }

			$result = $cityOutlets[$code];
			$result['City'] = $city;
			break;
		}

		return $result;
	}

	protected function buildOutlet($code, array $point)
	{
		return new Api\Model\Outlet([
			'name' => $point['Name'],
			'shopOutletCode' => $this->getPrefix() . $code,
			'phones' => [ $point['Phone'] ],
			'coords' => $point['cX'] . ',' . $point['cY'],
			'address' => [
				'street' => $point['Address'],
				'additional' => $point['AddressComment'],
			],
		]);
	}

	protected function getPrefix()
	{
		return Market\Config::getOption('trading_outlet_ipol_sdek_prefix', static::PREFIX);
	}

	abstract protected function pickupType();
}