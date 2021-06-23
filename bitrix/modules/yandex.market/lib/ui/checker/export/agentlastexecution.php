<?php

namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Export\Run as ExportRun;

class AgentLastExecution extends Checker\Reference\AbstractTest
{
	protected $systemMessage;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();

		foreach ($this->getWaitingAgents() as $agentRow)
		{
			$agentName = $this->resolveAgentName($agentRow);
			$agentState = $this->resolveAgentState($agentRow);
			$agentError = $this->makeError($agentName, $agentState, 'AGENT_WAIT_' . $agentRow['ID']);

			$result->addError($agentError);
		}

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
				'NAME' => ExportRun\Data\Agent::getNamespace() . '\\%',
			]
		);

		while ($row = $query->Fetch())
		{
			if (!$this->isWaitingAgent($row)) { continue; }

			$result[] = $row;
		}

		return $result;
	}

	protected function isWaitingAgent($agentRow)
	{
		$nextExecTimestamp = MakeTimeStamp($agentRow['NEXT_EXEC'], FORMAT_DATETIME);

		if ($nextExecTimestamp === false)
		{
			$result = true;
		}
		else
		{
			$nextExecDate = Main\Type\DateTime::createFromTimestamp($nextExecTimestamp);
			$nextExecLimit = $this->getNextExecLimit();

			$result = Market\Data\DateTime::compare($nextExecDate, $nextExecLimit) === -1; // next exec less limit
		}

		return $result;
	}

	protected function getNextExecLimit()
	{
		$result = new Main\Type\DateTime();
		$result->add('-PT1H');

		return $result;
	}

	protected function resolveAgentName($agentRow)
	{
		$result = (string)ExportRun\Data\Agent::getTitle($agentRow['NAME']);

		if ($result === '')
		{
			$result = $this->getMessage('NAME_DEFAULT', [
				'#ID#' => $agentRow['ID'],
			]);
		}

		return $result;
	}

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