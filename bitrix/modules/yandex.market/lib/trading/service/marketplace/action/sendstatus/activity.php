<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendStatus;

use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\ComplexActivity
{
	public function getTitle()
	{
		return 'Send status system';
	}

	public function onlyContents()
	{
		return true;
	}
	
	public function useGroup()
	{
		return true;
	}

	public function getActivities()
	{
		return [
			'status' => new StatusActivity($this->provider, $this->environment),
			'cancel' => new CancelActivity($this->provider, $this->environment),
		];
	}
}