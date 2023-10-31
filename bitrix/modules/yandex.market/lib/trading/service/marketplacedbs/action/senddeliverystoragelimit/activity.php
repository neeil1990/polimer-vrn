<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendDeliveryStorageLimit;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\FormActivity
{
	use Market\Reference\Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFilter()
	{
		return [
			'STATUS' => TradingService\MarketplaceDbs\Status::STATUS_PICKUP,
		];
	}

	public function getFields()
	{
		return [
			'newDate' => [
				'TYPE' => 'date',
				'NAME' => self::getMessage('NEW_DATE'),
				'MANDATORY' => 'Y',
			],
		];
	}

	public function getEntityValues($entity)
	{
		/** @var TradingService\MarketplaceDbs\Model\Order $entity */
		Market\Reference\Assert::typeOf($entity, TradingService\MarketplaceDbs\Model\Order::class, 'entity');

		if (!$entity->hasDelivery()) { return []; }

		$delivery = $entity->getDelivery();

		return [
			'newDate' => $delivery->getOutletStorageLimitDate(),
		];
	}

	public function getPayload(array $values)
	{
		return $values;
	}
}