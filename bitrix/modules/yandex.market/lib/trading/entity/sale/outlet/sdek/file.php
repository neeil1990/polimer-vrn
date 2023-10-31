<?php

namespace Yandex\Market\Trading\Entity\Sale\Outlet\Sdek;

use Bitrix\Main;
use Sale\Handlers as SaleHandlers;
use Yandex\Market;
use Yandex\Market\Api;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class File extends TradingEntity\Sale\Outlet
	implements TradingEntity\Reference\OutletInvertible
{
	use Concerns\HasMessage;

	const PREFIX = 'SDEK_';
	const MARKET_SERVICE_ID = 51;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function isMatch($deliveryId)
	{
		$deliveryService = $this->environment->getDelivery()->getDeliveryService($deliveryId);

		if (!($deliveryService instanceof SaleHandlers\Delivery\AdditionalProfile)) { return false; }

		$parentService = $deliveryService->getParentService();

		if (!method_exists($deliveryService, 'getConfigValues')) { return false; }
		if (!$parentService || !method_exists($parentService, 'getConfigValues')) { return false; }

		$selfConfig = $deliveryService->getConfigValues();
		$parentConfig = $parentService->getConfigValues();

		if (!isset($parentConfig['MAIN']['SERVICE_TYPE'], $selfConfig['MAIN']['PROFILE_TYPE'])) { return false; }

		return (
			$parentConfig['MAIN']['SERVICE_TYPE'] === 'CDEK'
			&& in_array(
				(int)$selfConfig['MAIN']['PROFILE_TYPE'],
				$this->sdekPickupProfileTypes(),
				true
			)
		);
	}

	protected function sdekPickupProfileTypes()
	{
		return [
			63,
			62,
			17,
			15,
			12,
			10,
			5,
			301,
			302,
			234,
			233,
			138,
			136,
		];
	}

	public function getOutlets(TradingEntity\Reference\Order $order, $deliveryId, Api\Model\Region $region)
	{
		$map = include __DIR__ . '/map.php';

		if (!is_array($map)) { return []; }

		$regionIds = $this->regionIds($region);
		$map = array_intersect_key($map, array_flip($regionIds));
		$partials = [];

		foreach ($map as $pointsGlued)
		{
			$partials[] = explode(',', $pointsGlued);
		}

		return !empty($partials) ? array_merge(...$partials) : [];
	}

	protected function regionIds(Api\Model\Region $region)
	{
		$level = $region;
		$result = [];

		while ($level)
		{
			$result[] = $level->getId();

			$level = $level->getParent();
		}

		return $result;
	}

	public function outletDetails($deliveryId, $code)
	{
		return $this->outletByCode($code);
	}

	protected function outletByCode($code)
	{
		$path = __DIR__ . '/details.csv';
		$handle = fopen($path, 'rb');
		$result = null;

		if ($handle === false)
		{
			throw new Main\SystemException(sprintf('cant open file %s', $path));
		}

		$header = fgetcsv($handle);

		while ($row = fgetcsv($handle))
		{
			if ($row[0] !== $code) { continue; }

			$result = $this->makeDetails($code, $header, $row);
		}

		fclose($handle);

		return $result;
	}

	protected function makeDetails($code, array $header, array $row)
	{
		$data = array_combine($header, $row);

		if (!Main\Application::isUtfMode())
		{
			$data = Main\Text\Encoding::convertEncoding($data, 'UTF-8', LANG_CHARSET);
		}

		return new Api\Model\Outlet([
			'shopOutletCode' => $code,
			'phones' => [ $data['Phone'] ],
			'address' => $data,
		]);
	}

	public function isMatchService(Market\Api\Delivery\Services\Model\DeliveryService $deliveryService)
	{
		return $deliveryService->getId() === static::MARKET_SERVICE_ID;
	}

	public function searchByCode(TradingEntity\Reference\Order $order, Market\Api\Model\Region $region, $code)
	{
		return $this->outletByCode(self::PREFIX . $code);
	}

	public function searchByAddress(
		TradingEntity\Reference\Order $order,
		Market\Api\Model\Region $region,
		TradingService\MarketplaceDbs\Model\Order\Delivery\Address $address
	)
	{
		return new Api\Model\Outlet([
			'shopOutletCode' => 'unknown',
			'address' => [
				'city' => $address->getField('city'),
				'street' => $address->getField('street'),
				'number' => $address->getField('house'),
				'block' => $address->getField('block'),
				'estate' => $address->getField('apartment'),
				'additional' => $address->getField('recipient'),
				'lat' => $address->getLat(),
				'lon' => $address->getLon(),
			],
		]);
	}
}