<?php

namespace Yandex\Market\Data;

class StringType
{
	public static function sanitize($value)
	{
		return is_scalar($value) && (string)$value !== '' ? (string)$value : null;
	}
}