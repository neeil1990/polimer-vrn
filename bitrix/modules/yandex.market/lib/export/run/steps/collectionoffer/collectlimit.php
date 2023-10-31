<?php
namespace Yandex\Market\Export\Run\Steps\CollectionOffer;

use Yandex\Market\Data;
use Yandex\Market\Export;
use Yandex\Market\Export\Run\Steps;
use Yandex\Market\Utils;

class CollectLimit
{
	protected $setup;
	protected $exportedCount;

	public function __construct(Export\Setup\Model $setup)
	{
		$this->setup = $setup;
	}

	public function canUse(Export\Collection\Model $collection)
	{
		$limit = $collection->getLimit();

		return ($limit->enabled() && $limit->sortField() !== null && $limit->count() !== null);
	}
	
	public function apply(array $rows, CollectState $state)
	{
		$this->resetCache();

		$rows = $this->sortRows($rows);

		$maxCount = (int)$state->collection->getLimit()->count();
		$rows = $this->localLimitsConflict($rows, $maxCount);
		$rows = $this->storedLimitsConflict($rows, $maxCount, $state);
		
		return $rows;
	}

	protected function resetCache()
	{
		$this->exportedCount = null;
	}

	protected function localLimitsConflict(array $rows, $maxCount)
	{
		return $this->markOverLimit($rows, $maxCount);
	}

	protected function storedLimitsConflict(array $rows, $maxCount, CollectState $state)
	{
		list($rows, $replaced) = $this->replaceExportedWithLessPriority($rows, $maxCount, $state);
		list($rows, $activated) = $this->activateStoredWithMorePriority($rows, $maxCount, $state);

		$rows += $replaced;
		$rows += $activated;

		return $rows;
	}

	protected function replaceExportedWithLessPriority(array $rows, $maxCount, CollectState $state)
	{
		$successRows = $this->onlySuccessRows($rows);

		if (empty($successRows)) { return [$rows, []]; }

		$firstRow = reset($successRows);
		$collectionId = $firstRow['COLLECTION_ID'];
		$collectionSign = $firstRow['COLLECTION_SIGN'];
		$elementIds = array_column($rows, 'ELEMENT_ID');
		$maxPriority = min(array_column($successRows, 'PRIORITY'));

		$limit = $maxCount;
		$fetchCount = min(count($successRows), $limit);
		$exported = $this->exportedWithLessPriority($collectionId, $collectionSign, $maxPriority, $elementIds, $fetchCount, $state);

		if (count($exported) < $maxCount)
		{
			$limit -= ($this->countExported($collectionId, $collectionSign, $elementIds, $state) - count($exported));
		}

		return $this->resolveStoredPriority($collectionId, $collectionSign, $rows, $exported, $limit);
	}

