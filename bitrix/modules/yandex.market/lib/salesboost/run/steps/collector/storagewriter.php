<?php
namespace Yandex\Market\SalesBoost\Run\Steps\Collector;

use Yandex\Market\Data;
use Yandex\Market\Reference\Assert;
use Yandex\Market\SalesBoost;
use Yandex\Market\Trading;
use Yandex\Market\Utils\ArrayHelper;

class StorageWriter
{
	protected $processor;

	public function __construct(SalesBoost\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function __invoke(callable $next, State $state)
	{
		foreach (array_chunk($state->elements, 500, true) as $elementsChunk)
		{
			$rows = $this->compileRows($elementsChunk, $state);
			$rows = $this->extendFeedExists($rows, $state);
			$rows = $this->extendSku($rows, $state);
			$active = $this->storedActive($rows, $state);
			list($rows, $needDeactivate) = $this->resolveConflict($rows, $active);

			$this->insert($rows);
			$this->deactivate($needDeactivate);
		}

		$next($state);
	}

	protected function compileRows(array $elements, State $state)
	{
		$result = [];
		$timestamp = new Data\Type\CanonicalDateTime();

		foreach ($elements as $elementId => $element)
		{
			$bid = (
				isset($state->elementsValues[$elementId]['BID'])
				&& is_scalar($state->elementsValues[$elementId]['BID'])
				&& trim($state->elementsValues[$elementId]['BID']) !== ''
					? Data\Number::normalize($state->elementsValues[$elementId]['BID'])
					: $state->boost->getBidDefault()
			);

			if ($state->boost->getBidFormat() === SalesBoost\Setup\Table::BID_FORMAT_PERCENT)
			{
				$bid *= 100;
			}

			if ($this->isValidBid($bid))
			{
				$status = SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE;
			}
			else
			{
				$bid = 0;
				$status = SalesBoost\Run\Storage\CollectorTable::STATUS_FAIL;
			}

			$result[$elementId] = [
				'BOOST_ID' => (int)$state->boost->getId(),
				'ELEMENT_ID' => $elementId,
				'PARENT_ID' => isset($element['PARENT_ID']) ? (int)$element['PARENT_ID'] : 0,
				'BUSINESS_ID' => $state->context['BUSINESS_ID'],
				'SKU' => null,
				'SORT' => (int)$state->boost->getSort(),
				'BID' => (int)$bid,
				'STATUS' => $status,
				'TIMESTAMP_X' => $timestamp,
			];
		}

		return $result;
	}

	protected function isValidBid($bid)
	{
		return ($bid >= 50 && $bid <= 9999);
	}

	protected function extendFeedExists(array $rows, State $state)
	{
		$trading = $state->boost->getTrading();
		$command = $trading->wakeupService()->getContainer()->get(Trading\Service\Marketplace\Command\FeedExists::class, [
			'environment' => $trading->getEnvironment(),
		]);
		$existsMap = array_flip($command->filterProducts(array_keys($rows)));

		foreach ($rows as &$row)
		{
			if (!isset($existsMap[$row['ELEMENT_ID']]))
			{
				$row['STATUS'] = SalesBoost\Run\Storage\CollectorTable::STATUS_FAIL;
			}
		}
		unset($row);

		return $rows;
	}

	protected function extendSku(array $rows, State $state)
	{
		$skuMap = $this->skuMap(array_keys($rows), $state);
		$used = [];

		foreach ($rows as $elementId => &$row)
		{
			$sku = null;

			if ($skuMap === null)
			{
				$sku = $elementId;
			}
			else if (isset($skuMap[$elementId]))
			{
				$sku = $skuMap[$elementId];
			}

			if ($sku === null || isset($used[$sku]))
			{
				$row['STATUS'] = SalesBoost\Run\Storage\CollectorTable::STATUS_FAIL;
				continue;
			}

			$row['SKU'] = $sku;
			$used[$sku] = true;
		}

		return $rows;
	}

	protected function skuMap(array $elementIds, State $state)
	{
		/** @var Trading\Service\Marketplace\Options $options */
		$trading = $state->boost->getTrading();
		$environment = $trading->getEnvironment();
		$options = $trading->wakeupService()->getOptions();

		Assert::typeOf($options, Trading\Service\Marketplace\Options::class, 'options');

		$optionsMap = $options->getProductSkuMap();

		if (empty($optionsMap)) { return null; }

		return $environment->getProduct()->getSkuMap($elementIds, $optionsMap);
	}

	protected function storedActive(array $rows, State $state)
	{
		$skus = array_column($rows, 'SKU');

		if (empty($skus)) { return []; }

		$result = [];

		$query = SalesBoost\Run\Storage\CollectorTable::getList([
			'filter' => [
				'=BUSINESS_ID' => $state->context['BUSINESS_ID'],
				'=SKU' => $skus,
				'=STATUS' => SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE,
			],
			'select' => [ 'BOOST_ID', 'ELEMENT_ID', 'SORT', 'SKU' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['SKU']] = [
				'BOOST_ID' => (int)$row['BOOST_ID'],
				'ELEMENT_ID' => $row['ELEMENT_ID'],
				'SORT' => (int)$row['SORT'],
			];
		}

		return $result;
	}

	protected function resolveConflict(array $rows, array $active)
	{
		$needDeactivate = [];

		foreach ($rows as &$newRow)
		{
			if (!isset($newRow['SKU'], $active[$newRow['SKU']])) { continue; }

			$sku = $newRow['SKU'];
			$stored = $active[$sku];

			if ($stored['BOOST_ID'] === $newRow['BOOST_ID']) { continue; }

			$isNewActive = ($newRow['STATUS'] === SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE);
			$isNewMorePriority = (
				$newRow['SORT'] < $stored['SORT']
				|| ($newRow['SORT'] === $stored['SORT'] && $newRow['BOOST_ID'] > $stored['BOOST_ID'])
			);

			if ($isNewMorePriority)
			{
				if (!$isNewActive) { continue; }

				$needDeactivate[$sku] = $stored;
			}
			else if ($isNewActive)
			{
				$newRow['STATUS'] = SalesBoost\Run\Storage\CollectorTable::STATUS_INACTIVE;
			}
		}
		unset($newRow);

		return [$rows, $needDeactivate];
	}

	protected function deactivate(array $active)
	{
		$grouped = ArrayHelper::groupBy($active, 'BOOST_ID');
		$filter = $this->makeGroupFilter($grouped);

		if ($filter === null) { return; }

		SalesBoost\Run\Storage\CollectorTable::updateBatch([
			'filter' => $filter,
		], [
			'STATUS' => SalesBoost\Run\Storage\CollectorTable::STATUS_INACTIVE,
			'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
		]);
	}

	protected function makeGroupFilter(array $grouped)
	{
		if (empty($grouped)) { return null; }

		$filter = [];

		if (count($grouped) > 1) { $filter['LOGIC'] = 'OR'; }

		foreach ($grouped as $boostId => $rows)
		{
			$filter[] = [
				'=BOOST_ID' => $boostId,
				'=ELEMENT_ID' => array_column($rows, 'ELEMENT_ID'),
			];
		}

		return $filter;
	}

	protected function insert(array $rows)
	{
		if (empty($rows)) { return; }

		SalesBoost\Run\Storage\CollectorTable::addBatch($rows, true);
	}
}