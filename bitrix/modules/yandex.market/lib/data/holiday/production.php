<?php

namespace Yandex\Market\Data\Holiday;

use Yandex\Market\Reference\Concerns;

class Production extends National
{
	use Concerns\HasMessage;

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function holidays()
	{
		return array_unique(array_merge(parent::holidays(), [
			'23.02',
			'24.02',
			'25.02',
			'26.02',
			'08.03',
			'29.04',
			'30.04',
			'01.05',
			'06.05',
			'07.05',
			'08.05',
			'09.05',
			'10.06',
			'11.06',
			'12.06',
			'04.11',
			'05.11',
			'06.11',
		]));
	}

	public function workdays()
	{
		return [
			'22.02',
			'07.03',
			'03.11',
		];
	}
}