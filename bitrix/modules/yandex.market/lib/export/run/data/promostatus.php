<?php

namespace Yandex\Market\Export\Run\Data;

use Bitrix\Main;
use Yandex\Market;

class PromoStatus
{
	const PROMO_INACTIVE = 'INACTIVE';
	const PROMO_IN_PAST = 'IN_PAST';
	const PROMO_IN_FUTURE = 'IN_FUTURE';
	const PROMO_READY = 'READY';

	const EXPORT_WAIT = 'WAIT';
	const EXPORT_FAIL = 'FAIL';
	const EXPORT_PARTIALLY = 'PARTIALLY';
	const EXPORT_READY = 'READY';

	protected static $exportStored = [];

	public static function preload($promoIds)
	{
		static::getExportStored($promoIds);
	}

	public static function getPromoState(Market\Export\Promo\Model $promo)
	{
		if (!$promo->isActive())
		{
			$result = static::PROMO_INACTIVE;
		}
		else if (!$promo->isActiveDate())
		{
			$result = $promo->getNextActiveDate() !== null
				? static::PROMO_IN_FUTURE
				: static::PROMO_IN_PAST;
		}
		else
		{
			$result = static::PROMO_READY;
		}

		return $result;
	}

	public static function getExportState(Market\Export\Promo\Model $promo)
	{
		$exportStatuses = static::getExportStoredOne($promo->getId());
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
		return in_array(Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS, $stored, true);
	}

	protected static function hasExportFail($stored)
	{
		return in_array(Market\Export\Run\Steps\Base::STORAGE_STATUS_FAIL, $stored, true);
	}

	protected static function getExportStoredOne($promoId)
	{
		$list = static::getExportStored($promoId);

		return isset($list[$promoId]) ? $list[$promoId] : [];
	}

	protected static function getExportStored($promoIds)
	{
		$promoMap = array_flip((array)$promoIds);
		$needToLoad = array_diff_key($promoMap, static::$exportStored);
		$result = array_intersect_key(static::$exportStored, $promoMap);

		if (!empty($needToLoad))
		{
			$newStatuses = static::loadExportStored(array_keys($needToLoad));

			$result += $newStatuses;
			static::$exportStored += $newStatuses;
		}

		return $result;
	}

	protected static function loadExportStored($promoIds)
	{
		$result = array_fill_keys($promoIds, []);

		$queryExportResult = Market\Export\Run\Storage\PromoTable::getList([
			'filter' => [ '=ELEMENT_ID' => $promoIds ],
			'select' => [ 'ELEMENT_ID', 'STATUS' ],
		]);

		while ($exportResult = $queryExportResult->fetch())
		{
			$promoId = (int)$exportResult['ELEMENT_ID'];
			$status = (int)$exportResult['STATUS'];

			if (
				isset($result[$promoId])
				&& !in_array($status, $result[$promoId], true)
			)
			{
				$result[$promoId][] = $status;
			}
		}

		return $result;
	}
}