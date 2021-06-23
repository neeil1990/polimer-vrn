<?php

namespace Yandex\Market\Trading\Service\Common;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

abstract class Status extends TradingService\Reference\Status
{
	const VIRTUAL_CREATED = 'CREATED';

	abstract public function getIncomingVariants();

	abstract public function getIncomingRequired();

	abstract public function getIncomingMeaningfulMap();

	abstract public function getOutgoingVariants();

	abstract public function getOutgoingRequired();

	abstract public function getOutgoingMeaningfulMap();

	public function getOutgoingMultiple()
	{
		return [];
	}

	public function isChanged($orderId, $status, $substatus = null)
	{
		$serviceKey = $this->provider->getUniqueKey();

		return Market\Trading\State\OrderStatus::isChanged($serviceKey, $orderId, $status . ':' . $substatus);
	}
}