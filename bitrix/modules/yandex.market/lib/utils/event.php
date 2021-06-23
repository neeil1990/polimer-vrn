<?php

namespace Yandex\Market\Utils;

use Yandex\Market;
use Bitrix\Main;

class Event
{
	public static function bind($className, $handlers)
	{
		foreach ($handlers as $handler)
		{
			Market\Reference\Event\Controller::register($className, $handler);
		}
	}

	public static function unbind($className, $handlers)
	{
		foreach ($handlers as $handler)
		{
			Market\Reference\Event\Controller::unregister($className, $handler);
		}
	}
}