<?php

namespace Yandex\Market\Data\Holiday;

use Yandex\Market\Reference\Concerns;

class National implements CalendarInterface
{
	use Concerns\HasMessage;

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function holidays()
	{
		return [
			'01.01',
			'02.01',
			'03.01',
			'04.01',
			'05.01',
			'06.01',
			'07.01',
			'08.01',
			'23.02',
			'08.03',
			'01.05',
			'09.05',
			'12.06',
			'04.11',
			'31.12',
		];
	}

	public function workdays()
	{
		return [];
	}
}