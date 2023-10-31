<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;
use Yandex\Market\Config;

class PushStore
{
	protected $setupId;
	protected $entityType;
	protected $primaryRule;
	protected $signRule;

	public function __construct($setupId, $entityType, $primaryRule, $signRule)
	{
		$this->setupId = $setupId;
		$this->entityType = $entityType;
		$this->primaryRule = $primaryRule;
		$this->signRule = $signRule;
	}

	public function filterExists($dataList, array $filter = [])
	{
		$values = $this->collectValues($dataList);

		if (empty($values)) { return []; }

		$exists = $this->fetchExists(array_keys($values), $filter);

		return $this->onlyFound($dataList, $exists);
	}

	public function filterChanged($dataList)
	{
		list($changed) = $this->splitChanged($dataList);

		return $changed;
	}

	public function splitChanged($dataList)
	{
		$values = $this->collectValues($dataList);

		if (empty($values)) { return [ [], $dataList ]; }

		$exists = $this->fetchExists(array_keys($values), $this->expireFilter());
		$changed = array_diff_assoc($values, $exists);

		return $this->splitFound($dataList, $changed);
	}

	protected function expireFilter()
	{
		$days = max(1, (int)Config::getOption(PushAgent::optionName('expire_days'), 7));

		$expire = new Main\Type\DateTime();
		$expire->add(sprintf('-P%sD', $days));

		return [
			'>=TIMESTAMP_X' => $expire,
		];
	}

	public function untouched(Main\Type\DateTime $after, array $filter = [])
	{
		$result = [];

		$query = Internals\PushTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->setupId,
				'=ENTITY_TYPE' => $this->entityType,
				'<CHECK_STAMP' => $after,
			] + $filter,
			'select' => [ 'ENTITY_ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = $this->unpackPrimary($row['ENTITY_ID']);
		}

		return $result;
	}

	public function touch($dataList)
	{
		$values = $this->collectValues($dataList);

		if (empty($values)) { return; }

		Internals\PushTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->setupId,
				'=ENTITY_TYPE' => $this->entityType,
				'=ENTITY_ID' => array_keys($values),
			],
		], [
			'CHECK_STAMP' => new Main\Type\DateTime(),
		]);
	}

	public function release($dataList)
	{
		$primaries = $this->collectPrimaries($dataList);

		if (empty($primaries)) { return; }

		Internals\PushTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $this->setupId,
				'=ENTITY_TYPE' => $this->entityType,
				'=ENTITY_ID' => $primaries,
			],
		]);
	}

	public function commit($dataList)
	{
		$values = $this->collectValues($dataList);

		if (empty($values)) { return; }

		$rows = $this->valuesToRows($values);

		Internals\PushTable::addBatch($rows, [
			'VALUE',
			'TIMESTAMP_X',
			'CHECK_STAMP',
		]);
	}

	public function reset()
	{
		Internals\PushTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $this->setupId,
				'=ENTITY_TYPE' => $this->entityType,
			],
		]);
	}

	protected function collectValues($dataList)
	{
		$result = [];

		foreach ($dataList as $data)
		{
			$primary = $this->getPrimary($data);

			if ($primary === null) { continue; }

			$sign = $this->getSign($data);

			if ($sign === '') { continue;}

			$result[$primary] = $sign;
		}

		return $result;
	}

	protected function collectPrimaries($dataList)
	{
		$result = [];

		foreach ($dataList as $data)
		{
			$primary = $this->getPrimary($data);

			if ($primary === null) { continue; }

			$result[] = $primary;
		}

		return $result;
	}

	protected function valuesToRows($values)
	{
		$timestamp = new Main\Type\DateTime();
		$result = [];

		foreach ($values as $primary => $sign)
		{
			$result[] = [
				'SETUP_ID' => $this->setupId,
				'ENTITY_TYPE' => $this->entityType,
				'ENTITY_ID' => $primary,
				'VALUE' => $sign,
				'TIMESTAMP_X' => $timestamp,
				'CHECK_STAMP' => $timestamp,
			];
		}

		return $result;
	}

	protected function fetchExists(array $primaries, array $filter = [])
	{
		$result = [];

		foreach (array_chunk($primaries, 500) as $primariesChunk)
		{
			$query = Internals\PushTable::getList([
				'filter' => [
					'=SETUP_ID' => $this->setupId,
					'=ENTITY_TYPE' => $this->entityType,
					'=ENTITY_ID' => $primariesChunk,
				] + $filter,
				'select' => [
					'ENTITY_ID',
					'VALUE',
				],
			]);

			while ($row = $query->fetch())
			{
				$result[$row['ENTITY_ID']] = (string)$row['VALUE'];
			}
		}

		return $result;
	}

	protected function onlyFound($dataList, $map)
	{
		list($found) = $this->splitFound($dataList, $map);

		return $found;
	}

	protected function splitFound($dataList, $map)
	{
		$found = [];
		$notFound = [];

		foreach ($dataList as $data)
		{
			$primary = $this->getPrimary($data);

			if ($primary === null || !isset($map[$primary]))
			{
				$notFound[] = $data;
			}
			else
			{
				$found[] = $data;
			}
		}

		return [ $found, $notFound ];
	}

	protected function getPrimary($data)
	{
		return $this->stringifyRule($data, $this->primaryRule);
	}

	protected function unpackPrimary($value)
	{
		return $this->unpackRule($value, $this->primaryRule);
	}

	protected function getSign($data)
	{
		return $this->stringifyRule($data, $this->signRule);
	}

	protected function stringifyRule($data, $rule)
	{
		if (is_callable($rule))
		{
			$result = (string)$rule($data);
		}
		else if (is_array($rule))
		{
			$values = [];

			foreach ($rule as $key)
			{
				$values[] = isset($data[$key]) ? (string)$data[$key] : '';
			}

			$result = implode(':', $values);
		}
		else
		{
			$result = isset($data[$rule]) ? (string)$data[$rule] : '';
		}

		return $result;
	}

	protected function unpackRule($value, $rule)
	{
		if (is_callable($rule))
		{
			throw new Main\NotSupportedException();
		}

		if (is_array($rule))
		{
			$partials = explode(':', $value);
			$result = [];

			foreach ($partials as $index => $partial)
			{
				$key = $rule[$index];

				$result[$key] = $partial;
			}
		}
		else
		{
			$result = $value;
		}

		return $result;
	}
}