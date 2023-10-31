<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class CancellationAcceptCreator extends Reference\PropertyCreator
{
	use Market\Reference\Concerns\HasMessage;

	protected function searchUsedProperties()
	{
		$result = [];

		foreach (Market\Trading\Setup\Model::loadList() as $setup)
		{
			$service = $setup->getService();
			$options = $service->getOptions();

			if (!($service instanceof TradingService\Reference\HasCancellationAccept)) { continue; }
			if (!method_exists($options, 'getProperty')) { continue; }

			$setup->wakeupService();

			$propertyId = (int)$options->getProperty('CANCELLATION_ACCEPT');

			if ($propertyId <= 0) { continue; }

			$result[$propertyId] = $this->makePropertyFields($service);
		}

		return array_unique($result);
	}

	protected function makePropertyFields(TradingService\Reference\Provider $service)
	{
		$cancellationAccept = $this->getCancellationAccept($service);

		return [
			'TYPE' => 'ENUM',
			'NAME' => static::getMessage('NAME', null, 'CANCELLATION_ACCEPT'),
			'CODE' => 'MARKET_CANCELLATION_ACCEPT',
			'VARIANTS' => $this->makePropertyEnum($cancellationAccept),
		];
	}

	protected function makePropertyEnum(TradingService\Reference\CancellationAccept $cancellationAccept)
	{
		return array_merge(
			$this->makePropertyWaitEnum(),
			$this->makePropertyConfirmEnum(),
			$this->makePropertyRejectEnum($cancellationAccept)
		);
	}

	protected function makePropertyWaitEnum()
	{
		return [
			[
				'ID' => Market\Data\Trading\CancellationAccept::WAIT,
				'VALUE' => static::getMessage('VARIANT_WAIT'),
			],
		];
	}

	protected function makePropertyConfirmEnum()
	{
		return [
			[
				'ID' => Market\Data\Trading\CancellationAccept::CONFIRM,
				'VALUE' => static::getMessage('VARIANT_CONFIRM'),
			],
		];
	}

	protected function makePropertyRejectEnum(TradingService\Reference\CancellationAccept $cancellationAccept)
	{
		$result = [];

		foreach ($cancellationAccept->getReasonVariants() as $variant)
		{
			$result[] = [
				'ID' => Market\Data\Trading\CancellationAccept::REJECT . ':' . $variant,
				'VALUE' => $cancellationAccept->getReasonTitle($variant),
			];
		}

		return $result;
	}

	protected function getCancellationAccept(TradingService\Reference\Provider $service)
	{
		if (!($service instanceof TradingService\Reference\HasCancellationAccept))
		{
			throw new Main\NotSupportedException('service hasn\'t cancellation accept entity');
		}

		return $service->getCancellationAccept();
	}
}