<?php
namespace Yandex\Market\Export\Run\Steps;

use Yandex\Market;

class Collection extends Base
{
	use Market\Reference\Concerns\HasMessage;

	protected $tagDescriptionList;
	protected $flushQueueSourceValues = [];
	protected $flushQueueElements = [];

    public function getName()
    {
        return Market\Export\Run\Manager::STEP_COLLECTION;
    }

    public function run($action, $offset = null)
    {
		$this->setRunAction($action);

		$stepResult = new Market\Result\Step();
		$offsetObject = new Market\Data\Run\Offset($offset, [
			'collection',
			'feedCollection',
		]);

	    (new Market\Data\Run\Waterfall())
		    ->add([$this, 'iterateCollection'])
	        ->add([$this, 'iterateFeedCollection'])
	        ->add([$this, 'processFeedCollection'])
	        ->run($stepResult, $offsetObject);

        return $stepResult;
    }

	public function iterateCollection(
		callable $next,
		Market\Result\Step $stepResult,
		Market\Data\Run\Offset $offset
	)
	{
		$readyCount = 0;
		$changedMap = null;

		if ($this->getRunAction() === Market\Export\Run\Processor::ACTION_CHANGE)
		{
			$changedMap = $this->getChangesMap();
		}

		/** @var Market\Export\Collection\Model $collection */
		foreach ($this->getSetup()->getCollectionCollection() as $collection)
		{
			$id = $collection->getId();
			$isActive = ($collection->isActive() && $collection->isActiveDate());

			if ($changedMap !== null && !isset($changedMap[$id])) { continue; }

			if (!$offset->tick('collection'))
			{
				if ($isActive) { ++$readyCount; }
				continue;
			}

			if (!$isActive) { continue; }

			$this->tagDescriptionList = $collection->getTagDescriptionList();
			$next($collection, $offset);

			if ($offset->interrupted()) { break; }

			++$readyCount;
		}

		$stepResult->setReadyCount($readyCount);

		if ($offset->interrupted())
		{
			$stepResult->setOffset((string)$offset);
			$stepResult->setTotal(1);
		}

		$this->flush();
	}

	public function iterateFeedCollection(
		callable $next,
		Market\Export\Collection\Model $collection,
		Market\Data\Run\Offset $offset
	)
	{
		foreach ($collection->getStrategy()->getFeedCollections() as $feedCollection)
		{
			if (!$offset->tick('feedCollection')) { continue; }

			$next($collection, $feedCollection);

			if ($this->getProcessor()->isTimeExpired())
			{
				$offset->tick('feedCollection'); // switch to next
				$offset->interrupt();
				break;
			}
		}
	}

	public function processFeedCollection(
		callable $next,
		Market\Export\Collection\Model $collection,
		Market\Export\Collection\Data\FeedCollection $feedCollection
	)
	{
		$id = $collection->makeCollectionSign($feedCollection);

		$fields = [
			'ID' => $id,
			'PRIMARY' => $collection->makeCollectionPrimary($feedCollection),
			'COLLECTION_ID' => $collection->getId(),
		];
		$fields += $feedCollection->getFields();

		$this->enqueue($id, $fields, [
			'COLLECTION' => $fields,
		]);

		$next($feedCollection);
	}

	public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
    {
        return $format->getCollection();
    }

    public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
    {
        return $format->getCollectionParentName();
    }

	protected function usePrimaryCollision($context)
	{
		return true;
	}

	protected function getDataLogEntityType()
    {
        return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_COLLECTION;
    }

    protected function getStorageDataClass()
    {
        return Market\Export\Run\Storage\CollectionTable::class;
    }

	protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
	{
		return [
			'COLLECTION_ID' => $element['COLLECTION_ID'],
		];
	}

	protected function getChangesMap()
    {
	    $changes = $this->getChanges();
	    $isOnlyCollectionChanged = false;
	    $changedMap = [];

	    if (isset($changes[Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION])) // has only changes in collection
	    {
		    $isOnlyCollectionChanged = (count($changes) === 1);
		    $changedMap += array_flip($changes[Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION]);
	    }

	    if (!$isOnlyCollectionChanged)
	    {
	    	$changedIds = $this->getStorageChangedOfferCollections();
		    $changedMap += array_flip($changedIds);
	    }

	    return $changedMap;
    }

