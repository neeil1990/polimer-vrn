<?php

namespace Yandex\Market\Trading\Service\Beru;

use Yandex\Market;

/** @deprecated */
class Provider extends Market\Trading\Service\Marketplace\Provider
{
	public function getServiceCode()
	{
		return Market\Trading\Service\Manager::SERVICE_BERU;
	}

	protected function createInfo()
	{
		return new Info($this);
	}

	protected function createOptions()
	{
		return new Options($this);
	}
}
