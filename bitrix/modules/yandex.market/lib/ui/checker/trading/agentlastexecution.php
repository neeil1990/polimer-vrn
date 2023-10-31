<?php

namespace Yandex\Market\Ui\Checker\Trading;

use Bitrix\Main;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Trading\Data as TradingData;

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
        return TradingData\Agent::getNamespace();
    }

	protected function resolveAgentName($agentRow)
	{
		$result = (string)TradingData\Agent::getTitle($agentRow['NAME']);

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
		return 'CHECKER_TEST_TRADING_AGENT_LAST_EXECUTION';
	}
}