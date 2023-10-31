<?php
namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market\Data;
use Yandex\Market\Export;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Result;

/**
 * @method Export\Xml\Tag\Base getTag($type = null)
 */
class CollectionOfferInsert extends Base
{
	use Concerns\HasOnce;

	public function getName()
	{
		return Export\Run\Manager::STEP_COLLECTION_OFFER_INSERT;
	}

	public function run($action, $offset = null)
	{
		$result = new Result\Step();
		$offsetObject = new Data\Run\Offset($offset, [
			'element',
		]);

		(new Data\Run\Waterfall())
			->add([$this, 'fetch'])
			->add([$this, 'write'])
			->add([$this, 'commit'])
			->run($action, $offsetObject);

		if ($offsetObject->interrupted())
		{
			$result->setOffset((string)$offsetObject);
			$result->setTotal(1);

			if ($this->getParameter('progressCount') === true)
			{
				$result->setReadyCount($this->getReadyCount());
			}
		}

		return $result;
	}

	public function getReadyCount()
	{
		return Export\Run\Storage\CollectionOfferTable::getCount([
			'=SETUP_ID' => $this->processor->getSetup()->getId(),
			'=STATUS' => Base::STORAGE_STATUS_SUCCESS,
			'=WRITTEN' => true,
			'>=TIMESTAMP_X' => $this->processor->getParameter('initTimeUTC'),
		]);
	}

	public function getFormatTag(Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return $format->getCollectionId();
	}

	public function isVirtual()
	{
		return true;
	}

	protected function getIgnoredTypeChanges()
	{
		return [
			Export\Run\Manager::ENTITY_TYPE_CURRENCY => true,
			Export\Run\Manager::ENTITY_TYPE_CATEGORY => true,
			Export\Run\Manager::ENTITY_TYPE_PROMO => true,
			Export\Run\Manager::ENTITY_TYPE_GIFT => true,
		];
	}

	public function fetch(callable $next, $action, Data\Run\Offset $offset)
	{
		do
		{
			$limit = 100;
			$filter = [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'>=ELEMENT_ID' => (int)$offset->get('element'),
				[
					'LOGIC' => 'OR',
					[ '=STATUS' => Base::STORAGE_STATUS_SUCCESS, '=WRITTEN' => false ],
					[ '!=STATUS' => Base::STORAGE_STATUS_SUCCESS, '=WRITTEN' => true ],
				],
			];

			if ($action === Export\Run\Processor::ACTION_CHANGE)
			{
				$filter[] = $this->changesFilter();
			}

			$query = Export\Run\Storage\CollectionOfferTable::getList([
				'filter' => $filter,
				'select' => [ 'ELEMENT_ID' ],
				'group' => [ 'ELEMENT_ID' ],
				'order' => [ 'ELEMENT_ID' => 'ASC' ],
				'limit' => $limit,
			]);

			$elementIds = array_column($query->fetchAll(), 'ELEMENT_ID');

			if (empty($elementIds)) { break; }

			$next($elementIds);

			$offset->set('element', end($elementIds));

			if (count($elementIds) < $limit) { break; }

			if ($this->processor->isTimeExpired())
			{
				$offset->interrupt();
				break;
			}
		}
		while (true);
	}

	/** @noinspection DuplicatedCode */
	protected function changesFilter()
	{
		$changes = $this->processor->getParameter('changes');
		$partials = [];

		if (!empty($changes[Export\Run\Manager::ENTITY_TYPE_COLLECTION]))
		{
			$partials[] = [
				'=COLLECTION_ID' => $changes[Export\Run\Manager::ENTITY_TYPE_COLLECTION],
			];
		}

		if (!empty($changes[Export\Run\Manager::ENTITY_TYPE_OFFER]))
		{
			$partials[] = [
				'>=TIMESTAMP_X' => $this->processor->getParameter('initTimeUTC'),
			];
		}

		if (count($partials) !== 1) { return []; }

		return reset($partials);
	}

