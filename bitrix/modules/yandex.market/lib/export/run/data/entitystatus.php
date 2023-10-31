<?php

namespace Yandex\Market\Export\Run\Data;

use Bitrix\Main;
use Yandex\Market\Export;

class EntityStatus
{
	const STATE_INACTIVE = 'INACTIVE';
	const STATE_IN_PAST = 'IN_PAST';
	const STATE_IN_FUTURE = 'IN_FUTURE';
	const STATE_READY = 'READY';

	const EXPORT_WAIT = 'WAIT';
	const EXPORT_FAIL = 'FAIL';
	const EXPORT_PARTIALLY = 'PARTIALLY';
	const EXPORT_READY = 'READY';

	protected static $exportStored = [];

	public static function preload($type, array $ids)
	{
		static::exportStored($type, $ids);
	}

	public static function modelState(EntityExportable $entity)
	{
		if (!$entity->isActive())
		{
			$result = static::STATE_INACTIVE;
		}
		else if (!$entity->isActiveDate())
		{
			$result = $entity->getNextActiveDate() !== null
				? static::STATE_IN_FUTURE
				: static::STATE_IN_PAST;
		}
		else
		{
			$result = static::STATE_READY;
		}

		return $result;
	}

	public static function exportState($type, $id)
	{
		$exportStatuses = static::exportStoredOne($type, $id);
		$hasExportSuccess = static::hasExportSuccess($exportStatuses);
		$hasExportFail = static::hasExportFail($exportStatuses);

		if ($hasExportSuccess && $hasExportFail)
		{
			$result = static::EXPORT_PARTIALLY;
		}
		else if ($hasExportSuccess)
		{
			$result = static::EXPORT_READY;
		}
		else if ($hasExportFail)
		{
			$result = static::EXPORT_FAIL;
		}
		else
		{
			$result = static::EXPORT_WAIT;
		}

		return $result;
	}

	protected static function hasExportSuccess($stored)
	{
		return in_array(Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS, $stored, true);
	}

	protected static function hasExportFail($stored)
	{
		return (
			in_array(Export\Run\Steps\Base::STORAGE_STATUS_FAIL, $stored, true)
			|| in_array(Export\Run\Steps\Base::STORAGE_STATUS_DUPLICATE, $stored, true)
		);
	}

	protected static function exportStoredOne($type, $id)
	{
		$list = static::exportStored($type, [ $id ]);

		return isset($list[$id]) ? $list[$id] : [];
	}

	protected static function exportStored($type, array $ids)
	{
		if (!isset(static::$exportStored[$type]))
		{
			static::$exportStored[$type] = [];
		}

		$promoMap = array_flip($ids);
		$needToLoad = array_diff_key($promoMap, static::$exportStored[$type]);
		$result = array_intersect_key(static::$exportStored[$type], $promoMap);

		if (!empty($needToLoad))
		{
			$newStatuses = static::fetchStored($type, array_keys($needToLoad));

			$result += $newStatuses;
			static::$exportStored[$type] += $newStatuses;
		}

		return $result;
	}

	protected static function fetchStored($type, array $ids)
	{
		$result = array_fill_keys($ids, []);
		/** @var Export\Run\Storage\CollectionTable|Export\Run\Storage\PromoTable $dataClass */
		list($dataClass, $field) = static::runStorageFormat($type);

		$queryExportResult = $dataClass::getList([
			'filter' => [ '=' . $field => $ids ],
			'select' => [ $field, 'STATUS' ],
			'group' => [ $field, 'STATUS' ],
		]);

		while ($exportResult = $queryExportResult->fetch())
		{
			$id = (int)$exportResult[$field];
			$status = (int)$exportResult['STATUS'];

			$result[$id][] = $status;
		}

		return $result;
	}

	protected static function runStorageFormat($type)
	{
		if ($type === Export\Run\Manager::ENTITY_TYPE_COLLECTION)
		{
			$result = [ Export\Run\Storage\CollectionTable::class, 'COLLECTION_ID' ];
		}
		else if ($type === Export\Run\Manager::ENTITY_TYPE_PROMO)
		{
			$result = [ Export\Run\Storage\PromoTable::class, 'ELEMENT_ID' ];
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown entity type %s', $type));
		}

		return $result;
	}
}