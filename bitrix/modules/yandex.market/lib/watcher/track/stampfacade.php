<?php
namespace Yandex\Market\Watcher\Track;

use Yandex\Market\Watcher\Agent;

class StampFacade
{
	public static function shift($service, $setupId)
	{
		static::shiftStamp($service, $setupId);
		static::releaseAgent($service, $setupId);
	}

	private static function shiftStamp($service, $setupId)
	{
		$state = new StampState($service, $setupId);
		$state->shift();
	}

	private static function releaseAgent($service, $setupId)
	{
		Agent\StateFacade::release('change', $service, $setupId);
	}
}