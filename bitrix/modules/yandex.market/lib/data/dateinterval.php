<?php

namespace Yandex\Market\Data;

use Bitrix\Main;

class DateInterval
{
	public static function isValid($periodString)
	{
		return preg_match('/^-?P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/', $periodString);
	}
}