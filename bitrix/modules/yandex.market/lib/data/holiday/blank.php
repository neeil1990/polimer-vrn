<?php

namespace Yandex\Market\Data\Holiday;

use Yandex\Market\Reference\Concerns;

class Blank implements CalendarInterface
{
	use Concerns\HasMessage;

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function holidays()
	{
		return [];
	}

	public function workdays()
	{
		return [];
	}
}