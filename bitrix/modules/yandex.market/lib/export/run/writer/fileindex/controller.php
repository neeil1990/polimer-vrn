<?php

namespace Yandex\Market\Export\Run\Writer\FileIndex;

use Bitrix\Main;
use Yandex\Market\Result;

class Controller
{
	protected $setupId;

	public function __construct($setupId)
	{
		$this->setupId = (int)$setupId;
	}

	public function clear()
	{
		$saveResult = PositionTable::deleteBatch([
			'filter' => [ '=SETUP_ID' => $this->setupId ],
		]);

		Result\Facade::handleException($saveResult);
	}

	public function commit($size)
	{
		$saveResult = RegistryTable::updateBatch([
			'filter' => [ '=SETUP_ID' => $this->setupId ],
		], [
			'FILE_SIZE' => $size,
		]);

		Result\Facade::handleException($saveResult);
	}

	public function test($size)
	{
		$result = false;

		$query = RegistryTable::getList([
			'select' => [ 'FILE_SIZE' ],
			'filter' => [ '=SETUP_ID' => $this->setupId ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$stored = (int)$row['FILE_SIZE'];

			$result = ($stored === 0 || $stored === (int)$size);
		}

		return $result;
	}

	public function insert(array $rows)
	{
		if (empty($rows)) { return; }

		$rows = array_map(
			function(array $row) { return $row + [ 'SETUP_ID' => $this->setupId ]; },
			$rows
		);

		$saveResult = PositionTable::addBatch($rows);

		Result\Facade::handleException($saveResult);
	}

	public function update($name, array $sizes)
	{
		foreach (array_chunk($sizes, 500, true) as $sizesChunk)
		{
			$rows = [];
			$positionPlaceholder = -1;

			foreach ($sizesChunk as $primary => $size)
			{
				$rows[] = [
					'SETUP_ID' => $this->setupId,
					'NAME' => $name,
					'PRIMARY' => $primary,
					'POSITION' => $positionPlaceholder,
					'SIZE' => $size,
				];
			}

			$saveResult = PositionTable::addBatch($rows, [ 'SIZE' ]);

			Result\Facade::handleException($saveResult);
		}
	}

	public function order($name, $primary)
	{
		$query = PositionTable::getList([
			'filter' => [
				'=SETUP_ID' => $this->setupId,
				'=NAME' => $name,
				'=PRIMARY' => $primary,
			],
			'select' => [ 'POSITION' ],
		]);

		$row = $query->fetchRaw();

		if (!$row)
		{
			throw new Main\SystemException(sprintf(
				'cant find tag order %s %s',
				$name,
				$primary
			));
		}

		return $row['POSITION'];
	}

	public function search($name, $primaries)
	{
		$result = [];

		foreach (array_chunk($primaries, 500, true) as $primariesChunk)
		{
			$primaryMap = array_flip($primariesChunk);
			$foundPosition = 0;
			$foundOffset = 0;

			$queryRows = PositionTable::getList([
				'filter' => [
					'=SETUP_ID' => $this->setupId,
					'=NAME' => $name,
					'=PRIMARY' => array_values($primariesChunk),
				],
				'select' => [ 'PRIMARY', 'POSITION' ],
				'order' => [ 'POSITION' => 'ASC' ],
			]);

			while ($row = $queryRows->fetchRaw())
			{
				$resultKey = $primaryMap[$row['PRIMARY']];

				$queryPosition = PositionTable::getList([
					'filter' => [
						'=SETUP_ID' => $this->setupId,
						'>=POSITION' => $foundPosition,
						'<POSITION' => $row['POSITION'],
					],
					'select' => [ 'OFFSET' ],
					'runtime' => [
						new Main\Entity\ExpressionField('OFFSET', 'SUM(%s)', 'SIZE'),
					],
				]);

				if ($positionRow = $queryPosition->fetch())
				{
					$foundPosition = $row['POSITION'];
					$foundOffset += (int)$positionRow['OFFSET'];
				}

				$result[$resultKey] = $foundOffset;
			}
		}

		return $result;
	}

	public function remove($name, $primaries)
	{
		if (empty($primaries)) { return; }

		foreach (array_chunk($primaries, 500) as $primaryChunk)
		{
			$saveResult = PositionTable::deleteBatch([
				'filter' => [
					'=SETUP_ID' => $this->setupId,
					'=NAME' => $name,
					'=PRIMARY' => $primaryChunk,
				],
			]);

			Result\Facade::handleException($saveResult);
		}
	}

	public function adjust($from, $diff)
	{
		$diff = (int)$diff;

		if ($diff === 0) { return; }

		$saveResult = PositionTable::updateBatch([
			'filter' => [
				'=SETUP_ID' => $this->setupId,
				'>=POSITION' => $from,
			],
		], [
			'POSITION' => new Main\DB\SqlExpression('?# ' . ($diff > 0 ? '+ ' . $diff : $diff), 'POSITION'),
		]);

		Result\Facade::handleException($saveResult);
	}
}