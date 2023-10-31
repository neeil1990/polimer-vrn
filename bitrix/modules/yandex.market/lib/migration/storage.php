<?php

namespace Yandex\Market\Migration;

use Bitrix\Main;
use Yandex\Market;

class Storage
{
	public static function canRestore($exception)
	{
		return ($exception instanceof Main\DB\SqlException);
	}

	public static function check()
	{
		$result = !Version::check('storage');

		if ($result)
		{
			Version::update('storage');

			static::reset();
		}

		return $result;
	}

	public static function reset()
	{
		Market\Reference\Storage\Controller::createTable();
		Market\Trading\UseCase\TradeBindingPreserve::restore();
	}
}