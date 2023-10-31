<?php

namespace Yandex\Market\Trading\Service\Reference;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;

abstract class Options extends Options\Skeleton
{
	abstract public function getTitle($version = '');

	abstract public function getTabs();

	public function getSetupId()
	{
		return $this->getRequiredValue('SETUP_ID');
	}

	public function getSiteId()
	{
		return $this->getRequiredValue('SITE_ID');
	}

	public function getPlatformId()
	{
		return $this->getRequiredValue('PLATFORM_ID');
	}

	public function getUrlId($default = null)
	{
		return $this->getRequiredValue('URL_ID', $default);
	}

	public function getEnvironmentFieldActions(TradingEntity\Reference\Environment $environment)
	{
		return [];
	}
}