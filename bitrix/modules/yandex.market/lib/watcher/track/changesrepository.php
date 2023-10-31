<?php
namespace Yandex\Market\Watcher\Track;

use Yandex\Market\Config;
use Yandex\Market\Data;
use Yandex\Market\Data\Type;
use Yandex\Market\Utils\ArrayHelper;

class ChangesRepository
{
	public static function unprocessedCount(StampState $state, Type\CanonicalDateTime $before = null)
	{
		if (
			$before !== null && $state->timestampX() !== null
			&& Data\DateTime::compare($before, $state->timestampX()) === -1
		)
		{
			return 0;
		}

		$types = self::bindTypes($state);

		return static::waitingChangesCount($types, $state);
	}

	public static function tasks(StampState $state)
	{
		$types = self::bindTypes($state);

		return self::taskChanges($types, $state);
	}

	private static function bindTypes(StampState $state)
	{
		return BindTable::getList([
			'select' => [ 'ELEMENT_TYPE', 'ELEMENT_GROUP', 'REPLACE_TYPE' ],
			'filter' => [ '=SERVICE' => $state->service(), '=SETUP_ID' => $state->setupId() ],
		])->fetchAll();
	}

	private static function waitingChangesCount(array $types, StampState $state)
	{
		if (empty($types)) { return 0; }

		$groupedTypes = ArrayHelper::groupBy($types, 'ELEMENT_TYPE');

		return ChangesTable::getCount([
			array_filter([ '>ID' => $state->offset() ]),
			static::changesTypesFilter($groupedTypes)
		]);
	}

	private static function taskChanges(array $types, StampState $state)
	{
		if (empty($types)) { return []; }

		$groupedTypes = ArrayHelper::groupBy($types, 'ELEMENT_TYPE');
		$result = [];

		$query = ChangesTable::getList([
			'filter' => [
				array_filter([
					'>ID' => $state->offset(),
					'<=ID' => $state->until(),
				]),
				static::changesTypesFilter($groupedTypes)
			],
			'select' => [ 'ID', 'ELEMENT_TYPE', 'ELEMENT_ID', 'ELEMENT_GROUP' ],
			'order' => [ 'ID' => 'asc' ],
			'limit' => max(1, (int)Config::getOption('export_run_agent_changes_limit', 1000)),
		]);

		while ($row = $query->fetch())
		{
			if (!isset($groupedTypes[$row['ELEMENT_TYPE']])) { continue; }

			foreach ($groupedTypes[$row['ELEMENT_TYPE']] as $type)
			{
				if ((string)$type['ELEMENT_GROUP'] !== (string)$row['ELEMENT_GROUP']) { continue; }

				$result[] = [
					'ID' => $row['ID'],
					'ELEMENT_TYPE' => $type['REPLACE_TYPE'] ?: $type['ELEMENT_TYPE'],
					'ELEMENT_ID' => $row['ELEMENT_ID'],
				];
			}
		}

		return $result;
	}

	private static function changesTypesFilter(array $groupedTypes)
	{
		$typesFilter = [];

		if (count($groupedTypes) > 1) { $typesFilter['LOGIC'] = 'OR'; }

		foreach ($groupedTypes as $type => $groupTypes)
		{
			$typesFilter[] = [
				'=ELEMENT_TYPE' => $type,
				'=ELEMENT_GROUP' => array_values(array_column($groupTypes, 'ELEMENT_GROUP', 'ELEMENT_GROUP')),
			];
		}

		return $typesFilter;
	}

	public static function clearProcessed()
	{
		$offset = self::processedOffset();

		if ($offset <= 0) { return; }

		self::removeProcessed($offset);

		if (!self::leftUnprocessed())
		{
			self::truncateChanges();
			self::releaseOutOfDate();
			self::resetStamp();
		}
	}

	private static function processedOffset()
	{
		$result = 0;

		$query = StampTable::getList([
			'filter' => [ '>=TIMESTAMP_X' => self::expireDate() ],
			'select' => [ 'OFFSET', 'UNTIL' ],
			'order' => [ 'UNTIL' => 'desc', 'OFFSET' => 'asc' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			if ($row['UNTIL'] > 0) { return 0; } // interrupted, need wait finish

			$result = (int)$row['OFFSET'];
		}

		return $result;
	}

	private static function expireDate()
	{
		$result = new Type\CanonicalDateTime();
		$result->add('-PT6H'); // only six hours to process changes

		return $result;
	}

	private static function removeProcessed($offset)
	{
		ChangesTable::deleteBatch([
			'filter' => [ '<=ID' => $offset ],
		]);
	}

	private static function leftUnprocessed()
	{
		return (bool)ChangesTable::getRow([ 'select' => [ 'ID' ] ]);
	}

	private static function truncateChanges()
	{
		$entity = ChangesTable::getEntity();
		$entity->getConnection()->truncateTable($entity->getDBTableName());
	}

	private static function releaseOutOfDate()
	{
		StampTable::deleteBatch([
			'filter' => [ '<TIMESTAMP_X' => self::expireDate() ],
		]);
	}

	private static function resetStamp()
	{
		StampTable::updateBatch([], [
			'OFFSET' => 0,
			'UNTIL' => 0,
		]);
	}
}