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
		static::restoreSalesBoostEvents();
		static::restoreConfirmationEvents();
	}

	protected static function truncateExportTrackTable()
	{
		$connection = Main\Application::getConnection();
		$tables = [
			Market\Watcher\Track\BindTable::getTableName(),
			Market\Watcher\Track\SourceTable::getTableName(),
			Market\Watcher\Track\StampTable::getTableName(),
			Market\Watcher\Track\ChangesTable::getTableName(),
		];

		foreach ($tables as $table)
		{
			$connection->truncateTable($table);
		}
	}

	protected static function restoreExportEvents()
	{
		$setupList = Market\Export\Setup\Model::loadList();

		foreach ($setupList as $setup)
		{
			$setup->updateListener();
		}
	}

	protected static function restoreTradingEvents()
	{
		$setupList = Market\Trading\Setup\Model::loadList([
			'filter' => [ '=ACTIVE' => Market\Trading\Setup\Table::BOOLEAN_Y ],
		]);

		foreach ($setupList as $setup)
		{
			static::installTradingService($setup);
		}
	}

	protected static function restoreSalesBoostEvents()
	{
		$boosts = Market\SalesBoost\Setup\Model::loadList([
			'filter' => [ '=ACTIVE' => Market\Trading\Setup\Table::BOOLEAN_Y ],
		]);

		foreach ($boosts as $boost)
		{
			$boost->updateListener();
		}
	}

	protected static function installTradingService(Market\Trading\Setup\Model $setup)
	{
		try
		{
			$service = $setup->wakeupService();
			$environment = $setup->getEnvironment();
			$installer = $service->getInstaller();
			$context = [
				'SETUP_ID' => $setup->getId(),
			];

			$installer->install($environment, $setup->getSiteId());
			$installer->postInstall($environment, $setup->getSiteId(), $context);
			$installer->tweak($environment, $setup->getSiteId(), $context);
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