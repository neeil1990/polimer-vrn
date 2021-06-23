<?php

namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;

class AgentLog extends Checker\Reference\AbstractTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();

		foreach ($this->getLogRecords(Market\Logger\Level::CRITICAL) as $logRecord)
		{
			$error = $this->makeError($logRecord);
			$result->addError($error);
		}

		foreach ($this->getLogRecords(Market\Logger\Level::WARNING) as $logRecord)
		{
			$error = $this->makeError($logRecord);
			$result->addWarning($error);
		}

		return $result;
	}

	protected function getLogRecords($level)
	{
		$query = Market\Logger\Table::getList([
			'filter' => [
				'=ENTITY_TYPE' => Market\Logger\Table::ENTITY_TYPE_EXPORT_AGENT,
				'=LEVEL' => $level,
				'>=TIMESTAMP_X' => $this->getLimitDate(),
			],
			'select' => [ 'MESSAGE' ],
			'order' => [ 'ID' => 'DESC' ],
			'limit' => 5,
		]);

		return $query->fetchAll();
	}

	protected function getLimitDate()
	{
		return (new Main\Type\DateTime())->add('-P1W');
	}

	protected function makeError($logRecord)
	{
		$logUrl = $this->getLogUrl();
		$group = $this->getMessage('ERROR_GROUP', null, 'AGENT');
		$description = $this->getMessage('ERROR_DESCRIPTION', [ '#URL#' => $logUrl ]);

		$result = new Checker\Reference\Error($logRecord['MESSAGE']);
		$result->setDescription($description);
		$result->setGroup($group, $logUrl);

		return $result;
	}

	protected function getLogUrl()
	{
		return Market\Ui\Admin\Path::getModuleUrl('log', [
			'lang' => LANGUAGE_ID,
			'find_entity_type' => Market\Logger\Table::ENTITY_TYPE_EXPORT_AGENT,
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_AGENT_LOG';
	}
}