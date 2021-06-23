<?php

namespace Yandex\Market\Migration;

use Bitrix\Main;
use Yandex\Market;

class Event
{
	public static function canRestore($exception)
	{
		return false;
	}

	public static function check()
	{
		$result = !Version::check('event');

		if ($result)
		{
			Version::update('event');

			static::reset();
		}

		return $result;
	}

	public static function reset()
	{
		Market\Reference\Event\Controller::deleteAll();
		Market\Reference\Event\Controller::updateRegular();

		static::truncateExportTrackTable();
		static::restoreExportEvents();
		static::restoreTradingEvents();
		static::restoreApiTokenRefresh();
		static::restoreConfirmationEvents();
	}

	protected static function truncateExportTrackTable()
	{
		$connection = Main\Application::getConnection();
		$trackTableName = Market\Export\Track\Table::getTableName();

		$connection->truncateTable($trackTableName);
	}

	protected static function restoreExportEvents()
	{
		$setupList = Market\Export\Setup\Model::loadList();

		/** @var Market\Export\Setup\Model $setup */
		foreach ($setupList as $setup)
		{
			if ($setup->isAutoUpdate())
			{
				$setup->handleChanges(true);
			}

			if ($setup->getRefreshPeriod() > 0)
			{
				$setup->handleRefresh(true);
			}
		}
	}

	protected static function restoreTradingEvents()
	{
		$setupList = Market\Trading\Setup\Model::loadList([
			'filter' => [ '=ACTIVE' => Market\Trading\Setup\Table::BOOLEAN_Y, ]
		]);

		foreach ($setupList as $setup)
		{
			static::installTradingService($setup);
		}
	}

	protected static function installTradingService(Market\Trading\Setup\Model $setup)
	{
		try
		{
			$service = $setup->wakeupService();
			$environment = $setup->getEnvironment();
			$installer = $service->getInstaller();

			$installer->install($environment, $setup->getSiteId());
		}
		catch (Main\SystemException $exception)
		{
			// silence
		}
	}

	protected static function restoreApiTokenRefresh()
	{
		Market\Api\OAuth2\RefreshToken\Agent::schedule();
	}

	protected static function restoreConfirmationEvents()
	{
		$setupList = Market\Confirmation\Setup\Model::loadList();

		foreach ($setupList as $setup)
		{
			$setup->install();
		}
	}
}