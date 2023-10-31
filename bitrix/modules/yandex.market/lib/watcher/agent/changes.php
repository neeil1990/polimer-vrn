<?php
namespace Yandex\Market\Watcher\Agent;

use Yandex\Market\Data\Run\Processor;
use Yandex\Market\Reference\Agent;
use Yandex\Market\Watcher\Track;

class Changes extends Agent\Base
{
	public static function getDefaultParams()
	{
		return [
			'interval' => 5,
			'sort' => 200, // more priority
		];
	}

	public static function schedule($service)
	{
		self::register([
			'method' => 'process',
			'arguments' => [ $service ],
		]);
	}

	public static function process($service)
	{
		$needRepeat = false;
		$ready = [];

		while ($setupId = self::nextSetupId($service, $ready))
		{
			$stampState = new Track\StampState($service, $setupId);
			$changes = Track\ChangesRepository::tasks($stampState);
			$lastChange = end($changes);

			if (empty($changes))
			{
				$ready[] = $setupId;
				$stampState->shift();
				continue;
			}

			$needRepeat = true;
			$processor = Factory::processor('change', $service, $setupId);
			$interrupted = $processor->run(Processor::ACTION_CHANGE, [
				'changes' => self::groupChangesByType($changes),
			]);

			if ($interrupted)
			{
				$stampState->interrupt($lastChange['ID']);
				break;
			}

			$ready[] = $setupId;
			$stampState->shift($lastChange['ID']);
		}

		if (!$needRepeat)
		{
			Track\ChangesRepository::clearProcessed();
		}

		return $needRepeat;
	}

	private static function nextSetupId($service, array $skipIds = [])
	{
		$result = null;
		$queryParameters = [
			'select' => [ 'SETUP_ID' ],
			'filter' => [ '=SERVICE' => $service ],
			'order' => [ 'SETUP_ID' => 'asc' ],
			'limit' => 1,
		];

		if (!empty($skipIds))
		{
			$queryParameters['filter']['!=SETUP_ID'] = $skipIds;
		}

		$query = Track\BindTable::getList($queryParameters);

		if ($row = $query->fetch())
		{
			$result = (int)$row['SETUP_ID'];
		}

		return $result;
	}

	private static function groupChangesByType(array $changes)
	{
		$result = [];

		foreach ($changes as $change)
		{
			if (!isset($result[$change['ELEMENT_TYPE']]))
			{
				$result[$change['ELEMENT_TYPE']] = [];
			}

			$result[$change['ELEMENT_TYPE']][] = $change['ELEMENT_ID'];
		}

		return $result;
	}
}