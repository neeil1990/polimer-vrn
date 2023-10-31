<?php
namespace Yandex\Market\Watcher\Agent;

use Bitrix\Main;
use Yandex\Market\Reference\Agent;
use Yandex\Market\Data\Run\Processor;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Utils;

class Refresh extends Agent\Base
{
	use Concerns\HasMessage;

	public static function start($setupType, $setupId, $restart = null, $proxyAgent = null, array $proxyArguments = [])
	{
		try
		{
			if ($proxyAgent !== null)
			{
				Assert::isSubclassOf($proxyAgent, Agent\Base::class);

				$className = $proxyAgent;
			}
			else
			{
				$className = static::class;
			}

			$agent = $proxyArguments + [
				'method' => 'process',
				'arguments' => [ $setupType, (int)$setupId ],
				'search' => Agent\Controller::SEARCH_RULE_SOFT,
				'interval' => 5,
			];

			if ($className::isRegistered($agent)) { return true; }

			if ($restart) { $agent['arguments'][] = true; }

			static::normalizeRefreshStartPeriod($setupType, $setupId);
			$className::register($agent);

			return true;
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			return false;
		}
	}

	protected static function normalizeRefreshStartPeriod($setupType, $setupId)
	{
		global $pPERIOD;

		$setup = Factory::setup($setupType, $setupId);

		if (!($setup instanceof EntityRefreshable)) { return; }

		if ($setup->hasFullRefresh() && $setup->hasRefreshTime())
		{
			$now = new Main\Type\DateTime();
			$nextExec = $setup->getRefreshNextExec();

			$pPERIOD = $nextExec->getTimestamp() - $now->getTimestamp();
		}
	}

	public static function process($setupType, $setupId, $needRestart = false)
	{
		$processor = Factory::processor('refresh', $setupType, $setupId);
		$action = $needRestart ? Processor::ACTION_FULL : Processor::ACTION_REFRESH;

		if (!Utils::isCli())
		{
			$processor->makeLogger()->error(self::getMessage('ONLY_CLI'));
			return true;
		}

		return $processor->run($action);
	}
}