    protected function getStorageChangedOfferCollections()
    {
		$query = Market\Export\Run\Storage\CollectionOfferTable::getList([
			'select' => [ 'COLLECTION_ID' ],
			'filter' => [
				'=SETUP_ID' => $this->getSetup()->getId(),
				'>=TIMESTAMP_X' => $this->getParameter('initTimeUTC'),
			],
			'group' => [ 'COLLECTION_ID' ],
		]);

    	return array_column($query->fetchAll(), 'COLLECTION_ID');
    }

	protected function getStorageChangesFilter($changes, $context)
    {
		if (empty($changes)) { return null; }

	    $result = [];

		if (!empty($changes[Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION]))
		{
			$result[] = [
				'=COLLECTION_ID' => $changes[Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION],
			];
		}

        if (!empty($changes[Market\Export\Run\Manager::ENTITY_TYPE_OFFER]))
        {
            $result[] = [
                '>=COLLECTION_OFFER.TIMESTAMP_X' => $this->getParameter('initTimeUTC'),
            ];
        }

        if (count($result) > 1)
        {
            $result['LOGIC'] = 'OR';
        }

        return $result;
    }

	protected function getIgnoredTypeChanges()
	{
		return [
			Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY => true,
			Market\Export\Run\Manager::ENTITY_TYPE_CATEGORY => true,
			Market\Export\Run\Manager::ENTITY_TYPE_PROMO => true,
			Market\Export\Run\Manager::ENTITY_TYPE_GIFT => true,
		];
	}

    protected function getFlushLimit()
    {
        return (int)($this->getParameter('collectionPageSize') ?: Market\Config::getOption('export_run_collection_page_size') ?: 20);
    }

    protected function isAllowDeleteParent()
    {
        return true;
    }

	protected function enqueue($primary, array $element, array $sourceValues)
	{
		$this->flushQueueElements[$primary] = $element;
		$this->flushQueueSourceValues[$primary] = $sourceValues;

		if (count($this->flushQueueElements) >= $this->getFlushLimit())
		{
			$this->flush();
		}
	}

	protected function flush()
	{
		if (empty($this->flushQueueElements)) { return; }

		$context = $this->getSetup()->getContext();

		$tagValuesList = $this->buildTagValuesList($this->tagDescriptionList, $this->flushQueueSourceValues, $context);

		$this->extendData($tagValuesList, $this->flushQueueElements, $context);
		$this->writeData($tagValuesList, $this->flushQueueElements, $context);

		$this->flushQueueSourceValues = [];
		$this->flushQueueElements = [];
	}

	protected function buildTagList($tagValuesList, array $context = [])
	{
		$tagResultList = parent::buildTagList($tagValuesList, $context);

		$this->validateTags($tagResultList, $context);

		return $tagResultList;
	}

	protected function validateTags(array $tagResultList, array $context)
	{
		$collectedOffers = $this->collectedOffers(array_keys($tagResultList), $context);

		/** @var Market\Result\XmlNode $tagResult */
		foreach ($tagResultList as $key => $tagResult)
		{
			if (!isset($collectedOffers[$key]))
			{
				$tagResult->addError(new Market\Error\Base(self::getMessage('ERROR_OFFERS_NOT_FOUND')));
			}
		}
	}

	protected function collectedOffers(array $collectionSigns, array $context)
	{
		if (empty($collectionSigns)) { return []; }

		$query = Market\Export\Run\Storage\CollectionOfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $context['SETUP_ID'],
				'=COLLECTION_SIGN' => $collectionSigns,
				'=STATUS' => Base::STORAGE_STATUS_SUCCESS,
			],
			'select' => [ 'COLLECTION_SIGN' ],
			'group' => [ 'COLLECTION_SIGN' ],
		]);

		return array_column($query->fetchAll(), 'COLLECTION_SIGN', 'COLLECTION_SIGN');
	}
}