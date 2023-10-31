<?php

namespace Yandex\Market\Api;

use Bitrix\Main;
use Yandex\Market;

class Locker
{
	protected $key;
	protected $limit;
	protected $timeout;
	protected $lockIndex;

	public function __construct($key, $limit, $timeout = 30)
	{
		$this->key = (string)$key;
		$this->limit = (int)$limit;
		$this->timeout = (int)$timeout;
	}

	public function lock()
	{
		if ($this->limit < 1) { return; }

		$variants = range(1, $this->limit);
		$variantsCount = count($variants);
		$variantIndex = 0;

		shuffle($variants);

		foreach ($variants as $variant)
		{
			$isLast = ($variantsCount === $variantIndex + 1);

			if ($this->createLock($variant, $isLast))
			{
				$this->lockIndex = $variant;
				break;
			}

			++$variantIndex;
		}
	}

	public function release()
	{
		if ($this->lockIndex !== null)
		{
			$this->releaseLock($this->lockIndex);
			$this->lockIndex = null;
		}
	}

	protected function createLock($index, $useTimeout)
	{
		$connection = $this->getConnection();

		if ($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$sql = sprintf(
				"SELECT GET_LOCK('%s', %d)",
				$this->getLockName($index),
				$useTimeout ? $this->timeout : 0
			);

			$lock = $connection->queryScalar($sql);
			$result = ((string)$lock !== '0');
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	protected function releaseLock($index)
	{
		$connection = $this->getConnection();

		if ($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$sql = sprintf(
				"DO RELEASE_LOCK('%s')",
				$this->getLockName($index)
			);

			$connection->queryExecute($sql);
		}
	}

	protected function getLockName($index)
	{
		return md5($this->key . (int)$index);
	}

	protected function getConnection()
	{
		return Main\Application::getConnection();
	}
}