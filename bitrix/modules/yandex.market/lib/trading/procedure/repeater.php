<?php

namespace Yandex\Market\Trading\Procedure;

use Yandex\Market;
use Bitrix\Main;

class Repeater
{
	protected $startTime;
	protected $timeLimit;
	protected $setupCache = [];

	public function getNearestQueueInterval()
	{
		$result = null;

		$query = QueueTable::getList([
			'filter' => [
				'<=EXEC_COUNT' => $this->getRepeatLimit(),
			],
			'limit' => 1,
			'select' => [ 'EXEC_DATE' ],
			'order' => [ 'EXEC_DATE' => 'ASC' ]
		]);

		if ($row = $query->fetch())
		{
			/** @var Main\Type\Date $execDate */
			$execDate = $row['EXEC_DATE'];
			$result = max(0, $execDate->getTimestamp() - time());
		}

		return $result;
	}

	public function processQueue($filter = null, $limit = null, $offset = null)
	{
		$result = new Market\Result\QueueProcess();
		$queryParameters = [
			'filter' => $filter,
			'order' => [ 'ID' => 'ASC' ]
		];

		if ($queryParameters['filter'] === null)
		{
			$queryParameters['filter'] = [
				'<=EXEC_DATE' => new Main\Type\DateTime(),
				'<=EXEC_COUNT' => $this->getRepeatLimit(),
			];
		}

		if ($offset !== null)
		{
			$queryParameters['filter']['>=ID'] = (int)$offset;
		}

		if ($limit === null)
		{
			$limit = $this->getProcessLimit();
		}

		if ($limit > 0)
		{
			$queryParameters['limit'] = $limit;
		}

		$query = QueueTable::getList($queryParameters);

		while ($row = $query->fetch())
		{
			if ($result->getTickCount() > 0 && $this->isTimeExpired())
			{
				$result->interrupt($row['ID']);
				break;
			}

			$result->tick();
			$rowResult = $this->processRow($row);

			if (!$rowResult->isSuccess())
			{
				$result->addErrors($rowResult->getErrors());
			}
		}

		return $result;
	}

	protected function processRow($row)
	{
		$setup = $this->getSetup($row['SETUP_ID']);
		$result = new Main\Result();

		if ($setup !== null && $setup->isActive())
		{
			$procedureResult = $this->repeatProcedure($setup, $row['PATH'], $row['DATA'], $row['ENTITY_TYPE'], $row['ENTITY_ID']);

			if (!$procedureResult->isSuccess())
			{
				$result->addErrors($procedureResult->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			QueueTable::delete($row['ID']);
		}
		else
		{
			$nextExecDate = new Main\Type\DateTime();
			$nextExecDate->add('PT' . $row['INTERVAL'] . 'S');

			QueueTable::update($row['ID'], [
				'EXEC_DATE' => $nextExecDate,
				'EXEC_COUNT' => $row['EXEC_COUNT'] + 1
			]);
		}

		return $result;
	}

	protected function getSetup($id)
	{
		if (!array_key_exists($id, $this->setupCache))
		{
			$this->setupCache[$id] = $this->loadSetup($id);
		}

		return $this->setupCache[$id];
	}

	protected function loadSetup($id)
	{
		try
		{
			$result = Market\Trading\Setup\Model::loadById($id);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = null;
		}

		return $result;
	}

	protected function repeatProcedure(Market\Trading\Setup\Model $setup, $path, $data, $entityType, $entityId)
	{
		$result = new Main\Result();
		$procedure = new Runner($entityType, $entityId);

		try
		{
			$procedure->run($setup, $path, $data);
		}
		catch (\Exception $exception)
		{
			$procedure->logException($exception);
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	protected function isTimeExpired()
	{
		$limit = $this->getTimeLimit();
		$startTime = $this->getStartTime();
		$passedTime = microtime(true) - $startTime;

		return ($passedTime >= $limit);
	}

	protected function getStartTime()
	{
		if ($this->startTime === null)
		{
			$this->startTime = microtime(true);
		}

		return $this->startTime;
	}

	protected function getTimeLimit()
	{
		if ($this->timeLimit === null)
		{
			$maxExecutionTime = (int)ini_get('max_execution_time') * 0.75;
            $systemUsedTime = $this->getSystemUsedTime();
			$optionName = 'trading_repeat_time_limit';
			$optionDefault = 5;

			if (Market\Utils::isCli())
			{
				$optionName .= '_cli';
				$optionDefault = 30;
			}

			$this->timeLimit = (int)Market\Config::getOption($optionName, $optionDefault);

			if ($maxExecutionTime > 0 && $this->timeLimit > ($maxExecutionTime - $systemUsedTime))
			{
				$this->timeLimit = ($maxExecutionTime - $systemUsedTime);
			}
		}

		return $this->timeLimit;
	}

    protected function getSystemUsedTime()
    {
        if (!defined('START_EXEC_TIME')) { return 0; }

        return max(0, microtime(true) - START_EXEC_TIME);
    }

	protected function getProcessLimit()
	{
		return max(1, (int)Market\Config::getOption('trading_repeat_process_limit', 10));
	}

	protected function getRepeatLimit()
	{
		return (int)Market\Config::getOption('trading_repeat_limit', 5);
	}
}