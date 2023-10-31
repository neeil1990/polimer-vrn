<?php

namespace Yandex\Market\Export\Xml\Listing;

use Yandex\Market\Data\TextString;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Type;

class ConditionType
	implements Listing, ListingWithMigration
{
	use Concerns\HasMessage;

	const NEW_TYPE = 'new'; // NEW is reserved word
	const PREOWNED = 'preowned';
	const SHOWCASE_SAMPLE = 'showcasesample';
	const REDUCTION = 'reduction';
	const FASHION_PREOWNED = 'fashionpreowned';

	public function values()
	{
		return [
			static::NEW_TYPE,
			static::PREOWNED,
			static::SHOWCASE_SAMPLE,
			static::REDUCTION,
			static::FASHION_PREOWNED,
		];
	}

	public function display($value)
	{
		return self::getMessage(TextString::toUpper($value), null, $value);
	}

	/** @noinspection PhpDeprecationInspection */
	public function migrate($value)
	{
		if ($value === Type\ConditionType::TYPE_USED)
		{
			$result = static::PREOWNED;
		}
		else if ($value === Type\ConditionType::TYPE_LIKE_NEW)
		{
			$result = static::REDUCTION;
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	public function synonyms($value)
	{
		$message = (string)self::getMessage(TextString::toUpper($value) . '_SYNONYM', null, '');

		if ($message === '') { return []; }

		return explode(',', $message);
	}
}