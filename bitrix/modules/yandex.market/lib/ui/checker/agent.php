<?php

namespace Yandex\Market\Ui\Checker;

use Bitrix\Main;
use Yandex\Market;

class Agent extends Market\Reference\Agent\Regular
{
	public static function getDefaultParams()
	{
		return [
			'interval' => 3600 * 24, // once at day
		];
	}

	public static function run()
	{
		$history = new History();
		$hasFailed = false;

		foreach (static::getTests() as $testClass)
		{
			$test = Factory::make($testClass);

			$testResult = $test->test();
			$history->register($test, $testResult);

			if (!$testResult->isSuccess())
			{
				$hasFailed = true;
			}
		}

		if ($history->hasErrors())
		{
			Notify::error();
		}
		else if (!$hasFailed)
		{
			Notify::closeError();
		}

		$history->flush();
	}

	protected static function getTests()
	{
		return [
			Export\SetupStatus::class,
			Export\PromoStatus::class,
			Export\AgentActivity::class,
			Export\AgentLastExecution::class,
			Export\AgentLog::class,
			Trading\IncomingRequest::class,
			Trading\OutgoingRequest::class,
			Trading\EventLog::class,
		];
	}
}