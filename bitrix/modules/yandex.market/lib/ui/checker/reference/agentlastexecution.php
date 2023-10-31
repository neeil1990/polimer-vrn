<?php

namespace Yandex\Market\Ui\Checker\Reference;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;

abstract class AgentLastExecution extends AbstractTest
{
	protected $systemMessage;
	protected $hasExecutedCache = [];

	public function test()
	{
		$result = new Market\Result\Base();

		\CTimeZone::Disable();

		foreach ($this->getWaitingAgents() as $agentRow)
		{
			$agentName = $this->resolveAgentName($agentRow);
			$agentState = $this->resolveAgentState($agentRow);
			$agentError = $this->makeError($agentName, $agentState, 'AGENT_WAIT_' . $agentRow['ID']);

			$result->addError($agentError);
		}

		\CTimeZone::Enable();

		return $result;
	}

	protected function getWaitingAgents()
	{
		$result = [];

		$query = \CAgent::GetList(
			[],
			[
				'MODULE_ID' => Market\Config::getModuleName(),
				'ACTIVE' => 'Y',
				'NAME' => $this->getAgentNamespace() . '\\%',
			]
		);

		while ($row = $query->Fetch())
		{
			if (!$this->isWaitingAgent($row)) { continue; }

			$result[] = $row;
		}

		return $result;
	}

    abstract protected function getAgentNamespace();

	protected function isWaitingAgent($agentRow)
	{
		$nextExecTimestamp = MakeTimeStamp($agentRow['NEXT_EXEC'], FORMAT_DATETIME);

		if ($nextExecTimestamp === false) { return true; }

		$nextExecDate = Main\Type\DateTime::createFromTimestamp($nextExecTimestamp);
		$nextExecLimit = $this->getNextExecLimit();
		$isLessLimit = Market\Data\DateTime::compare($nextExecDate, $nextExecLimit) === -1; // next exec less limit

		if ($isLessLimit === false) { return false; }
		if ((string)$agentRow['LAST_EXEC'] !== '') { return true; }
		if (!$this->isJustRegistered($nextExecDate, $nextExecLimit)) { return true; }

		$checkTimestamp = (string)$agentRow['DATE_CHECK'] !== ''
			? MakeTimeStamp($agentRow['DATE_CHECK'], FORMAT_DATETIME)
			: false;

		if ($checkTimestamp !== false)
		{
			$checkDate = Main\Type\DateTime::createFromTimestamp($checkTimestamp);

			return Market\Data\DateTime::compare($checkDate, $nextExecLimit) === -1;
		}

		return !$this->hasExecutedAgents($nextExecLimit, $agentRow['IS_PERIOD']);
	}

	protected function getNextExecLimit()
	{
		$result = new Main\Type\DateTime();
		$result->add('-PT1H');

		return $result;
	}

	protected function isJustRegistered(Main\Type\DateTime $date, Main\Type\DateTime $limit)
	{
		return (
			$date->format('H:i:s') === '00:00:00'
			&& $date->format('d.m.Y') === $limit->format('d.m.Y')
		);
	}

	protected function hasExecutedAgents(Main\Type\DateTime $from, $isPeriod = 'N')
	{
		$filter = [
			'ACTIVE' => 'Y',
			'IS_PERIOD' => $isPeriod,
			'LAST_EXEC' => ConvertTimeStamp($from->getTimestamp()),
		];
		$cacheKey = md5(serialize($filter));

		if (isset($this->hasExecutedCache[$cacheKey])) { return $this->hasExecutedCache[$cacheKey]; }

		$query = \CAgent::GetList([], $filter);
		$result = (bool)$query->Fetch();

		$this->hasExecutedCache[$cacheKey] = $result;

		return $result;
	}

	abstract protected function resolveAgentName($agentRow);

	protected function resolveAgentState($agentRow)
	{
		$lastExecMessage = $agentRow['LAST_EXEC']
			? $this->getMessage('LAST_EXEC_FROM', [ '#TIME#' => $agentRow['LAST_EXEC'] ])
			: '';

		if ($agentRow['RUNNING'] === 'Y')
		{
			$result = $this->getMessage('STATE_HANG', [
				'#LAST_EXEC#' => $lastExecMessage,
			]);
		}
		else if ((string)$agentRow['DATE_CHECK'] !== '')
		{
			$result = $this->getMessage('STATE_QUEUED', [
				'#LAST_EXEC#' => $lastExecMessage,
			]);
		}
		else
		{
			$result = $this->getMessage('STATE_WAIT', [
				'#LAST_EXEC#' => $lastExecMessage,
			]);
		}

		return $result;
	}

	protected function makeError($agentName, $state, $code = 0)
	{
		$message = sprintf('%s: %s', $agentName, $state);
		$systemMessage = $this->getSystemMessage();

		$result = new Checker\Reference\Error($message, $code);
		$result->setDescription($systemMessage);

		return $result;
	}

	protected function getSystemMessage()
	{
		if ($this->systemMessage === null)
		{
			$this->systemMessage = $this->fetchSystemMessage();
		}

		return $this->systemMessage;
	}

	protected function fetchSystemMessage()
	{
		$test = new \CSiteCheckerTest();
		$testResult = $test->check_bx_crontab();
		$message = '';

		if ($testResult === false)
		{
			$message = $this->getMessage('RESOLVE_CRONTAB_SETUP');
		}

		return $message;
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_AGENT_LAST_EXECUTION';
	}
}