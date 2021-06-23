<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;

abstract class Installer
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	abstract public function install(TradingEntity\Reference\Environment $environment, $siteId, array $context = []);

	abstract public function uninstall(TradingEntity\Reference\Environment $environment, $siteId, array $context = []);

	public function migrate(TradingEntity\Reference\Environment $environment, $siteId, Provider $provider = null, array $context = [])
	{
		// nothing by default
	}
}