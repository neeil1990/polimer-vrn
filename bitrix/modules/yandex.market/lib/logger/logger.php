<?php
namespace Yandex\Market\Logger;

use Bitrix\Main;
use Yandex\Market;

class Logger extends Reference\Logger
{
	public function __construct()
	{
		$this->level = Market\Config::getOption('export_log_level', Market\Logger\Level::WARNING);
	}

	public function getDataClass()
	{
		return Table::class;
	}

	protected function getContextFields()
	{
		return [
			'ENTITY_TYPE',
			'ENTITY_PARENT',
			'ENTITY_ID',
			'ERROR_CODE',
		];
	}

	protected function flushUpdate($dataList)
	{
		return true; // disable update for performance issue
	}

	protected function isTracingOn()
	{
		return true;
	}
}
