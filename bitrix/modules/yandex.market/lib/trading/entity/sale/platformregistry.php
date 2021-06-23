<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class PlatformRegistry extends Market\Trading\Entity\Reference\PlatformRegistry
{
	protected function createPlatform($serviceCode, $siteId)
	{
		return new Platform($this->environment, $serviceCode, $siteId);
	}
}