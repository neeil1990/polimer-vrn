<?php
namespace Yandex\Market\Export\Run\Steps\Offer;

use Yandex\Market\Export;

class CollectionLink
{
	const SOURCE_TYPE = 'VIRTUAL_COLLECTION_OFFER';

	protected $setup;
	protected $format;
	protected $runAction;
	protected $wait = [];
	protected $success = [];

	public function __construct(
		Export\Setup\Model $setup,
		Export\Xml\Format\Reference\Base $format,
		$runAction
	)
	{
		$this->setup = $setup;
		$this->format = $format;
		$this->runAction = $runAction;
	}

	public function extend(array $elementIds, array $sourceValuesList)
	{
		$known = $this->known($elementIds);

		if (empty($known)) { return $sourceValuesList; }

		foreach ($known as $elementId => $collectionIds)
		{
			if (!isset($sourceValuesList[$elementId])) { continue; }

			sort($collectionIds);

			$sourceValuesList[$elementId][static::SOURCE_TYPE] = [
				'COLLECTION_ID' => $collectionIds,
			];
 		}

		return $sourceValuesList;
	}

	public function known(array $elementIds)
	{
		if (empty($elementIds) || !$this->need()) { return []; }

		$result = [];

		$query = Export\Run\Storage\CollectionOfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'=ELEMENT_ID' => $elementIds,
				'=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
			],
			'select' => [
				'ELEMENT_ID',
				'WRITTEN',
				'COLLECTION_STATUS' => 'COLLECTION.STATUS',
				'COLLECTION_PRIMARY' => 'COLLECTION.PRIMARY',
			],
		]);

		while ($row = $query->fetch())
		{
			$elementId = (int)$row['ELEMENT_ID'];

			if (!isset($result[$elementId]))
			{
				$result[$elementId] = [];
			}

			if ((int)$row['COLLECTION_STATUS'] !== Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS)
			{
				continue;
			}

			if ($row['WRITTEN'])
			{
				$this->success[$elementId] = $elementId;
			}
			else
			{
				$this->wait[$elementId] = $elementId;
			}

			$result[$elementId][] = $row['COLLECTION_PRIMARY'];
		}

		return $result;
	}

	protected function need()
	{
		return (
			$this->runAction !== Export\Run\Processor::ACTION_FULL
			&& $this->format->getCollectionId() !== null
		);
	}

	public function commit(array $written)
	{
		$toDelete = array_diff_key($this->wait + $this->success, $written);
		$toSuccess = array_intersect_key($this->wait, $written);

		$this->releaseDeleted($toDelete);
		$this->commitWritten($toSuccess);

		$this->success = [];
		$this->wait = [];
	}

	protected function releaseDeleted(array $toDelete)
	{
		if (empty($toDelete)) { return; }

		Export\Run\Storage\CollectionOfferTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'=ELEMENT_ID' => array_values($toDelete),
			],
		]);
	}

	protected function commitWritten(array $toSuccess)
	{
		if (empty($toSuccess)) { return; }

		Export\Run\Storage\CollectionOfferTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->setup->getId(),
				'=ELEMENT_ID' => array_values($toSuccess),
				'=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'=WRITTEN' => false,
			],
		], [
			'WRITTEN' => true,
		]);
	}
}