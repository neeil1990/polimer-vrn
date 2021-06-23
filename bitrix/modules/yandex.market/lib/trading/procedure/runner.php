<?php

namespace Yandex\Market\Trading\Procedure;

use Yandex\Market;
use Bitrix\Main;

class Runner
{
	/** @var string */
	protected $entityType;
	/** @var string */
	protected $entityId;
	/** @var string|null*/
	protected $auditType;
	/** @var array|null*/
	protected $lastRun;
	/** @var Market\Psr\Log\LoggerInterface*/
	protected $logger;

	public function __construct($entityType, $entityId)
	{
		$this->entityType = $entityType;
		$this->entityId = $entityId;
	}

	public function run(Market\Trading\Setup\Model $setup, $path, $data)
	{
		$this->saveLastRun($setup, $path, $data);

		$service = $setup->getService();
		$environment = $setup->getEnvironment();
		$router = $service->getRouter();

		$this->setLogger($service->getLogger());

		if (!$setup->isActive())
		{
			throw new Main\SystemException('trading platform is inactive');
		}

		$action = $router->getDataAction($path, $environment, $data);

		$this->setAuditType($action->getAudit());

		$setup->wakeupService();
		$action->process();

		return $action->getResponse();
	}

	public function clearRepeat()
	{
		if ($this->lastRun === null)
		{
			throw new Main\ObjectNotFoundException();
		}

		QueueTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $this->lastRun['SETUP_ID'],
				'=PATH' => $this->lastRun['PATH'],
				'=ENTITY_TYPE' => $this->entityType,
				'=ENTITY_ID' => $this->entityId,
			],
		]);
	}

	public function createRepeat($interval = 600)
	{
		if ($this->lastRun === null)
		{
			throw new Main\ObjectNotFoundException();
		}

		QueueTable::add($this->lastRun + [
			'INTERVAL' => $interval,
			'ENTITY_TYPE' => $this->entityType,
			'ENTITY_ID' => $this->entityId,
			'EXEC_DATE' => new Main\Type\DateTime(),
			'EXEC_COUNT' => 0
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

	protected function setAuditType($auditType)
	{
		$this->auditType = $auditType;
	}

	protected function getAuditType()
	{
		return $this->auditType ?: Market\Logger\Trading\Audit::PROCEDURE;
	}

	protected function setLogger(Market\Psr\Log\LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	public function logException(\Exception $exception)
	{
		if ($this->logger !== null)
		{
			$this->logger->error($exception, [
				'AUDIT' => $this->getAuditType(),
				'ENTITY_TYPE' => $this->entityType,
				'ENTITY_ID' => $this->entityId,
			]);
		}
	}

	protected function saveLastRun(Market\Trading\Setup\Model $setup, $path, $data)
	{
		$this->lastRun = [
			'SETUP_ID' => $setup->getId(),
			'PATH' => $path,
			'DATA' => $data,
		];
	}
}