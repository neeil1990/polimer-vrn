<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;
use Yandex\Market\Reference\Concerns;

class Locker
{
	use Concerns\HasMessage;

	protected $sign;
	protected $timeout;
	protected $locked = false;

	public function __construct($sign, $timeout = 30)
	{
		$this->sign = (string)$sign;
		$this->timeout = (int)$timeout;
	}

	public function lock()
	{
		if (!$this->supports()) { return; }

		$sql = sprintf(
			"SELECT GET_LOCK('%s', %d)",
			$this->sign,
			$this->timeout
		);

		$lock = $this->connection()->queryScalar($sql);

		if ((string)$lock === '0')
		{
			throw new Main\SystemException(self::getMessage('REJECTED'));
		}

		$this->locked = true;
	}

	public function release()
	{
		if (!$this->locked || !$this->supports()) { return; }

		$sql = sprintf(
			"DO RELEASE_LOCK('%s')",
			$this->sign
		);

		$this->connection()->queryExecute($sql);
		$this->locked = false;
	}

	protected function supports()
	{
		return $this->connection() instanceof Main\DB\MysqlCommonConnection;
	}

	protected function connection()
	{
		return Main\Application::getConnection();
	}
}