<?php

namespace Yandex\Market\Trading\Procedure;

use Bitrix\Main;

class Task
{
	/** @var string */
	protected $entityType;
	/** @var string */
	protected $entityId;

	public function __construct($entityType, $entityId)
	{
		$this->entityType = $entityType;
		$this->entityId = $entityId;
	}

	public function clear($setupId, $path)
	{
		QueueTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $setupId,
				'=PATH' => $path,
				'=ENTITY_TYPE' => $this->entityType,
				'=ENTITY_ID' => $this->entityId,
			],
		]);
	}

	public function schedule($setupId, $path, $data, $interval = 600)
	{
		QueueTable::add([
			'SETUP_ID' => $setupId,
			'PATH' => $path,
			'DATA' => $data,
			'INTERVAL' => $interval,
			'ENTITY_TYPE' => $this->entityType,
			'ENTITY_ID' => $this->entityId,
			'EXEC_DATE' => new Main\Type\DateTime(),
			'EXEC_COUNT' => 0,
		]);

		$this->registerRepeatAgent();
	}

	protected function registerRepeatAgent()
	{
		Agent::register([
			'method' => 'repeat',
			'next_exec' => new Main\Type\DateTime(),
		]);
	}
}