<?php
namespace Yandex\Market\SalesBoost\Run\Steps\Collector;

use Yandex\Market\Export;
use Yandex\Market\Data;
use Yandex\Market\Glossary;
use Yandex\Market\SalesBoost;
use Yandex\Market\Trading;
use Yandex\Market\Utils;

class ElementsLoader
{
	protected $processor;
	protected $firstFilter = true;

	public function __construct(SalesBoost\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function __invoke(callable $next, State $state, Data\Run\Offset $offset)
	{
		(new Data\Run\Waterfall())
			->add([$this, 'iterateBoosts'])
			->add([$this, 'iterateProductCollection'])
			->add([$this, 'iterateFilterCollection'])
			->add([$this, 'loadElements'])
			->after($next)
			->run($state, $offset);
	}

	public function iterateBoosts(callable $next, State $state, Data\Run\Offset $offset)
	{
		foreach ($this->boosts($state) as $boost)
		{
			if (!$offset->tick('boost')) { continue; }

			if (!$boost->isActive() || !$boost->isActiveDate()) { continue; }

			$boostState = clone $state;
			$boostState->boost = $boost;
			$boostState->context = $boost->getContext();
			$boostState->sourceMap = [
				'BID' => $boost->getBidFields(),
			];

			$next($boostState, $offset);

			if ($offset->interrupted()) { break; }
		}
	}

	protected function boosts(State $state)
	{
		$primaryFilter = [];
		$activeFilter = [
			'=ACTIVE' => true,
		];

		if (isset($state->selectedBoosts))
		{
			if (empty($state->selectedBoosts)) { return []; }

			$primaryFilter['=ID'] = $state->selectedBoosts;
		}

		return SalesBoost\Setup\Model::loadList([
			'filter' => $primaryFilter + $activeFilter,
		]);
	}

	public function iterateProductCollection(callable $next, State $state, Data\Run\Offset $offset)
	{
		/** @var SalesBoost\Product\Model $boostProduct */
		foreach ($state->boost->getProductCollection() as $boostProduct)
		{
			if (!$offset->tick('product')) { continue; }

			$selectBuilder = new Export\Routine\QueryBuilder\Select();

			$productState = clone $state;
			$productState->boostProduct = $boostProduct;
			$productState->context += $boostProduct->getContext();
			$productState->sourceSelect = $this->buildSourceSelect($productState->sourceMap);
			$productState->sourceSelect = $selectBuilder->boot($productState->sourceSelect, $productState->context);
			$productState->querySelect = $selectBuilder->compile($productState->sourceSelect, $productState->context);

			$next($productState, $offset);

			$selectBuilder->release($productState->sourceSelect, $productState->context);

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

	public function iterateFilterCollection(callable $next, State $state, Data\Run\Offset $offset)
	{
		$this->firstFilter = true;

		/** @var Export\Filter\Model $exportFilter */
		foreach ($state->boostProduct->getFilterCollection() as $exportFilter)
		{
			if (!$offset->tick('filter'))
			{
				$this->firstFilter = false;
				continue;
			}

			$changesFilter = null;

			if ($state->runAction === Data\Run\Processor::ACTION_CHANGE)
			{
				$changesFilter = $this->makeQueryChangesFilter($state->changes, $state->context);

				if ($changesFilter === null) { continue; } // changed other entity
			}

			$filterBuilder = new Export\Routine\QueryBuilder\Filter();

			$filterState = clone $state;
			$filterState->exportFilter = $exportFilter;
			$filterState->context += $exportFilter->getContext(true);
			$sourceFilter = $exportFilter->getSourceFilter();
			$sourceFilter = $filterBuilder->boot($sourceFilter, $filterState->context);

			foreach ($filterBuilder->compile($sourceFilter, $filterState->sourceSelect, $filterState->context, $changesFilter) as $queryFilter)
			{
				if (!$offset->tick('query')) { continue; }

				$filterState->queryFilter = $queryFilter;

				$next($filterState, $offset);
			}

			$filterBuilder->release($sourceFilter, $filterState->context);
			$this->firstFilter = false;

			if ($offset->interrupted()) { break; }
		}
	}

	/** @noinspection DuplicatedCode */
	protected function makeQueryChangesFilter(array $changes, array $context)
	{
		if (!empty($changes[Glossary::SERVICE_SALES_BOOST])) { return []; }
		if (empty($changes[Glossary::ENTITY_OFFER])) { return null; }

		$ids = (array)$changes[Glossary::ENTITY_OFFER];

		if ($context['HAS_OFFER'])
		{
			$idsMap = array_flip($ids);

			$queryOffers = \CIBlockElement::GetList(
				[],
				[
					'IBLOCK_ID' => $context['OFFER_IBLOCK_ID'],
					'ID' => $changes[Glossary::ENTITY_OFFER]
				],
				false,
				false,
				[
					'IBLOCK_ID',
					'ID',
					'PROPERTY_' . $context['OFFER_PROPERTY_ID'],
				]
			);

			while ($offer = $queryOffers->Fetch())
			{
				$offerId = (int)$offer['ID'];
				$parentId = (int)$offer['PROPERTY_' . $context['OFFER_PROPERTY_ID'] . '_VALUE'];

				if ($parentId > 0 && !isset($idsMap[$parentId]))
				{
					$idsMap[$parentId] = true;
				}

				if (isset($idsMap[$offerId]))
				{
					unset($idsMap[$offerId]);
				}
			}

			$ids = array_keys($idsMap);
		}

		if (empty($ids)) { return null; }

		return [
			'ELEMENT' => [ 'ID' => $ids ],
		];
	}

	/** @noinspection DuplicatedCode */
	public function loadElements(callable $next, State $state, Data\Run\Offset $offset)
	{
		do
		{
			$elementFetcher = new Export\Routine\QueryBuilder\ElementFetcher();

			if (!$this->firstFilter)
			{
				$readyFilter = [
					'=BOOST_ID' => $state->boost->getId(),
					'>=TIMESTAMP_X' => $state->initTime,
				];

				$elementFetcher->exclude(SalesBoost\Run\Storage\CollectorTable::class, $readyFilter, 'ELEMENT_ID');
			}

			$queryResult = $elementFetcher->load($state->queryFilter, $state->querySelect, $state->context, $offset->get('element'));

			$sourceFetcher = new Export\Routine\QueryBuilder\SourceFetcher();
			$sourceValues = $sourceFetcher->load($state->sourceSelect, $queryResult['ELEMENT'], $queryResult['PARENT'], $state->context);

			$elementValues = $this->extractValues($state->sourceMap, $sourceValues);

			$elementsState = clone $state;
			$elementsState->elements = $queryResult['ELEMENT'];
			$elementsState->elementsValues = $elementValues;

			$next($elementsState);

			$offset->set('element', $queryResult['OFFSET']);

			if (!$queryResult['HAS_NEXT']) { break; }

			if ($this->processor->isExpired())
			{
				$offset->interrupt();
				break;
			}
		}
		while (true);
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
}