<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market\Data;
use Yandex\Market\Glossary;
use Yandex\Market\Config;
use Yandex\Market\Utils\ArrayHelper;
use Yandex\Market\Watcher;
use Yandex\Market\Export;
use Yandex\Market\SalesBoost;

class ElementChange
{
	private static $registered = [];
	private static $flushQueue = [];
	private static $booted = false;

	public static function has($type, $id)
	{
		$sign = $type . ':' . $id;

		return isset(self::$registered[$sign]);
	}

	public static function add($type, $id, $group = null)
	{
		$sign = $type . ':' . $id;

		if (isset(self::$registered[$sign])) { return; }

		self::$registered[$sign] = true;

		if ($group === null) { $group = self::resolveGroup($type, $id); }

		self::queue($type, $id, $group);
		self::boot();
	}

	private static function queue($type, $id, $group)
	{
		$limit = Config::getOption('export_run_agent_changes_limit', 1000);

		self::$flushQueue[] = [ $type, $id, $group ];

		if (count(self::$flushQueue) >= $limit)
		{
			self::flush();
		}
	}

	private static function boot()
	{
		if (self::$booted) { return; }

		self::$booted = true;

		$eventManager = Main\EventManager::getInstance();

		$eventManager->addEventHandler('main', 'OnAfterEpilog', [__CLASS__, 'flush']);
		register_shutdown_function([__CLASS__, 'flush']);
	}

	public static function flush()
	{
		$chunkSize = self::flushChunkSize();

		foreach (array_chunk(self::$flushQueue, $chunkSize) as $chunk)
		{
			$rows = self::makeRows($chunk);
			$rows = self::filterUnprocessed($rows);

			if (empty($rows)) { continue; }

			ChangesTable::addBatch($rows);
		}

		self::$flushQueue = [];
		self::registerAgent();
	}

	private static function flushChunkSize()
	{
		return max(1, (int)Config::getOption('changes_flush_chunk_size', 100));
	}

	private static function makeRows(array $changes)
	{
		$result = [];

		foreach ($changes as list($type, $id, $group))
		{
			$result[] = [
				'ELEMENT_TYPE' => $type,
				'ELEMENT_ID' => $id,
				'ELEMENT_GROUP' => $group,
			];
		}

		return $result;
	}

	private static function filterUnprocessed(array $rows)
	{
		$offset = self::processedOffset();
		$exists = self::fetchExists($rows, $offset);

		foreach ($rows as $key => $row)
		{
			if (isset($exists[$row['ELEMENT_TYPE']][$row['ELEMENT_ID']]))
			{
				unset($rows[$key]);
			}
		}

		return $rows;
	}

	private static function processedOffset()
	{
		$row = StampTable::getRow([
			'select' => [ 'OFFSET' ],
			'order' => [ 'OFFSET' => 'desc' ],
		]);

		if ($row === null) { return 0; }

		return (int)$row['OFFSET'];
	}

	private static function fetchExists(array $rows, $offset)
	{
		if (empty($rows)) { return []; }

		$result = [];

		$query = ChangesTable::getList([
			'filter' => self::existsFilter($rows, $offset),
			'select' => [ 'ELEMENT_TYPE', 'ELEMENT_ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['ELEMENT_TYPE']][$row['ELEMENT_ID']] = true;
		}

		return $result;
	}

	private static function existsFilter(array $rows, $offset)
	{
		$grouped = ArrayHelper::groupBy($rows, 'ELEMENT_TYPE');
		$elementFilter = [];

		if (count($grouped) > 1) { $elementFilter['LOGIC'] = 'OR'; }

		foreach ($grouped as $elementType => $groupRows)
		{
			$elementFilter[] = [
				'=ELEMENT_TYPE' => $elementType,
				'=ELEMENT_ID' => array_column($groupRows, 'ELEMENT_ID'),
			];
		}

		return [
			'>ID' => $offset,
			$elementFilter,
		];
	}

	private static function registerAgent()
	{
		Export\Run\Agent::register([ 'method' => 'change' ]);
		SalesBoost\Run\Agent::register([ 'method' => 'change' ]);
	}

	private static function resolveGroup($type, $id)
	{
		if ($type !== Glossary::ENTITY_OFFER) { return 0; }

		return (int)Data\Iblock\Element::iblockId($id);
	}
}