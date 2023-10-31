<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class CancelReasonCreator extends Reference\PropertyCreator
{
	use Market\Reference\Concerns\HasMessage;

	protected function searchUsedProperties()
	{
		$result = [];

		foreach (Market\Trading\Setup\Model::loadList() as $setup)
		{
			$service = $setup->getService();
			$options = $service->getOptions();

			if (!($service instanceof TradingService\Reference\HasCancelReason)) { continue; }
			if (!method_exists($options, 'getProperty')) { continue; }

			$setup->wakeupService();

			$propertyId = (int)$options->getProperty('REASON_CANCELED');

			if ($propertyId <= 0) { continue; }

			$result[$propertyId] = $this->makePropertyFields($service);
		}

		return array_unique($result);
	}

	protected function makePropertyFields(TradingService\Reference\Provider $service)
	{
		$cancelReason = $this->getCancelReason($service);

		return [
			'TYPE' => 'ENUM',
			'NAME' => static::getMessage('NAME', null, 'CANCEL_REASON'),
			'CODE' => 'MARKET_CANCEL_REASON',
			'VARIANTS' => $this->makePropertyEnum($cancelReason),
		];
	}

	protected function makePropertyEnum(TradingService\Reference\CancelReason $cancelReason)
	{
		$result = [];

		foreach ($cancelReason->getVariants() as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => $cancelReason->getTitle($variant),
			];
		}

		return $result;
	}

	protected function getCancelReason(TradingService\Reference\Provider $service)
	{
		if (!($service instanceof TradingService\Reference\HasCancelReason))
		{
			throw new Main\NotSupportedException('service hasn\'t cancel reason entity');
		}

		return $service->getCancelReason();
	}
}