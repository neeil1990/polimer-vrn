<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Concerns\Action;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasRegionHandler
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Order $order
 * @method makeRegionNotFoundLocationError(Market\Api\Model\Region $region)
 */
trait HasRegionHandler
{
	protected function handleRegionNotFoundLocation(Market\Api\Model\Region $region)
	{
		$error = $this->makeRegionNotFoundLocationError($region);

		$this->order->resetLocation();
		$this->provider->getLogger()->warning($error);
	}
}