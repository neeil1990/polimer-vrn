<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\AdminView;

use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\ComplexActivity
{
	use Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function onlyContents()
	{
		return true;
	}

	public function getFilter()
	{
		return [
			'PROCESSING' => true,
		];
	}

	public function getActivities()
	{
		return [
			'buyer' => new BuyerActivity($this->provider, $this->environment),
		];
	}
}