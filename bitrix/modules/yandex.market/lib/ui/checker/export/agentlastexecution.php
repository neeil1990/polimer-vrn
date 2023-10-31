<?php

namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Export\Run as ExportRun;

class AgentLastExecution extends Checker\Reference\AgentLastExecution
{
	protected $systemMessage;
	protected $hasExecutedCache = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

    protected function getAgentNamespace()
    {
        return ExportRun\Data\Agent::getNamespace();
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

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_AGENT_LAST_EXECUTION';
	}
}