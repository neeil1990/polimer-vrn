<?php

namespace Yandex\Market\Trading\Service\Common\Action\SettingsLog;

use Yandex\Market\Data;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Common\Action\HttpRequest
{
	public function getUrl()
	{
		return $this->getField('url');
	}

	public function getLevel()
	{
		return $this->getField('level');
	}

	public function getAudit()
	{
		return $this->getField('audit');
	}

	public function getPage()
	{
		return $this->getField('page');
	}

	public function getOrder()
	{
		return $this->getField('order');
	}

	public function getDateFrom()
	{
		$value = (string)$this->getField('dateFrom');

		if ($value === '') { return null; }

		return Data\DateTime::convertFromService($this->getField('dateFrom'));
	}

	public function getDateTo()
	{
		$value = (string)$this->getField('dateTo');

		if ($value === '') { return null; }

		return Data\DateTime::convertFromService($this->getField('dateTo'));
	}
}