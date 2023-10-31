<?php

namespace Yandex\Market\Utils\ServerStamp;

use Bitrix\Main;
use Yandex\Market\Reference\Concerns;
use Yandex\Market;

class Development implements PropertyInterface
{
	use Concerns\HasMessage;

	const START_NAME = 'server_stamp_development_start';

	public function name()
	{
		return 'development';
	}

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function reset()
	{
		Market\State::remove(static::START_NAME);
	}

	public function collect()
	{
		return Main\Config\Option::get('main', 'update_devsrv', '') === 'Y';
	}

	public function test($stored, $current)
	{
		if ($current && !$stored)
		{
			throw new ChangedException(self::getMessage('STARTED'));
		}

		if ($current && $stored && $this->expired())
		{
			throw new ChangedException(self::getMessage('EXPIRED'));
		}

		if (!$current)
		{
			$this->resetStart();
		}
	}

	protected function expired()
	{
		$now = new Main\Type\DateTime();
		$start = $this->storedStart() ?: $this->touchStart();
		$diff = $now->getTimestamp() - $start->getTimestamp();

		return ($diff > $this->expireLimit());
	}

	protected function expireLimit()
	{
		return 60 * 60 * 6; // six hours for development
	}

	protected function resetStart()
	{
		$stored = $this->storedStart();

		if ($stored === null) { return; }

		Market\State::remove(static::START_NAME);
	}

	protected function storedStart()
	{
		$dateString = (string)Market\State::get(static::START_NAME);

		if ($dateString === '') { return null; }

		return new Main\Type\DateTime($dateString, \DateTime::ATOM);
	}

	protected function touchStart()
	{
		$result = new Main\Type\DateTime();

		Market\State::set(static::START_NAME, $result->format(\DateTime::ATOM));

		return $result;
	}
}