	protected function exportedWithLessPriority($collectionId, $collectionSign, $priority, array $exclude, $maxCount, CollectState $state)
	{
		if ($maxCount === 0) { return []; }

		$query = Export\Run\Storage\CollectionOfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'!=ELEMENT_ID' => $exclude,
				'=COLLECTION_ID' => $collectionId,
				'=COLLECTION_SIGN' => $collectionSign,
				'=STATUS' => Steps\Base::STORAGE_STATUS_SUCCESS,
				'>=PRIORITY' => $priority,
				$this->readyRefreshFilter($state),
			],
			'select' => [ 'ELEMENT_ID', 'PRIORITY', 'STATUS', 'WRITTEN' ],
			'order' => [ 'PRIORITY' => 'DESC', 'ELEMENT_ID' => 'DESC' ],
			'limit' => $maxCount,
		]);

		return Utils\ArrayHelper::columnToKey($query->fetchAll(), 'ELEMENT_ID');
	}

	protected function countExported($collectionId, $collectionSign, array $exclude = [], CollectState $state = null)
	{
		if ($this->exportedCount !== null) { return $this->exportedCount; }

		$this->exportedCount = Export\Run\Storage\CollectionOfferTable::getCount([
			'=SETUP_ID' => $this->setup->getId(),
			'!=ELEMENT_ID' => $exclude,
			'=COLLECTION_ID' => $collectionId,
			'=COLLECTION_SIGN' => $collectionSign,
			'=STATUS' => Steps\Base::STORAGE_STATUS_SUCCESS,
			$this->readyRefreshFilter($state),
		]);

		return $this->exportedCount;
	}

	protected function activateStoredWithMorePriority(array $rows, $maxCount, CollectState $state)
	{
		$writtenRows = $this->onlyWrittenRows($rows);

		if (empty($writtenRows)) { return [$rows, []]; }

		$firstRow = reset($writtenRows);
		$collectionId = $firstRow['COLLECTION_ID'];
		$collectionSign = $firstRow['COLLECTION_SIGN'];
		$elementIds = array_column($rows, 'ELEMENT_ID');
		$minPriority = max(array_column($writtenRows, 'PRIORITY'));

		$limit = $maxCount;
		$fetchCount = min(count($writtenRows), $maxCount);
		$deactivated = $this->deactivatedUnderMinPriority($collectionId, $collectionSign, $minPriority, $elementIds, $fetchCount, $state);

		if (empty($deactivated)) { return [$rows, []]; }

		$limit -= $this->countExported($collectionId, $collectionSign, $elementIds, $state);

		return $this->resolveStoredPriority($collectionId, $collectionSign, $rows, $deactivated, $limit);
	}

	protected function deactivatedUnderMinPriority($collectionId, $collectionSign, $priority, array $exclude, $maxCount, CollectState $state)
	{
		if ($maxCount === 0) { return []; }

		$query = Export\Run\Storage\CollectionOfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'!=ELEMENT_ID' => $exclude,
				'=COLLECTION_ID' => $collectionId,
				'=COLLECTION_SIGN' => $collectionSign,
				'=STATUS' => Steps\Base::STORAGE_STATUS_DUPLICATE,
				'<=PRIORITY' => $priority,
				$this->readyRefreshFilter($state),
			],
			'select' => [ 'ELEMENT_ID', 'PRIORITY', 'STATUS', 'WRITTEN' ],
			'order' => [ 'PRIORITY' => 'ASC', 'ELEMENT_ID' => 'ASC' ],
			'limit' => $maxCount,
		]);

		return Utils\ArrayHelper::columnToKey($query->fetchAll(), 'ELEMENT_ID');
	}

	protected function readyRefreshFilter(CollectState $state = null)
	{
		if ($state === null) { return []; }

		if ($state->runAction === Export\Run\Processor::ACTION_REFRESH)
		{
			return [ '>=TIMESTAMP_X' => $state->initTime ];
		}

		if ($state->runAction === Export\Run\Processor::ACTION_CHANGE)
		{
			if (empty($state->changes[Export\Run\Manager::ENTITY_TYPE_OFFER]) || count($state->changes) !== 1)
			{
				return [ '>=TIMESTAMP_X' => $state->initTime ];
			}

			if ($state->context['HAS_OFFER'])
			{
				return [
					'LOGIC' => 'OR',
					[ '>=TIMESTAMP_X' => $state->initTime ],
					[
						'!=ELEMENT_ID' => $state->changes[Export\Run\Manager::ENTITY_TYPE_OFFER],
						'!=OFFER.PARENT_ID' => $state->changes[Export\Run\Manager::ENTITY_TYPE_OFFER],
					],
				];
			}

			return [
				'LOGIC' => 'OR',
				[ '>=TIMESTAMP_X' => $state->initTime ],
				[ '!=ELEMENT_ID' => $state->changes[Export\Run\Manager::ENTITY_TYPE_OFFER] ],
			];
		}

		return [];
	}

	protected function resolveStoredPriority($collectionId, $collectionSign, array $rows, array $stored, $maxCount)
	{
		// sorted heap

		if ($maxCount > 0)
		{
			$heap = $this->sortRows($rows + $stored);
			$heap = array_slice($heap, 0, $maxCount, true);
		}
		else
		{
			$heap = [];
		}

		// mark rows over limit

		foreach ($rows as &$row)
		{
			if (!isset($heap[$row['ELEMENT_ID']]))
			{
				$row['STATUS'] = Steps\Base::STORAGE_STATUS_DUPLICATE;
			}
		}
		unset($row);

		// stored delete

		$changes = [];

		foreach ($stored as $elementId => $row)
		{
			$alreadyDeleted = ((int)$row['STATUS'] !== Steps\Base::STORAGE_STATUS_SUCCESS);
			$needDelete = !isset($heap[$elementId]);

			if ($alreadyDeleted === $needDelete) { continue; }

			$changes[$elementId] = [
				'SETUP_ID' => $this->setup->getId(),
				'ELEMENT_ID' => $row['ELEMENT_ID'],
				'COLLECTION_ID' => $collectionId,
				'COLLECTION_SIGN' => $collectionSign,
				'STATUS' => $needDelete
					? Export\Run\Steps\Base::STORAGE_STATUS_DUPLICATE
					: Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'WRITTEN' => $row['WRITTEN'],
				'PRIORITY' => $row['PRIORITY'],
				'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
			];
		}

		return [$rows, $changes];
	}

	protected function onlySuccessRows(array $rows)
	{
		return array_filter($rows, static function(array $row) { return $row['STATUS'] === Steps\Base::STORAGE_STATUS_SUCCESS; });
	}

	protected function onlyWrittenRows(array $rows)
	{
		return array_filter($rows, static function(array $row) { return $row['WRITTEN']; });
	}

	protected function sortRows(array $rows)
	{
		uasort($rows, static function(array $aRow, array $bRow) {
			$aPriority = (int)$aRow['PRIORITY'];
			$bPriority = (int)$bRow['PRIORITY'];

			if ($aPriority !== $bPriority) { return ($aPriority < $bPriority ? -1 : 1); }

			$aId = (int)$aRow['ELEMENT_ID'];
			$bId = (int)$bRow['ELEMENT_ID'];

			if ($aId === $bId) { return 0; }

			return ($aId < $bId ? -1 : 1);
		});

		return $rows;
	}

	protected function markOverLimit(array $rows, $maxCount)
	{
		if (count($rows) <= $maxCount) { return $rows; }

		$counter = 0;

		foreach ($rows as &$row)
		{
			$status = (int)$row['STATUS'];

			if ($counter >= $maxCount)
			{
				$row['STATUS'] = Steps\Base::STORAGE_STATUS_DUPLICATE;
			}
			else if ($status === Steps\Base::STORAGE_STATUS_SUCCESS)
			{
				++$counter;
			}
		}
		unset($row);

		return $rows;
	}

	public function fulfill($collectionId, $collectionSign, $maxCount)
	{
		$this->resetCache();

		$needCount = $maxCount - $this->countExported($collectionId, $collectionSign);
		$elementIds = $this->readyForActivate($collectionId, $collectionSign, $needCount);

		$this->activateStored($collectionId, $collectionSign, $elementIds);
	}

	protected function readyForActivate($collectionId, $collectionSign, $limit)
	{
		if ($limit <= 0) { return []; }

		$query = Export\Run\Storage\CollectionOfferTable::getList([
			'select' => [ 'ELEMENT_ID' ],
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'=COLLECTION_ID' => $collectionId,
				'=COLLECTION_SIGN' => $collectionSign,
				'=STATUS' => Steps\Base::STORAGE_STATUS_DUPLICATE,
			],
			'order' => [ 'PRIORITY' => 'ASC', 'ELEMENT_ID' => 'ASC' ],
			'limit' => $limit,
		]);

		return array_column($query->fetchAll(), 'ELEMENT_ID');
	}

	protected function activateStored($collectionId, $collectionSign, array $elementIds)
	{
		if (empty($elementIds)) { return; }

		Export\Run\Storage\CollectionOfferTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'=COLLECTION_ID' => $collectionId,
				'=COLLECTION_SIGN' => $collectionSign,
				'=ELEMENT_ID' => $elementIds,
			],
		], [
			'STATUS' => Steps\Base::STORAGE_STATUS_SUCCESS,
			'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
		]);
	}
}