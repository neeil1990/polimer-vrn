<?php

namespace Yandex\Market\Result;

use Bitrix\Main;

class Pool
{
	protected static $instances = [];

	protected $className;
	protected $free = [];

	/**
	 * @param class-string<Base|Main\Result> $className
	 *
	 * @return static
	 */
	public static function getInstance($className = Base::class)
	{
		if (!isset(static::$instances[$className]))
		{
			static::$instances[$className] = new static($className);
		}

		return static::$instances[$className];
	}

	/** @param class-string<Base|Main\Result> $className */
	public function __construct($className)
	{
		$this->className = $className;
	}

	/** @return Base|Main\Result */
	public function get()
	{
		$result = array_pop($this->free);

		if ($result === null || !$result->isSuccess())
		{
			$result = new $this->className;
		}

		return $result;
	}

	/** @param Base|Main\Result $result */
	public function release($result)
	{
		if (!$result->isSuccess()) { return; }

		$this->free[] = $result;
	}
}