	public function write(callable $next, array $elementIds)
	{
		$tasks = $this->makeTasks($elementIds);
		$written = $this->searchWritten($tasks);
		$modified = $this->modifyWritten($written, $tasks);
		$changed = array_diff_assoc($modified, $written);

		$this->updateWritten($changed);
		$this->updateOfferStorage($changed, $tasks);

		$next($elementIds);
	}

	protected function makeTasks(array $elementIds)
	{
		$tasks = [];

		$query = Export\Run\Storage\CollectionOfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'=ELEMENT_ID' => $elementIds,
			],
			'select' => [
				'ELEMENT_ID',
				'STATUS',
				'COLLECTION_STATUS' => 'COLLECTION.STATUS',
				'COLLECTION_PRIMARY' => 'COLLECTION.PRIMARY',
				'PRIMARY' => 'OFFER.PRIMARY',
			],
		]);

		while ($row = $query->fetch())
		{
			$collectionValid = ((int)$row['COLLECTION_STATUS'] === Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS);
			$offerValid = ((int)$row['STATUS'] === Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS);

			if (!isset($tasks[$row['ELEMENT_ID']]))
			{
				$tasks[$row['ELEMENT_ID']] = [
					'ELEMENT_ID' => $row['ELEMENT_ID'],
					'PRIMARY' => $row['PRIMARY'],
					'COLLECTION_PRIMARY' => [],
				];
			}

			if ($collectionValid && $offerValid)
			{
				$tasks[$row['ELEMENT_ID']]['COLLECTION_PRIMARY'][] = $row['COLLECTION_PRIMARY'];
			}
		}

		return $tasks;
	}

	protected function searchWritten(array $tasks)
	{
		$offerTag = $this->offerStep()->getTag();
		$primaryAttribute = $offerTag->getPrimary();

		Assert::notNull($primaryAttribute, 'offer->primaryAttribute');

		return $this->processor->getWriter()->searchTagList(
			$offerTag->getName(),
			array_column($tasks, 'PRIMARY'),
			$primaryAttribute->getName()
		);
	}

	protected function updateWritten(array $written)
	{
		$offerTag = $this->offerStep()->getTag();
		$primaryAttribute = $offerTag->getPrimary();

		Assert::notNull($primaryAttribute, 'offer->primaryAttribute');

		return $this->processor->getWriter()->updateTagList(
			$offerTag->getName(),
			$written,
			$primaryAttribute->getName()
		);
	}

	protected function updateOfferStorage(array $written, array $tasks)
	{
		$hashMap = $this->calculateWrittenHash($written);
		$offerRows = $this->compileOfferRows($hashMap, $tasks);

		if (empty($offerRows)) { return; }

		Export\Run\Storage\OfferTable::addBatch($offerRows, [ 'HASH' ]);
	}

	protected function calculateWrittenHash(array $written)
	{
		$offerStep = $this->offerStep();
		$result = [];

		foreach ($written as $primary => $xmlContent)
		{
			$result[$primary] = $offerStep->calculateXmlContentHash($xmlContent);
		}

		return $result;
	}

	protected function compileOfferRows(array $hashMap, array $tasks)
	{
		$primaryMap = array_column($tasks, 'ELEMENT_ID', 'PRIMARY');
		$result = [];

		foreach ($hashMap as $primary => $hash)
		{
			if (!isset($primaryMap[$primary]))
			{
				trigger_error(sprintf('missing primaryMap %s', $primary), E_USER_WARNING);
				continue;
			}

			$result[] = [
				'SETUP_ID' => $this->processor->getSetup()->getId(),
				'ELEMENT_ID' => $primaryMap[$primary],
				'HASH' => $hash,
			];
		}

		return $result;
	}

	protected function modifyWritten(array $written, array $tasks)
	{
		$primaryMap = array_column($tasks, 'ELEMENT_ID', 'PRIMARY');

		foreach ($written as $primary => &$tagString)
		{
			if (!isset($primaryMap[$primary]))
			{
				trigger_error(sprintf('cant find tag %s element', $primary), E_USER_WARNING);
				continue;
			}

			$elementId = $primaryMap[$primary];
			$task = $tasks[$elementId];

			$tagString = $this->updateTagCollections($tagString, $task['COLLECTION_PRIMARY']);
		}
		unset($tagString);

		return $written;
	}

	protected function updateTagCollections($tagString, array $collectionPrimaries)
	{
		$tagName = $this->getTag()->getName();

		sort($collectionPrimaries);

		list($tagPartials, $storedCollections) = Data\Xml\TagString::sliceByTag($tagString, $tagName);
		$actualCollections = array_map(static function($collectionId) use ($tagName) {
			return sprintf('<%1$s>%2$s</%1$s>', $tagName, $collectionId);
		}, $collectionPrimaries);

		if (
			count($actualCollections) === count($storedCollections)
			&& count(array_diff($actualCollections, $storedCollections)) === 0
		)
		{
			return $tagString;
		}

		if (count($tagPartials) > 1)
		{
			$openTag = array_shift($tagPartials);

			return $openTag . implode('', $actualCollections) . implode('', $tagPartials);
		}

		$collectionsTagString = implode('', $actualCollections);

		// insert after siblings

		foreach ($this->siblingAnchorTags() as $anchorTag)
		{
			$result = Data\Xml\TagString::injectAfter($tagString, $anchorTag, $collectionsTagString);

			if ($result !== null) { return $result; }
		}

		// append to offer

		$result = Data\Xml\TagString::injectAppend($tagString, $this->parentAnchorTag(), $collectionsTagString);

		if ($result === null)
		{
			throw new Main\SystemException('cant find collectionId anchor');
		}

		return $result;
	}

	protected function siblingAnchorTags()
	{
		return $this->once('siblingAnchorTags', null, function() {
			$result = [];
			$collectionTag = $this->getTag();

			foreach ($this->getFormat()->getOffer()->getChildren() as $sibling)
			{
				if ($sibling->getName() === $collectionTag->getName()) { break; }

				$result[] = $sibling->getName();
			}

			return array_reverse($result);
		});
	}

	protected function parentAnchorTag()
	{
		return $this->once('parentAnchorTag', null, function() {
			return $this->getFormat()->getOffer()->getName();
		});
	}

	public function commit(callable $next, array $elementIds)
	{
		$this->commitWritten($elementIds);
		$this->commitUnwritten($elementIds);
		$this->commitFailed($elementIds);
		$this->releaseDeleted($elementIds);

		$next($elementIds);
	}

	protected function commitWritten(array $elementIds)
	{
		if (empty($elementIds)) { return; }

		Export\Run\Storage\CollectionOfferTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'=ELEMENT_ID' => $elementIds,
				'=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'=WRITTEN' => false,
				'=COLLECTION.STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
			],
		], [
			'WRITTEN' => true,
		]);
	}

	protected function commitUnwritten(array $elementIds)
	{
		if (empty($elementIds)) { return; }

		Export\Run\Storage\CollectionOfferTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'=ELEMENT_ID' => $elementIds,
				'!=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'=WRITTEN' => true,
			],
		], [
			'WRITTEN' => false,
		]);
	}

	protected function commitFailed(array $elementIds)
	{
		if (empty($elementIds)) { return; }

		Export\Run\Storage\CollectionOfferTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'=ELEMENT_ID' => $elementIds,
				'=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'!=COLLECTION.STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
			],
		], [
			'STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_FAIL,
			'WRITTEN' => false,
		]);
	}

	protected function releaseDeleted(array $elementIds)
	{
		if (empty($elementIds)) { return; }

		Export\Run\Storage\CollectionOfferTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'=ELEMENT_ID' => $elementIds,
				'=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_DELETE,
			],
		]);
	}

	/** @return Offer */
	protected function offerStep()
	{
		$offerStep = $this->processor->getStep(Export\Run\Manager::STEP_OFFER);

		Assert::typeOf($offerStep, Offer::class, 'offerStep');

		return $offerStep;
	}
}