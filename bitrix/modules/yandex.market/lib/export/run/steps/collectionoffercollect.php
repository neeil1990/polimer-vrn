<?php
namespace Yandex\Market\Export\Run\Steps;

use Yandex\Market\Data;
use Yandex\Market\Export;
use Yandex\Market\Result;
use Yandex\Market\Utils;

class CollectionOfferCollect extends Base
{
	const PRIORITY_MAX = 1000000;

	protected $limitService;

	public function __construct(Export\Run\Processor $processor)
	{
		parent::__construct($processor);

		$this->limitService = new CollectionOffer\CollectLimit($processor->getSetup());
	}

	public function getName()
	{
		return Export\Run\Manager::STEP_COLLECTION_OFFER_COLLECT;
	}

	public function run($action, $offset = null)
	{
		$result = new Result\Step();

		$state = new CollectionOffer\CollectState();
		$state->runAction = $action;
		$state->initTime = $this->processor->getParameter('initTimeUTC');

		if ($action === Export\Run\Processor::ACTION_CHANGE)
		{
			$state->changes = $this->getChanges();
		}

		$offsetObject = new Data\Run\Offset($offset, [
			'collection',
			'feedCollection',
			'product',
			'filter',
			'query',
			'element',
		]);

		(new Data\Run\Waterfall())
			->add([$this, 'iterateCollection'])
			->add([$this, 'iterateFeedCollection'])
			->add([$this, 'iterateProduct'])
			->add([$this, 'loadElements'])
			->add([$this, 'writeStorage'])
			->run($state, $offsetObject);

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

	protected function removeByFilter($filter, $context)
	{
		$exported = $this->exportedReadyForDelete($filter);

		$updated = $this->updateDataStorage($filter, [
			'STATUS' => static::STORAGE_STATUS_DELETE,
			'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
		]);

		if ($updated)
		{
			$this->removeDeletedLog($context);
		}

		$this->fulfilLimits($exported);
	}

	protected function exportedReadyForDelete($filter)
	{
		$dataClass = $this->getStorageDataClass();
		$filter[] = [
			'=SETUP_ID' => $this->getSetup()->getId(),
			'=STATUS' => Base::STORAGE_STATUS_SUCCESS,
		];

		$queryExists = $dataClass::getList([
			'filter' => $filter,
			'select' => [ 'SETUP_ID', 'ELEMENT_ID', 'COLLECTION_ID', 'COLLECTION_SIGN' ],
			'runtime' => $this->getStorageRuntime(),
		]);

		return $queryExists->fetchAll();
	}

	protected function fulfilLimits(array $rows)
	{
		if (empty($rows)) { return; }

		$collectionGrouped = Utils\ArrayHelper::groupBy($rows, 'COLLECTION_ID');

		/** @var Export\Collection\Model $collection */
		foreach ($this->processor->getSetup()->getCollectionCollection() as $collection)
		{
			$collectionId = $collection->getId();

			if (!isset($collectionGrouped[$collectionId]) || !$this->limitService->canUse($collection)) { continue; }

			$changedSigns = array_column($collectionGrouped[$collectionId], 'COLLECTION_SIGN', 'COLLECTION_SIGN');

			foreach ($collection->getStrategy()->getFeedCollections() as $feedCollection)
			{
				$collectionSign = $collection->makeCollectionSign($feedCollection);

				if (!isset($changedSigns[$collectionSign])) { continue; }

				$this->limitService->fulfill($collectionId, $collectionSign, $collection->getLimit()->count());
			}
		}
	}

	public function getReadyCount()
	{
		$dataClass = $this->getStorageDataClass();
		$context = $this->getContext();
		$filter = $this->getStorageReadyFilter($context);

		return $dataClass::getCount($filter);
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

	public function iterateCollection(callable $next, CollectionOffer\CollectState $state, Data\Run\Offset $offset)
	{
		$changedMap = $this->collectionChangedMap($state);

		/** @var Export\Collection\Model $collection */
		foreach ($this->processor->getSetup()->getCollectionCollection() as $collection)
		{
			$id = $collection->getId();

			if ($changedMap !== null && !isset($changedMap[$id])) { continue; }
			if (!$offset->tick('collection')) { continue; }
			if (!$collection->isActive() || !$collection->isActiveDate()) { continue; }

			$nextState = clone $state;
			$nextState->collection = $collection;
			$nextState->sourceMap = [];

			if ($this->limitService->canUse($collection))
			{
				$nextState->sourceMap['PRIORITY'] = [ $collection->getLimit()->sortField() ];
			}

			$next($nextState, $offset);

			if ($offset->interrupted()) { break; }
		}
	}

	protected function collectionChangedMap(CollectionOffer\CollectState $state)
	{
		if ($state->runAction !== Export\Run\Processor::ACTION_CHANGE) { return null; }

		$changes = $this->processor->getParameter('changes');

		if (
			empty($changes[Export\Run\Manager::ENTITY_TYPE_COLLECTION])
			|| count($changes) !== 1
		)
		{
			return null;
		}

		return array_flip((array)$changes[Export\Run\Manager::ENTITY_TYPE_COLLECTION]);
	}

	public function iterateFeedCollection(callable $next, CollectionOffer\CollectState $state, Data\Run\Offset $offset)
	{
		foreach ($state->collection->getStrategy()->getFeedCollections() as $feedCollection)
		{
			if (!$offset->tick('feedCollection')) { continue; }

			$nextState = clone $state;
			$nextState->feedCollection = $feedCollection;

			$next($nextState, $offset);

			if ($offset->interrupted()) { break; }
		}
	}

	public function iterateProduct(callable $next, CollectionOffer\CollectState $state, Data\Run\Offset $offset)
	{
		/** @var Export\CollectionProduct\Model $product */
		foreach ($state->feedCollection->getProductCollection() as $product)
		{
			if (!$offset->tick('product')) { continue; }

			$iblockLink = $this->getSetup()->getIblockLinkCollection()->getByIblockId($product->getIblockId());

			if ($iblockLink === null) { continue; }

			$context = $product->getContext();
			$selectBuilder = new Export\Routine\QueryBuilder\Select();

			$state->sourceSelect = $this->buildSourceSelect($state->sourceMap);
			$state->sourceSelect = $this->extendSourceSelect($state->sourceSelect, $iblockLink);
			$state->sourceSelect = $selectBuilder->boot($state->sourceSelect, $context);
			$state->querySelect = $selectBuilder->compile($state->sourceSelect, $context);

			/** @var Export\Filter\Model $filter */
			foreach ($product->getFilterCollection() as $filter)
			{
				if (!$offset->tick('filter')) { continue; }

				$filterBuilder = new Export\Routine\QueryBuilder\Filter();

				$sourceFilter = $filter->getSourceFilter();
				$sourceFilter = $filterBuilder->boot($sourceFilter, $context);

				foreach ($filterBuilder->compile($sourceFilter, $state->sourceSelect, $context) as $queryFilter)
				{
					if (!$offset->tick('query')) { continue; }

					$nextState = clone $state;
					$nextState->context = $context;
					$nextState->queryFilter = $queryFilter;

					$next($nextState, $offset);

					if ($offset->interrupted()) { break; }
				}

				$filterBuilder->release($sourceFilter, $context);

				if ($offset->interrupted()) { break; }
			}

			$selectBuilder->release($state->sourceSelect, $context);

			if ($offset->interrupted()) { break; }
		}
	}

	/** @noinspection DuplicatedCode */
	protected function buildSourceSelect(array $sourceMap)
	{
		$result = [];

		foreach ($sourceMap as $sourceFields)
		{
			foreach ($sourceFields as $sourceField)
			{
				if (!isset($result[$sourceField['SOURCE']]))
				{
					$result[$sourceField['SOURCE']] = [];
				}

				if (!in_array($sourceField['FIELD'], $result[$sourceField['SOURCE']], true))
				{
					$result[$sourceField['SOURCE']][] = $sourceField['FIELD'];
				}
			}
		}

		return $result;
	}

	protected function extendSourceSelect(array $sourceSelect, Export\IblockLink\Model $iblockLink)
	{
		$priceTag = $iblockLink->getTagDescription('price');

		if (
			!isset($priceTag['VALUE']['TYPE'], $priceTag['VALUE']['FIELD'], $sourceSelect[$priceTag['VALUE']['TYPE']])
			|| !in_array($priceTag['VALUE']['FIELD'], $sourceSelect[$priceTag['VALUE']['TYPE']], true)
		)
		{
			return $sourceSelect;
		}

		$currencyTag = $iblockLink->getTagDescription('currencyId');

		if (!isset($currencyTag['VALUE']['TYPE'], $currencyTag['VALUE']['FIELD'])) { return $sourceSelect; }

		if (!isset($sourceSelect[$currencyTag['VALUE']['TYPE']]))
		{
			$sourceSelect[$currencyTag['VALUE']['TYPE']] = [];
		}

		$sourceSelect[$currencyTag['VALUE']['TYPE']][] = $currencyTag['VALUE']['FIELD'];

		return $sourceSelect;
	}

	/** @noinspection DuplicatedCode */
	public function loadElements(callable $next, CollectionOffer\CollectState $state, Data\Run\Offset $offset)
	{
		do
		{
			$elementFetcher = new Export\Routine\QueryBuilder\ElementFetcher();
			$elementFetcher->included(Export\Run\Storage\OfferTable::class, $this->includedFilter($state), 'ELEMENT_ID', 'PARENT_ID');

			$queryResult = $elementFetcher->load($state->queryFilter, $state->querySelect, $state->context, $offset->get('element'));

			$sourceFetcher = new Export\Routine\QueryBuilder\SourceFetcher();
			$sourceValues = $sourceFetcher->load($state->sourceSelect, $queryResult['ELEMENT'], $queryResult['PARENT'], $state->context);

			$elementValues = $this->extractValues($state->sourceMap, $sourceValues);
			$priorities = Utils\ArrayHelper::column($elementValues, 'PRIORITY');

			$next($queryResult['ELEMENT'], $priorities, $state);

			$offset->set('element', $queryResult['OFFSET']);

			if (!$queryResult['HAS_NEXT']) { break; }

			if ($this->processor->isTimeExpired())
			{
				$offset->interrupt();
				break;
			}
		}
		while (true);
	}

	protected function includedFilter(CollectionOffer\CollectState $state)
	{
		$result = [
			'=SETUP_ID' => $this->processor->getSetup()->getId(),
			'=STATUS' => Export\Run\Steps\Offer::STORAGE_STATUS_SUCCESS,
		];

		if ($state->runAction === Export\Run\Processor::ACTION_CHANGE)
		{
			$result[] = $this->offerChangesFilter($state);
		}

		return $result;
	}

	protected function offerChangesFilter(CollectionOffer\CollectState $state)
	{
		$changes = $this->processor->getParameter('changes');

		if (
			!empty($changes[Export\Run\Manager::ENTITY_TYPE_COLLECTION])
			|| empty($changes[Export\Run\Manager::ENTITY_TYPE_OFFER])
		)
		{
			return [];
		}

		if ($state->context['HAS_OFFER'])
		{
			return [
				'LOGIC' => 'OR',
				[ '=ELEMENT_ID' => $changes[Export\Run\Manager::ENTITY_TYPE_OFFER] ],
				[ '=PARENT_ID' => $changes[Export\Run\Manager::ENTITY_TYPE_OFFER] ],
			];
		}

		return [
			'=ELEMENT_ID' => $changes[Export\Run\Manager::ENTITY_TYPE_OFFER],
		];
	}

	/** @noinspection DuplicatedCode */
	protected function extractValues(array $sourceMap, array $sourceValues)
	{
		$result = [];

		foreach ($sourceValues as $elementId => $elementValues)
		{
			$extracted = [];

			foreach ($sourceMap as $target => $mapFields)
			{
				foreach ($mapFields as $mapField)
				{
					if (!isset($elementValues[$mapField['SOURCE']][$mapField['FIELD']])) { continue; }

					$value = $elementValues[$mapField['SOURCE']][$mapField['FIELD']];

					if (Utils\Value::isEmpty($value)) { continue; }

					$extracted[$target] = $value;
					break;
				}
			}

			if (empty($extracted)) { continue; }

			$result[$elementId] = $extracted;
		}

		return $result;
	}

	public function writeStorage(callable $next, array $elements, array $priorities, CollectionOffer\CollectState $state)
	{
		$rows = $this->compileRows($elements, $priorities, $state);
		$rows = $this->markWritten($rows, $state);
		$rows = $this->applyLimits($rows, $state);

		$this->insertRows($rows);

		$next($elements, $state);
	}

	protected function compileRows(array $elements, array $priorities, CollectionOffer\CollectState $state)
	{
		$now = new Data\Type\CanonicalDateTime();
		$result = [];

		foreach ($elements as $key => $element)
		{
			$priority = (isset($priorities[$key]) ? Data\Number::castInteger($priorities[$key]) : null);
			$priority = ($priority !== null ? $priority : static::PRIORITY_MAX);

			if ($state->collection->getLimit()->sortInverted())
			{
				$priority = static::PRIORITY_MAX - $priority;
			}

			$result[$key] = [
				'SETUP_ID' => $this->processor->getSetup()->getId(),
				'ELEMENT_ID' => $element['ID'],
				'COLLECTION_ID' => $state->collection->getId(),
				'COLLECTION_SIGN' => $state->collection->makeCollectionSign($state->feedCollection),
				'STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'WRITTEN' => false,
				'PRIORITY' => max(0, $priority),
				'TIMESTAMP_X' => $now,
			];
		}

		return $result;
	}

	protected function applyLimits(array $rows, CollectionOffer\CollectState $state)
	{
		if (!$this->limitService->canUse($state->collection)) { return $rows; }

		return $this->limitService->apply($rows, $state);
	}

	protected function markWritten(array $rows, CollectionOffer\CollectState $state)
	{
		$written = $this->written($rows, $state);

		foreach ($rows as &$row)
		{
			if (!isset($written[$row['ELEMENT_ID']])) { continue; }

			$row['WRITTEN'] = $written[$row['ELEMENT_ID']];
		}
		unset($row);

		return $rows;
	}

	protected function written(array $rows, CollectionOffer\CollectState $state)
	{
		if (empty($rows)) { return []; }

		$query = Export\Run\Storage\CollectionOfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->processor->getSetup()->getId(),
				'=ELEMENT_ID' => array_column($rows, 'ELEMENT_ID'),
				'=COLLECTION_SIGN' => $state->collection->makeCollectionSign($state->feedCollection),
			],
			'select' => [ 'ELEMENT_ID', 'WRITTEN' ],
		]);

		return array_column($query->fetchAll(), 'WRITTEN', 'ELEMENT_ID');
	}

	protected function insertRows(array $rows)
	{
		if (empty($rows)) { return; }

		Export\Run\Storage\CollectionOfferTable::addBatch($rows, true);
	}

	/**
	 * @return Export\Run\Storage\CollectionOfferTable
	 * @noinspection PhpIncompatibleReturnTypeInspection
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	protected function getStorageDataClass()
	{
		return Export\Run\Storage\CollectionOfferTable::class;
	}

	protected function getDataStorageDisabledFields()
	{
		return [
			'HASH' => true,
			'PRIMARY' => true,
			'CONTENTS' => true,
		];
	}

	/** @noinspection DuplicatedCode */
	protected function getStorageChangesFilter($changes, $context)
	{
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
				'LOGIC' => 'OR',
				[ '=ELEMENT_ID' => $changes[Export\Run\Manager::ENTITY_TYPE_OFFER] ],
				[ '=OFFER.PARENT_ID' => $changes[Export\Run\Manager::ENTITY_TYPE_OFFER] ],
			];
		}

		if (count($partials) !== 1) { return []; }

		return reset($partials);
	}
}