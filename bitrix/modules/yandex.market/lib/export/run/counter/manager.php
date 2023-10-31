<?php

namespace Yandex\Market\Export\Run\Counter;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Manager
{
	protected $counter;
	protected $typeOffset = 0;
	protected $types = [
		'Temporary',
		'Manual'
	];

	public function getCounter()
	{
		if ($this->counter === null)
		{
			$this->counter = $this->createCounter();
		}

		return $this->counter;
	}

	public function hasCounter()
	{
		return isset($this->types[$this->typeOffset]);
	}

	public function invalidateCounter()
	{
		$this->counter = null;
		$this->typeOffset++;
	}

	/**
	 * @return Base
	 * @throws Main\SystemException
	 */
	protected function createCounter()
	{
		if (!$this->hasCounter())
		{
			throw new Main\SystemException(Market\Config::getLang('EXPORT_RUN_COUNTER_TYPE_EXPIRED'));
		}

		$method = $this->types[$this->typeOffset];
		$className = __NAMESPACE__ . '\\' . $method;

		return new $className;
	}
}