<?php

namespace Yandex\Market\Export\Xml\Listing;

use Yandex\Market\Data\TextString;
use Yandex\Market\Reference\Concerns;

class ConditionQuality implements Listing
{
	use Concerns\HasMessage;

	const PERFECT = 'perfect';
	const EXCELLENT = 'excellent';
	const GOOD = 'good';

	public function values()
	{
		return [
			static::PERFECT,
			static::EXCELLENT,
			static::GOOD,
		];
	}

	public function display($value)
	{
		return self::getMessage(TextString::toUpper($value), null, $value);
	}

	public function synonyms($value)
	{
		$message = (string)self::getMessage(TextString::toUpper($value) . '_SYNONYM', null, '');

		if ($message === '') { return []; }

		return explode(',', $message);
	}
}