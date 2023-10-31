<?php
namespace Yandex\Market\SalesBoost\Run\Steps;

use Yandex\Market\Data;
use Yandex\Market\Result;
use Yandex\Market\SalesBoost;
use Yandex\Market\Utils\ArrayHelper;

class Planner extends Data\Run\StepSkeleton
{
	protected $processor;

	public function __construct(SalesBoost\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function getName()
	{
		return 'planner';
	}

	/** @noinspection SlowArrayOperationsInLoopInspection */
	public function run($action, $offset = null)
	{
		$result = new Result\Step();
		$offset = (int)$offset;

		do
		{
			$limit = 500;
			$collected = $this->collected($limit, $offset);

			if (empty($collected)) { break; }

			$submitted = $this->submitted($collected);
			list($compiled, $deactivated) = $this->compile($collected, $submitted, $action);
			$compiled = array_merge($compiled, $this->replaces($deactivated));
			$compiled = $this->conflict($compiled);

			$this->schedule($compiled);

			$offset += $limit;

			if (count($collected) < $limit) { break; }

			if ($this->processor->isExpired())
			{
				$result->setOffset($offset);
				$result->setTotal(1);
			}
		}
		while (true);

		return $result;
	}

	protected function collected($limit, $offset = 0)
	{
		$result = [];

		$query = SalesBoost\Run\Storage\CollectorTable::getList([
			'select' => [
				'BOOST_ID',
				'ELEMENT_ID',
				'BUSINESS_ID',
				'SKU',
				'STATUS',
				'BID',
			],
			'filter' => [ '>=TIMESTAMP_X' => $this->processor->parameter('initTimeUTC') ],
			'limit' => $limit,
			'offset' => (int)$offset,
		]);

		while ($row = $query->fetch())
		{
			$result[$this->signCollected($row)] = $row;
		}

		return $result;
	}

	protected function signCollected(array $row)
	{
		return $row['BOOST_ID'] . ':' . $row['ELEMENT_ID'];
	}

	protected function submitted(array $collected)
	{
		$filter = $this->makeGroupFilter($collected, 'BOOST_ID', 'ELEMENT_ID');

		if ($filter === null) { return []; }

		$result = [];

		$query = SalesBoost\Run\Storage\SubmitterTable::getList([
			'filter' => $filter,
		]);

		while ($row = $query->fetch())
		{
			$result[$this->signCollected($row)] = $row;
		}

		return $result;
	}

	protected function compile(array $collected, array $submitted, $action)
	{
		$insert = [];
		$deactivated = [];
		$now = new Data\Type\CanonicalDateTime();
		$activeStatuses = [
			SalesBoost\Run\Storage\SubmitterTable::STATUS_READY => true,
			SalesBoost\Run\Storage\SubmitterTable::STATUS_ACTIVE => true,
		];

		foreach ($collected as $sign => $collectedRow)
		{
			$submittedRow = isset($submitted[$sign]) ? $submitted[$sign] : null;
			$submittedStatus = $submittedRow !== null ? $submittedRow['STATUS'] : null;
			$isSubmitActive = isset($activeStatuses[$submittedStatus]);

			if ($collectedRow['STATUS'] === SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE)
			{
				if ($isSubmitActive && (string)$submittedRow['SKU'] !== (string)$collectedRow['SKU'])
				{
					$insert[] = [
						'BUSINESS_ID' => $submittedRow['BUSINESS_ID'],
						'SKU' => $submittedRow['SKU'],
						'BOOST_ID' => $collectedRow['BOOST_ID'],
						'ELEMENT_ID' => $collectedRow['ELEMENT_ID'],
						'STATUS' => SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE,
						'BID' => 0,
						'TIMESTAMP_X' => $now,
					];
				}
				else if (
					$submittedStatus === SalesBoost\Run\Storage\SubmitterTable::STATUS_ACTIVE
					&& (int)$submittedRow['BID'] === (int)$collectedRow['BID']
					&& $action !== SalesBoost\Run\Processor::ACTION_FULL
				)
				{
					continue;
				}

				$insert[] = [
					'BUSINESS_ID' => $collectedRow['BUSINESS_ID'],
					'SKU' => $collectedRow['SKU'],
					'BOOST_ID' => $collectedRow['BOOST_ID'],
					'ELEMENT_ID' => $collectedRow['ELEMENT_ID'],
					'STATUS' => SalesBoost\Run\Storage\SubmitterTable::STATUS_READY,
					'BID' => $collectedRow['BID'],
					'TIMESTAMP_X' => $now,
				];
			}
			else if ($isSubmitActive)
			{
				$deactivated[] = [
					'BUSINESS_ID' => $submittedRow['BUSINESS_ID'],
					'SKU' => $submittedRow['SKU'],
				];

				$insert[] = [
					'BUSINESS_ID' => $submittedRow['BUSINESS_ID'],
					'SKU' => $submittedRow['SKU'],
					'BOOST_ID' => $collectedRow['BOOST_ID'],
					'ELEMENT_ID' => $collectedRow['ELEMENT_ID'],
					'STATUS' => SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE,
					'BID' => 0,
					'TIMESTAMP_X' => $now,
				];
			}
			else if ($submittedStatus === SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE)
			{
				$insert[] = [
					'BUSINESS_ID' => $submittedRow['BUSINESS_ID'],
					'SKU' => $submittedRow['SKU'],
					'BOOST_ID' => $collectedRow['BOOST_ID'],
					'ELEMENT_ID' => $collectedRow['ELEMENT_ID'],
					'STATUS' => SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE,
					'BID' => 0,
					'TIMESTAMP_X' => $now,
				];
			}
		}

		return [$insert, $deactivated];
	}

	protected function replaces(array $stored)
	{
		$replaced = $this->replacesWaiting($stored);

		$this->saveReplaces($replaced);

		return $this->compileReplaces($replaced);
	}

	protected function replacesWaiting(array $stored)
	{
		$filter = $this->makeGroupFilter($stored, 'BUSINESS_ID', 'SKU');

		if ($filter === null) { return []; }

		$found = [];
		$result = [];

		$query = SalesBoost\Run\Storage\CollectorTable::getList([
			'filter' => [
				$filter,
				'=STATUS' => [
					SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE,
					SalesBoost\Run\Storage\CollectorTable::STATUS_INACTIVE,
				],
			],
			'select' => [ 'BOOST_ID', 'ELEMENT_ID', 'BUSINESS_ID', 'SKU', 'BID', 'STATUS' ],
			'order' => [ 'SORT' => 'ASC', 'BOOST_ID' => 'desc' ],
		]);

		while ($row = $query->fetch())
		{
			$sign = $this->signSubmitted($row);

			if (isset($found[$sign])) { continue; }

			$found[$sign] = true;

			if ($row['STATUS'] !== SalesBoost\Run\Storage\CollectorTable::STATUS_INACTIVE) { continue; }

			$result[] = $row;
		}

		return $result;
	}

	protected function saveReplaces(array $replaced)
	{
		$filter = $this->makeGroupFilter($replaced, 'BOOST_ID', 'ELEMENT_ID');

		if ($filter === null) { return; }

		SalesBoost\Run\Storage\CollectorTable::updateBatch([
			'filter' => $filter,
		], [
			'STATUS' => SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE,
			'TIMESTAMP_X' => new Data\Type\CanonicalDateTime(),
		]);
	}

	protected function compileReplaces(array $replaced)
	{
		$now = new Data\Type\CanonicalDateTime();
		$result = [];

		foreach ($replaced as $row)
		{
			$result[] = [
				'BUSINESS_ID' => $row['BUSINESS_ID'],
				'SKU' => $row['SKU'],
				'BOOST_ID' => $row['BOOST_ID'],
				'ELEMENT_ID' => $row['ELEMENT_ID'],
				'STATUS' => SalesBoost\Run\Storage\SubmitterTable::STATUS_READY,
				'BID' => $row['BID'],
				'TIMESTAMP_X' => $now,
			];
		}

		return $result;
	}

	protected function conflict(array $compiled)
	{
		$compiled = $this->localConflict($compiled);
		$compiled = $this->storedConflict($compiled);

		return $compiled;
	}

	protected function localConflict(array $compiled)
	{
		$usedRows = [];
		$usedKeys = [];
		$result = $compiled;

		foreach ($compiled as $key => $row)
		{
			$sign = $this->signSubmitted($row);

			if (!isset($usedRows[$sign]))
			{
				$usedRows[$sign] = $row;
				$usedKeys[$sign] = $key;
				continue;
			}

			$usedRow = $usedRows[$sign];
			$usedKey = $usedKeys[$sign];

			if (
				$row['STATUS'] !== SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE
				&& $usedRow['STATUS'] === SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE
			)
			{
				$usedRows[$sign] = $row;
				$usedKeys[$sign] = $key;
				unset($result[$usedKey]);
			}
			else
			{
				unset($result[$key]);
			}
		}

		return $result;
	}

	protected function storedConflict(array $compiled)
	{
		$compiledForDelete = array_filter($compiled, static function(array $row) { return $row['STATUS'] === SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE; });
		$stored = $this->stored($compiledForDelete);
		$result = $compiled;

		foreach ($compiled as $key => $row)
		{
			if ($row['STATUS'] !== SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE) { continue; }

			$sign = $this->signSubmitted($row);

			if (!isset($stored[$sign])) { continue; }

			$storedRow = $stored[$sign];
			$isSame = (
				(int)$row['BOOST_ID'] === (int)$storedRow['BOOST_ID']
				&& (int)$row['ELEMENT_ID'] === (int)$storedRow['ELEMENT_ID']
			);

			if ($isSame) { continue; }

			if ($storedRow['STATUS'] !== SalesBoost\Run\Storage\SubmitterTable::STATUS_DELETE)
			{
				unset($result[$key]);
			}
		}

		return $result;
	}

	protected function stored(array $compiled)
	{
		$filter = $this->makeGroupFilter($compiled, 'BUSINESS_ID', 'SKU');

		if ($filter === null) { return []; }

		$result = [];

		$query = SalesBoost\Run\Storage\SubmitterTable::getList([
			'select' => [ 'BUSINESS_ID', 'SKU', 'BOOST_ID', 'ELEMENT_ID', 'STATUS', 'BID' ],
			'filter' => $filter,
		]);

		while ($row = $query->fetch())
		{
			$result[$this->signSubmitted($row)] = $row;
		}

		return $result;
	}

	protected function makeGroupFilter(array $rows, $groupBy, $elementField)
	{
		$grouped = ArrayHelper::groupBy($rows, $groupBy);

		if (empty($grouped)) { return null; }

		$filter = [];

		if (count($grouped) > 1) { $filter['LOGIC'] = 'OR'; }

		foreach ($grouped as $boostId => $group)
		{
			$filter[] = [
				'=' . $groupBy => $boostId,
				'=' . $elementField => array_column($group, $elementField),
			];
		}

		return $filter;
	}

	protected function signSubmitted(array $row)
	{
		return $row['BUSINESS_ID'] . ':' . $row['SKU'];
	}

	protected function schedule(array $compiled)
	{
		if (empty($compiled)) { return; }

		SalesBoost\Run\Storage\SubmitterTable::addBatch($compiled, true);
	}
}

