<?php
namespace Yandex\Market\Export\Run;

use Yandex\Market\Watcher;
use Yandex\Market\Glossary;

class Agent extends Watcher\Agent\AgentFacade
{
	/** @deprecated */
	const NOTIFY_TAG = 'YANDEX_MARKET_RUN_AGENT';

	public static function refreshStart($setupId, $needRestart = null)
	{
		if ($needRestart === null)
		{
			$needRestart = (Writer\IndexFacade::isAllowed() && !Writer\IndexFacade::search($setupId));
		}

		return parent::refreshStart($setupId, $needRestart);
	}

	/** @deprecated */
	public static function releaseState($method, $setupId, $currentState = null)
	{
		Watcher\Agent\StateFacade::release($method, static::serviceType(), $setupId);
	}

	protected static function serviceType()
	{
		return Glossary::SERVICE_EXPORT;
	}
}