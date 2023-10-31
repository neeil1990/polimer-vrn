<?php
namespace Yandex\Market\Watcher\Track;

use Yandex\Market\Data\Type;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Utils\ArrayHelper;

class StampState
{
	use Concerns\HasOnce;

	/** @var string */
	private $service;
	/** @var int */
	private $setupId;
	/** @var array|null */
	private $known;

	public function __construct($service, $setupId)
	{
		$this->service = $service;
		$this->setupId = $setupId;
	}

	public function service()
	{
		return $this->service;
	}

	public function setupId()
	{
		return $this->setupId;
	}

	public function offset()
	{
		$state = $this->state();

		return $state !== null ? (int)$state['OFFSET'] : 0;
	}

	public function until()
	{
		$state = $this->state();

		return $state !== null ? (int)$state['UNTIL'] : 0;
	}

	public function timestampX()
	{
		$state = $this->state();

		return $state !== null ? $state['TIMESTAMP_X'] : null;
	}

	public function state()
	{
		if ($this->known !== null) { return $this->known; }

		return $this->once('state', function() {
			$result = null;

			$query = StampTable::getList([
				'filter' => [
					'=SERVICE' => $this->service,
					'=SETUP_ID' => $this->setupId,
				],
				'select' => [ 'OFFSET', 'UNTIL', 'TIMESTAMP_X' ],
			]);

			if ($row = $query->fetch())
			{
				$result = [
					'OFFSET' => (int)$row['OFFSET'],
					'UNTIL' => (int)$row['UNTIL'],
					'TIMESTAMP_X' => $row['TIMESTAMP_X'],
				];
			}

			return $result;
		});
	}

	public function start()
	{
		if ($this->state() !== null) { return; }

		$this->write([
			'OFFSET' => 0,
			'UNTIL' => 0,
		]);
	}

	public function interrupt($until)
	{
		$this->write([
			'OFFSET' => $this->offset(),
			'UNTIL' => (int)$until,
		]);
	}

	public function drop()
	{
		StampTable::deleteBatch([
			'filter' => [
				'=SERVICE' => $this->service,
				'=SETUP_ID' => $this->setupId,
			],
		]);
	}

	public function shift($offset = null)
	{
		if ($offset === null)
		{
			$offset = $this->maxOffset();

			if ($this->offset() >= $offset) { return; }
		}

		$this->write([
			'OFFSET' => (int)$offset,
			'UNTIL' => 0,
		]);
	}

	private function maxOffset()
	{
		$result = 0;

		$query = ChangesTable::getList([
			'select' => [ 'ID' ],
			'order' => [ 'ID' => 'desc' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	private function write($values)
	{
		$primary = [
			'SERVICE' => $this->service,
			'SETUP_ID' => $this->setupId,
		];
		$values += [
			'TIMESTAMP_X' => new Type\CanonicalDateTime(),
		];

		if ($this->known !== null)
		{
			StampTable::updateBatch(
				[ 'filter' => ArrayHelper::prefixKeys($primary, '=') ],
				$values
			);
		}
		else
		{
			StampTable::addBatch([$primary + $values], true);
		}

		$this->known = $values;
	}
}