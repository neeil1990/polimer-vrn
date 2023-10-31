<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendDeliveryDate;

use Yandex\Market;
use Yandex\Market\Api;
use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\FormActivity
{
	use Market\Reference\Concerns\HasMessage;

	const REASON_USER_MOVED_DELIVERY_DATES = 'USER_MOVED_DELIVERY_DATES';
	const REASON_PARTNER_MOVED_DELIVERY_DATES  = 'PARTNER_MOVED_DELIVERY_DATES';

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFilter()
	{
		return [
			'PROCESSING' => true,
		];
	}

	public function getFields()
	{
		return [
			'reason' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getMessage('REASON'),
				'VALUES' => $this->getReasonEnum(),
				'MANDATORY' => 'Y',
			],
			'date' => [
				'TYPE' => 'date',
				'NAME' => self::getMessage('DATE'),
				'MANDATORY' => 'Y',
			],
		];
	}

	protected function getReasonEnum()
	{
		$result = [];
		$variants = [
			static::REASON_USER_MOVED_DELIVERY_DATES,
			static::REASON_PARTNER_MOVED_DELIVERY_DATES,
		];

		foreach ($variants as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => self::getMessage('REASON_' . $variant)
			];
		}

		return $result;
	}

	public function getEntityValues($entity)
	{
		/** @var Api\Model\Order $entity */
		Market\Reference\Assert::typeOf($entity, Api\Model\Order::class, 'entity');

		if (!$entity->hasDelivery()) { return []; }

		$dates = $entity->getDelivery()->getDates();

		if ($dates === null) { return []; }

		return [
			'date' => $dates->getFrom() ?: $dates->getTo(),
		];
	}

	public function getPayload(array $values)
	{
		return $values;
	}
}