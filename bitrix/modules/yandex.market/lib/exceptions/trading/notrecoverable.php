<?php

namespace Yandex\Market\Exceptions\Trading;

use Bitrix\Main;
use Yandex\Market;

class NotRecoverable extends Main\SystemException
{
	protected $logLevel = Market\Logger\Level::ERROR;

	public function setLogLevel($level)
	{
		$this->logLevel = $level;
	}

	public function getLogLevel()
	{
		return $this->logLevel;
	}
}