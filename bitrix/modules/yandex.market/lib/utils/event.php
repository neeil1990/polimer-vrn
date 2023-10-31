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

	public static function eventTitle($event)
	{
		if (!is_array($event)) { return 'unknown'; }

		if (isset($event['CALLBACK']))
		{
			if (is_array($event['CALLBACK']))
			{
				$title = $event['CALLBACK'][0] . '::' . $event['CALLBACK'][1];
			}
			else if (is_string($event['CALLBACK']))
			{
				$title = $event['CALLBACK'];
			}
			else
			{
				try
				{
					$reflection = new \ReflectionFunction($event['CALLBACK']);
					$title = $reflection->getFileName() . ':' . $reflection->getStartLine();
				}
				catch (\ReflectionException $exception)
				{
					$title = 'anonymous';
				}
			}
		}
		else if (isset($event['TO_CLASS'], $event['TO_METHOD']))
		{
			$title = $event['TO_CLASS'] . '::' . $event['TO_METHOD'];
		}
		else
		{
			$title = 'unknown';
		}

		return $title;
	}
}