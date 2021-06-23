<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Installer extends TradingService\Common\Installer
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function install(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		parent::install($environment, $siteId, $context);
		$this->installListener($environment);
		$this->installAdminExtension($environment);
	}

	public function uninstall(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		parent::uninstall($environment, $siteId, $context);
		$this->uninstallListener($environment, $context);
		$this->uninstallAdminExtension($environment, $context);
	}

	protected function installListener(TradingEntity\Reference\Environment $environment)
	{
		$environment->getListener()->bind();
	}

	protected function uninstallListener(TradingEntity\Reference\Environment $environment, array $context)
	{
		if (!$context['SERVICE_USED'])
		{
			$environment->getListener()->unbind();
		}
	}

	protected function installAdminExtension(TradingEntity\Reference\Environment $environment)
	{
		$environment->getAdminExtension()->install();
	}

	protected function uninstallAdminExtension(TradingEntity\Reference\Environment $environment, array $context)
	{
		if (!$context['SERVICE_USED'])
		{
			$environment->getAdminExtension()->uninstall();
		}
	}
}