<?php

namespace Yandex\Market\Data\Holiday;

use Yandex\Market\Reference\Concerns;

class Manual implements CalendarInterface
{
	use Concerns\HasMessage;

	protected $holidays = [];
	protected $workdays = [];

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function setup(array $holidays, array $workdays = [])
	{
		$this->holidays = $holidays;
		$this->workdays = $workdays;
	}

	public function holidays()
	{
		return $this->holidays;
	}

	public function workdays()
	{
		return $this->workdays;
	}
}