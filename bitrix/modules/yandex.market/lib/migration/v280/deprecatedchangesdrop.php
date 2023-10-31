<?php
namespace Yandex\Market\Migration\V280;

use Bitrix\Main;
use Yandex\Market\Export\Run\Storage;
use Yandex\Market\Reference\Agent;

/** @noinspection PhpUnused */
class DeprecatedChangesDrop extends Agent\Base
{
	public static function schedule()
	{
		$date = new Main\Type\DateTime();
		$date->add('P1D');

		static::register([
			'next_exec' => $date->toString(),
		]);
	}

	/** @noinspection PhpDeprecationInspection */
	public static function run()
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$name = Storage\ChangesTable::getTableName();

		if ($connection->isTableExists($name))
		{
			$connection->query(sprintf(
				'DROP TABLE %s',
				$sqlHelper->quote($name)
			));
		}

		return false;
	}
}