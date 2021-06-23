<?php

namespace Yandex\Market\Data\Trading;

use Bitrix\Main;
use Yandex\Market;

class Cis
{
	public static function fromMarkingCode($markingCode)
	{
		return Market\Data\TextString::getSubstring($markingCode, 0, 31);
	}
}