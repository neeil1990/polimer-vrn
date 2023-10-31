<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class LiftTypeCreator extends Reference\PropertyCreator
{
	use Market\Reference\Concerns\HasMessage;

	protected function searchUsedProperties()
	{
		$result = [];

		foreach (Market\Trading\Setup\Model::loadList() as $setup)
		{
			try
			{
				$service = $setup->getService();

				$this->getDeliveryService($service);
				$setup->wakeupService();

				$propertyId = (int)$service->getOptions()->getProperty('LIFT_TYPE');

				if ($propertyId <= 0) { continue; }

				$result[$propertyId] = $this->makePropertyFields($service);
			}
			catch (Main\SystemException $exception)
			{
				continue;
			}
		}

		return array_unique($result);
	}

	protected function makePropertyFields(TradingService\Reference\Provider $service)
	{
		$delivery = $this->getDeliveryService($service);

		return [
			'TYPE' => 'ENUM',
			'NAME' => self::getMessage('NAME', null, 'LIFT_TYPE'),
			'CODE' => 'MARKET_LIFT_TYPE',
			'VARIANTS' => $delivery->getLiftEnum(),
		];
	}

	/**
	 * @param TradingService\Reference\Provider $service
	 *
	 * @return TradingService\MarketplaceDbs\Delivery
	 */
	protected function getDeliveryService(TradingService\Reference\Provider $service)
	{
		Market\Reference\Assert::methodExists($service, 'getDelivery');

		$delivery = $service->getDelivery();

		Market\Reference\Assert::typeOf($delivery, TradingService\MarketplaceDbs\Delivery::class, 'service.delivery');

		return $delivery;
	}
}