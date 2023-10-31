<?php

namespace Yandex\Market\Ui\Checker\Reference;

use Yandex\Market;
use Yandex\Market\Ui\Checker;

abstract class AgentActivity extends Checker\Reference\AbstractTest
	implements Checker\Reference\FixableTest
{
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

	protected function getInactiveAgents()
	{
		$result = [];

		$query = \CAgent::GetList(
			[],
			[
				'MODULE_ID' => Market\Config::getModuleName(),
				'ACTIVE' => 'N',
				'NAME' => $this->getAgentNamespace() . '\\%',
			]
		);

		while ($row = $query->Fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

    abstract protected function getAgentNamespace();

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

	abstract protected function resolveAgentName($agentRow);

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