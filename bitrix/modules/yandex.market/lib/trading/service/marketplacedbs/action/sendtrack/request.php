<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendTrack;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\SendRequest
{
	public function getTrackCode()
	{
		return (string)$this->getRequiredField('trackCode');
	}

	public function getDeliveryId()
	{
		return (string)$this->getRequiredField('deliveryId');
	}
}