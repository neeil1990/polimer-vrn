<?php
namespace Yandex\Market\SalesBoost\Run\Steps;

use Yandex\Market\Data;
use Yandex\Market\Glossary;
use Yandex\Market\Result;
use Yandex\Market\SalesBoost;
use Yandex\Market\Trading;

class Collector extends Data\Run\StepSkeleton
{
	protected $processor;

	public function __construct(SalesBoost\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function getName()
	{
		return 'collector';
	}

	public function validateAction($action)
	{
		if ($action === Data\Run\Processor::ACTION_CHANGE)
		{
			return (
				$this->processor->parameter('initTimeUTC') instanceof Data\Type\CanonicalDateTime
				&& $this->changesFilter() !== null
			);
		}

		if ($action === Data\Run\Processor::ACTION_REFRESH)
		{
			return ($this->processor->parameter('initTimeUTC') instanceof Data\Type\CanonicalDateTime);
		}

		return true;
	}

	public function run($action, $offset = null)
	{
		$result = new Result\Step();
		$state = $this->createState($action);
		$offsetObject = new Data\Run\Offset($offset, [
			'boost',
			'product',
			'filter',
			'query',
			'element',
		]);

		(new Data\Run\Waterfall())
			->add(new Collector\ElementsLoader($this->processor))
			->add(new Collector\StorageWriter($this->processor))
			->run($state, $offsetObject);

		if ($offsetObject->interrupted())
		{
			$result->setOffset((string)$offsetObject);
			$result->setTotal(1);

			if ($this->processor->parameter('progressCount') === true)
			{
				$result->setReadyCount($this->readyCount());
			}
		}

		return $result;
	}

	protected function readyCount()
	{
		return SalesBoost\Run\Storage\CollectorTable::getCount([
			'>=TIMESTAMP_X' => $this->processor->parameter('initTimeUTC'),
		]);
	}

	public function after($action)
	{
		if ($action === Data\Run\Processor::ACTION_CHANGE)
		{
			$this->markDeleted($this->changesFilter());
		}
		else
		{
			$this->markDeleted();
		}
	}

	public function finalize($action)
	{
		if ($action === Data\Run\Processor::ACTION_CHANGE)
		{
			$this->clearDeleted($this->changesFilter());
		}
		else
		{
			$this->clearDeleted();
		}
	}

	protected function createState($action)
	{
		$state = new Collector\State();
		$state->runAction = $action;
		$state->initTime = $this->processor->parameter('initTimeUTC');
		$state->selectedBoosts = $this->processor->parameter('boosts');

		if ($action === Data\Run\Processor::ACTION_CHANGE)
		{
			$state->changes = $this->processor->parameter('changes');
		}

		return $state;
	}

	protected function changesFilter()
	{
		$changes = $this->processor->parameter('changes');
		$partials = [];

		if (!empty($changes[Glossary::SERVICE_SALES_BOOST]))
		{
			$partials[] = [
				'=BOOST_ID' => $changes[Glossary::SERVICE_SALES_BOOST],
			];
		}

		if (!empty($changes[Glossary::ENTITY_OFFER]))
		{
			$partials[] = [
				'LOGIC' => 'OR',
				[ '=ELEMENT_ID' => $changes[Glossary::ENTITY_OFFER] ],
				[ '=PARENT_ID' => array_filter((array)$changes[Glossary::ENTITY_OFFER]) ], // remove null
			];
		}

		if (empty($partials)) { return null; }

		if (count($partials) > 1)
		{
			return [ 'LOGIC' => 'OR' ] + $partials;
		}

		return reset($partials);
	}

	protected function markDeleted(array $filter = [])
	{
		$selectedBoosts = $this->boostsForClean();
		$queryFilter = [];

		if ($selectedBoosts !== null)
		{
			$queryFilter['=BOOST_ID'] = $selectedBoosts;
		}

		$queryFilter[] = $filter;
		$queryFilter['<TIMESTAMP_X'] = $this->processor->parameter('initTimeUTC');

		SalesBoost\Run\Storage\CollectorTable::updateBatch([
			'filter' => $queryFilter,
		], [
			'STATUS' => SalesBoost\Run\Storage\CollectorTable::STATUS_DELETE,
			'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
		]);
	}

	protected function clearDeleted(array $filter = [])
	{
		$selectedBoosts = $this->boostsForClean();
		$queryFilter = [];

		if ($selectedBoosts !== null)
		{
			$queryFilter['=BOOST_ID'] = $selectedBoosts;
		}

		$queryFilter[] = $filter;
		$queryFilter['=STATUS'] = SalesBoost\Run\Storage\CollectorTable::STATUS_DELETE;
		$queryFilter['SUBMITTER.ELEMENT_ID'] = false; // submitter task completed and deleted

		SalesBoost\Run\Storage\CollectorTable::deleteBatch([
			'filter' => $queryFilter,
		]);
	}

	protected function boostsForClean()
	{
		$partials = [];
		$keys = [ 'boosts', 'inactiveBoosts' ];

		foreach ($keys as $key)
		{
			$selected = $this->processor->parameter($key);

			if ($selected !== null)
			{
				$partials[] = (array)$selected;
			}
		}

		return !empty($partials) ? array_merge(...$partials) : null;
	}
}

