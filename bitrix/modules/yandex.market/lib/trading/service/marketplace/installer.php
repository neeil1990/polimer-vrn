<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property Provider $provider
*/
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
		$this->installShipmentMenu();
	}

	public function postInstall(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		$this->installSyncAgent($context);
	}

	public function tweak(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		static::clearCache();
		$this->applyPushAgents($this->getPushAgents(), $context);
		$this->linkBusiness($context);
	}

	public function uninstall(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		$exportStatuses = $this->getPushAgents(true);
		$exportStatuses = array_fill_keys(array_keys($exportStatuses), false);

		parent::uninstall($environment, $siteId, $context);
		$this->unlinkBusiness($context);
		$this->uninstallListener($environment, $context);
		$this->uninstallAdminExtension($environment, $context);
		$this->uninstallShipmentMenu($context);
		$this->uninstallSyncAgent($context);
		$this->applyPushAgents($exportStatuses, $context);
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

	protected function installShipmentMenu()
	{
		if (!$this->isShipmentMenuSupported()) { return; }

		Market\Config::setOption('menu_logistic', 'Y');
	}

	protected function uninstallShipmentMenu(array $context)
	{
		if (!empty($context['BEHAVIOR_USED']) || !$this->isShipmentMenuSupported()) { return; }

		Market\Config::removeOption('menu_logistic');
	}

	protected function isShipmentMenuSupported()
	{
		return $this->provider->getRouter()->hasDataAction('admin/shipments');
	}

	protected function installSyncAgent(array $context)
	{
		Market\Reference\Assert::notNull($context['SETUP_ID'], 'context["SETUP_ID"]');

		$setupId = $context['SETUP_ID'];
		$nextExec = $this->getSyncAgentNextExec();

		Market\Trading\State\OrderStatusSync::register([
			'method' => 'start',
			'arguments' => [ $setupId ],
			'next_exec' => ConvertTimeStamp($nextExec->getTimestamp(), 'FULL'),
		]);
	}

	protected function getSyncAgentNextExec()
	{
		$result = new Main\Type\DateTime();
		$result->setTime(mt_rand(0, 10), mt_rand(0, 59));

		if ($result->getTimestamp() <= time())
		{
			$result->add('P1D');
		}

		return $result;
	}

	protected function uninstallSyncAgent(array $context)
	{
		Market\Reference\Assert::notNull($context['SETUP_ID'], 'context["SETUP_ID"]');

		$setupId = $context['SETUP_ID'];

		Market\Trading\State\OrderStatusSync::unregister([
			'method' => 'start',
			'arguments' => [ (int)$setupId ], // fix
		]);
		Market\Trading\State\OrderStatusSync::unregister([
			'method' => 'start',
			'arguments' => [ $setupId ],
		]);
		Market\Trading\State\OrderStatusSync::unregister([
			'method' => 'sync',
			'arguments' => [ $setupId ],
			'search' => Market\Reference\Agent\Controller::SEARCH_RULE_SOFT,
		]);
	}

	protected function getPushAgents($onlyList = false)
	{
		$options = $this->provider->getOptions();

		return [
			'push/stocks' => !$onlyList && $options->usePushStocks() && !$this->groupPushStocksUsed($options),
			'push/prices' => !$onlyList && $options->usePushPrices(),
		];
	}

	protected function groupPushStocksUsed(Options $options)
	{
		$result = false;

		foreach ($options->getStoreGroup() as $setupId)
		{
			$isRegistered = Market\Trading\State\PushAgent::isRegistered([
				'method' => 'change',
				'arguments' => [ (string)$setupId, 'push/stocks' ],
			]);

			if ($isRegistered)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function hasPushRefreshAgent($path)
	{
		return true;
	}

	protected function applyPushAgents(array $statuses, array $context)
	{
		Market\Reference\Assert::notNull($context['SETUP_ID'], 'context["SETUP_ID"]');

		$setupId = (string)$context['SETUP_ID'];

		foreach ($statuses as $path => $status)
		{
			if ($status)
			{
				if ($this->hasPushRefreshAgent($path))
				{
					$refreshDelay = Market\Trading\State\PushAgent::getRefreshPeriod();
					$refreshNext = $this->getPushAgentNextExec($refreshDelay);

					Market\Trading\State\PushAgent::register([
						'method' => 'refresh',
						'arguments' => [ $setupId, $path ],
						'next_exec' => $refreshNext,
						'interval' => $refreshDelay,
					]);
				}

				$changeDelay = Market\Trading\State\PushAgent::getChangePeriod();
				$changeNext = $this->getPushAgentNextExec($changeDelay);

				Market\Trading\State\PushAgent::register([
					'method' => 'change',
					'arguments' => [ $setupId, $path ],
					'next_exec' => $changeNext,
					'interval' => $changeDelay,
				]);
			}
			else
			{
				Market\Trading\State\PushAgent::unregister([
					'method' => 'refresh',
					'arguments' => [ $setupId, $path ],
				]);

				Market\Trading\State\PushAgent::unregister([
					'method' => 'change',
					'arguments' => [ $setupId, $path ],
				]);

				Market\Trading\State\PushAgent::unregister([
					'method' => 'process',
					'arguments' => [ $setupId, $path ],
					'search' => Market\Reference\Agent\Controller::SEARCH_RULE_SOFT,
				]);
			}
		}
	}

	protected function getPushAgentNextExec($delay = 60)
	{
		$result = new Main\Type\DateTime();
		$result->add(sprintf('PT%sS', $delay));

		return $result;
	}

	/** @deprecated */
	protected function getExportAgentNextExec()
	{
		$defaults = Market\Trading\State\PushAgent::getDefaultParams();
		$interval = isset($defaults['interval']) ? (int)$defaults['interval'] : 60;

		return $this->getPushAgentNextExec($interval);
	}

	protected function linkBusiness(array $context)
	{
		Market\Reference\Assert::notNull($context['SETUP_ID'], 'context["SETUP_ID"]');

		$command = $this->provider->getContainer()->get(TradingService\Marketplace\Command\LinkBusiness::class, [
			'setupId' => $context['SETUP_ID'],
			'businessId' => $this->provider->getOptions()->getValue('BUSINESS_ID'),
		]);

		$command->install();
	}

	protected function unlinkBusiness(array $context)
	{
		Market\Reference\Assert::notNull($context['SETUP_ID'], 'context["SETUP_ID"]');

		$command = $this->provider->getContainer()->get(TradingService\Marketplace\Command\LinkBusiness::class, [
			'setupId' => $context['SETUP_ID'],
			'businessId' => $this->provider->getOptions()->getValue('BUSINESS_ID'),
		]);

		$command->uninstall();
	}
}