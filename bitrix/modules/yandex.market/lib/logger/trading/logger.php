<?php

namespace Yandex\Market\Logger\Trading;

use Yandex\Market;
use Bitrix\Main;

class Logger extends Market\Logger\Reference\Logger
{
	protected static $tracingOn;

	protected $entityParent;
	protected $level;

	public function getDataClass()
	{
		return Table::class;
	}

	public function setEntityParent($parentId)
	{
		$this->entityParent = $parentId;
	}

	protected function createRow($level, $message, array $context = [])
	{
		$result = parent::createRow($level, $message, $context);

		if ($this->entityParent !== null && empty($result['ENTITY_PARENT']))
		{
			$result['ENTITY_PARENT'] = $this->entityParent;
		}

		return $result;
	}

	protected function getContextFields()
	{
		return [
			'ENTITY_TYPE',
			'ENTITY_PARENT',
			'ENTITY_ID',
			'AUDIT',
			'URL',
			'TRACE',
		];
	}

	protected function isTracingOn()
	{
		if (static::$tracingOn === null)
		{
			static::$tracingOn = (Market\Config::getOption('trading_log_tracing', 'Y') !== 'N');
		}

		return static::$tracingOn;
	}
}