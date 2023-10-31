<?php
namespace Yandex\Market\Watcher\Agent;

use Bitrix\Main;
use Yandex\Market\Watcher;
use Yandex\Market\Reference;

abstract class AgentFacade extends Reference\Agent\Base
{
	public static function getDefaultParams()
	{
		return Watcher\Agent\Changes::getDefaultParams();
	}

	public static function change()
	{
		return Watcher\Agent\Changes::process(static::serviceType());
	}

	public static function refreshStart($setupId, $needRestart = null)
	{
		return Watcher\Agent\Refresh::start(static::serviceType(), $setupId, $needRestart, static::class, [
			'method' => 'refresh',
			'arguments' => [ (int)$setupId ],
		]);
	}

	public static function refresh($setupId, $needRestart = false)
	{
		return Watcher\Agent\Refresh::process(static::serviceType(), $setupId, $needRestart);
	}

	protected static function serviceType()
	{
		throw new Main\NotImplementedException();
	}
}