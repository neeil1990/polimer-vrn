<?php

namespace Yandex\Market\Data;

class Quantity
{
	public static function round($value)
	{
		return round($value, 2);
	}
}