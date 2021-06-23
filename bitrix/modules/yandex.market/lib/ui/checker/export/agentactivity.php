<?php

namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Export\Run as ExportRun;

class AgentActivity extends Checker\Reference\AbstractTest
	implements Checker\Reference\FixableTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();

		foreach ($this->getInactiveAgents() as $agentRow)
		{
			$agentName = $this->resolveAgentName($agentRow);
			$agentState = $this->resolveAgentState($agentRow);
			$agentError = new Market\Error\Base($agentName . ': ' . $agentState, 'AGENT_INACTIVE_' . $agentRow['ID']);

			$result->addError($agentError);
		}

		return $result;
	}

	public function fix()
	{
		foreach ($this->getInactiveAgents() as $agentRow)
		{
			$this->activateAgent($agentRow);
		}
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_AGENT_ACTIVITY';
	}

	protected function getInactiveAgents()
	{
		$result = [];

		$query = \CAgent::GetList(
			[],
			[
				'MODULE_ID' => Market\Config::getModuleName(),
				'ACTIVE' => 'N',
				'NAME' => ExportRun\Data\Agent::getNamespace() . '\\%',
			]
		);

		while ($row = $query->Fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	protected function activateAgent($agentRow)
	{
		$fields = [
			'ACTIVE' => 'Y',
		];

		if (isset($agentRow['RETRY_COUNT']) && $agentRow['RETRY_COUNT'] >= 3)
		{
			$fields['RETRY_COUNT'] = 0;
		}

		$updateResult = \CAgent::Update($agentRow['ID'], $fields);

		if (!$updateResult)
		{
			throw Market\Exceptions\Facade::fromApplication();
		}
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
		if (isset($agentRow['RETRY_COUNT']) && $agentRow['RETRY_COUNT'] >= 3)
		{
			$result = $this->getMessage('STATE_CRASH');
		}
		else
		{
			$result = $this->getMessage('STATE_INACTIVE');
		}

		return $result;
	}
}