<?php

namespace Yandex\Market\Export\Xml\Format\Turbo\Data;

use Yandex\Market\Reference\Concerns;

class Info
{
	use Concerns\HasMessage;

	public static function getPublishNote()
	{
		return static::getMessage('PUBLISH');
	}
}