<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\VerifyEac;

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
			'EAC_TYPE' => TradingService\Marketplace\Model\Order\Delivery::EAC_TYPE_COURIER_TO_MERCHANT,
		];
	}

	public function getFields()
	{
		return [
			'code' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('CODE'),
				'MANDATORY' => 'Y',
			],
		];
	}

	public function getEntityValues($entity)
	{
		return [];
	}

	public function getPayload(array $values)
	{
		return $values;
	}
}