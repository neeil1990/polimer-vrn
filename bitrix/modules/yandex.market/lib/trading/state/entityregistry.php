<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;

class EntityRegistry
{
	public static function get($setupId, $entityType, $entityId)
	{
		$result = null;

		$query = Internals\EntityTable::getList([
			'filter' => [
				'=SETUP_ID' => $setupId,
				'=ENTITY_TYPE' => $entityType,
				'=ENTITY_ID' => $entityId,
			],
			'select' => [ 'DATA' ],
		]);

		if ($row = $query->fetch())
		{
			$result = (array)$row['DATA'];
		}

		return $result;
	}

	public static function touch($setupId, $entityType, $entityId)
	{
		Internals\EntityTable::update([
			'SETUP_ID' => $setupId,
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
		], [
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		]);
	}

	public static function store($setupId, $entityType, $entityId, array $data)
	{
		$primary = [
			'SETUP_ID' => $setupId,
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
		];
		$fields = [
			'DATA' => $data,
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		];

		$exists = Internals\EntityTable::getByPrimary($primary);

		if ($exists->fetch())
		{
			Internals\EntityTable::update($primary, $fields);
		}
		else
		{
			Internals\EntityTable::add($primary + $fields);
		}
	}
}