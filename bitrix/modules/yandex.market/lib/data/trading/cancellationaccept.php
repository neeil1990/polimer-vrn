<?php

namespace Yandex\Market\Data\Trading;

use Yandex\Market\Reference\Concerns;

class CancellationAccept
{
	use Concerns\HasMessage;

	const WAIT = 'WAIT';
	const CONFIRM = 'CONFIRM';
	const REJECT = 'REJECT';

	public static function getStateTitle($state)
	{
		return self::getMessage($state);
	}
}