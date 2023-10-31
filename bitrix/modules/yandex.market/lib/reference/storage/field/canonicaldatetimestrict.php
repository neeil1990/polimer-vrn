<?php
namespace Yandex\Market\Reference\Storage\Field;

use Bitrix\Main;
use Yandex\Market;

class CanonicalDateTimeStrict extends DateTimeStrict
{
	public static function fill($value)
	{
		if ($value instanceof Main\Type\DateTime)
		{
			$value = Market\Data\DateTime::toCanonical($value);
		}
		else if (empty($value))
		{
			$value = Market\Data\DateTime::makeCanonicalDummy();
		}

		return $value;
	}

	public static function resolve($value)
	{
		if (!($value instanceof Main\Type\DateTime)) { return $value; }

		$value = Market\Data\DateTime::asCanonical($value);

		return parent::resolve($value);
	}
}