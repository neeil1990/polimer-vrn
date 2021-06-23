<?php

namespace Yandex\Market\Export\Run\Data;

use Bitrix\Main;
use Yandex\Market;

class ExportDate
{
	public static function getLastUpdate(Market\Export\Setup\Model $setup)
	{
		$status = SetupStatus::getExportState($setup);

		if ($status !== Market\Export\Run\Data\SetupStatus::EXPORT_READY) { return null; }

		/** @var Main\Entity\DataManager[] $storages */
		$result = null;
		$storages = [
			Market\Export\Run\Storage\GiftTable::class,
			Market\Export\Run\Storage\PromoTable::class,
			Market\Export\Run\Storage\CurrencyTable::class,
			Market\Export\Run\Storage\CategoryTable::class,
			Market\Export\Run\Storage\OfferTable::class,
		];

		foreach ($storages as $storage)
		{
			$query = $storage::getList([
				'select' => [ 'TIMESTAMP_X' ],
				'filter' => [ '=SETUP_ID' => $setup->getId() ],
				'order' => [ 'TIMESTAMP_X' => 'desc' ],
				'limit' => 1,
			]);
			$row = $query->fetch();

			if (empty($row['TIMESTAMP_X'])) { continue; }

			/** @var Main\Type\DateTime $timestamp */
			$timestamp = $row['TIMESTAMP_X'];

			if (
				$result === null
				|| Market\Data\DateTime::compare($result, $timestamp) === -1
			)
			{
				$result = $timestamp;
			}
		}

		return $result;
	}
}