<?php

namespace Yandex\Market\Data;

class Quantity
{
	public static function equal($first, $second, $gap = 0.01)
	{
		if ($first === $second) { return true; }

		$firstRounded = static::round($first);
		$secondRounded = static::round($second);

		if ($firstRounded === $secondRounded) { return true; }

		$diff = abs($firstRounded - $secondRounded);

		return $diff <= $gap;
 	}

    public static function floor($value, $precision = 2, $gap = 0.01)
    {
	    $roundValue = static::round($value, $precision);
	    $diff = abs($roundValue - $value);

	    if ($diff <= $gap) { return $roundValue; }

		$multiplier = 10 ** $precision;
		$floorValue = (floor($value * $multiplier) / $multiplier);

		return $floorValue;
    }

	public static function round($value, $precision = 2)
	{
		return round($value, $precision);
	}
}