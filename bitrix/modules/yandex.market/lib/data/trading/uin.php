<?php

namespace Yandex\Market\Data\Trading;

class Uin
{
	public static function formatMarkingCode($markingCode)
	{
		$formatted = preg_replace('/\D/', '', $markingCode);

		return $formatted !== '' ? $formatted : $markingCode;
	}

	public static function diff(array $first, array $second)
	{
		$firstCompare = array_map(static function($code) { return static::formatMarkingCode($code); }, $first);
		$secondCompare = array_map(static function($code) { return static::formatMarkingCode($code); }, $second);
		$diff = array_diff($firstCompare, $secondCompare);

		return array_intersect_key($first, $diff);
	}
}