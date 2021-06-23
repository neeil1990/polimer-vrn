<?php

namespace Yandex\Market\Export\Run;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Agent extends Market\Reference\Agent\Base
{
	const NOTIFY_TAG = 'YANDEX_MARKET_RUN_AGENT';

	protected static $offsetStorageIds = [];
	protected static $timeLimit;
	protected static $startTime;
	protected static $hasRunWithResourcesExpired = false;
	protected static $isEnvironmentChecked = false;
	protected static $originGlobalVariables = [];

	public static function getDefaultParams()
	{
		return [
			'interval' => 5
		];
	}

	public static function change()
	{
		$isNeedRepeatAgent = false;

		if (static::isResourcesExpired())
		{
			$isNeedRepeatAgent = true;
		}
		else
		{
			$readySetupIds = [];
			$method = 'change';
			$isResourcesExpired = false;

			while ($setupId = static::getNextSetupId($readySetupIds))
			{
				$interceptor = static::createInterceptor($method, $setupId);
				$state = static::getState($method, $setupId);
				$stateStartTime = (
					!empty($state['START_TIME']) && $state['START_TIME'] instanceof Main\Type\DateTime
						? $state['START_TIME']
						: null
				);
				$startTime = ($stateStartTime !== null ? $stateStartTime : new Main\Type\DateTime());
				$changes = static::getSetupChanges($setupId, $stateStartTime);
				$changesBySource = static::groupChangesByType($changes);
				$isFinished = false;
				$isError = false;
				$progressStep = null;
				$progressOffset = null;

				static::checkEnvironment($setupId, $method);
				static::resolveGlobalVariables();
				$interceptor->bind();

				try
				{
					/** @var \Yandex\Market\Export\Setup\Model $setup */
					$setup = Market\Export\Setup\Model::loadById($setupId);

					if (!$setup->isFileReady())
					{
						$progressStep = isset($state['STEP']) ? $state['STEP'] : null;
						$progressOffset = isset($state['OFFSET']) ? $state['OFFSET'] : null;
					}
					else
					{
						$processor = new Market\Export\Run\Processor($setup, [
							'changes' => $changesBySource,
							'step' => isset($state['STEP']) ? $state['STEP'] : null,
							'stepOffset' => isset($state['OFFSET']) ? $state['OFFSET'] : null,
							'startTime' => static::getStartTime(),
							'timeLimit' => static::getTimeLimit(),
							'usePublic' => true,
							'initTime' => $startTime
						]);

						$processResult = $processor->run('change');

						if ($processResult->isFinished())
						{
							$isFinished = true;
						}
						else if (!$processResult->isSuccess())
						{
							$isError = true;
						}
						else
						{
							$progressStep = $processResult->getStep();
							$progressOffset = $processResult->getStepOffset();
						}

						if ($processor->isResourcesExpired())
						{
							$isResourcesExpired = true;
						}
					}
				}
				catch (\Exception $exception)
				{
					$isError = true;

					static::handleException($method, $setupId, $exception);
				}
				catch (\Throwable $exception)
				{
					$isError = true;

					static::handleException($method, $setupId, $exception);
				}

				if ($isFinished || $isError)
				{
              		static::releaseChanges($setupId, $changes);
					static::releaseState($method, $setupId, $state ?: false);
				}
				else
				{
					static::setState($method, $setupId, $progressStep, $progressOffset, $startTime, $state);
				}

				$interceptor->unbind();
				static::revertGlobalVariables();

				$readySetupIds[] = $setupId;
				$isNeedRepeatAgent = true;

				if ($isResourcesExpired)
				{
					static::markResourcesExpired();
					break;
				}
			}
		}

		return $isNeedRepeatAgent;
	}

	public static function refreshStart($setupId)
	{
		static::normalizeRefreshStartPeriod($setupId);
		static::register([
			'method' => 'refresh',
			'arguments' => [ (int)$setupId ]
		]);
	}

	protected static function normalizeRefreshStartPeriod($setupId)
	{
		global $pPERIOD;

		try
		{
			/** @var Market\Export\Setup\Model $setup */
			$setup = Market\Export\Setup\Model::loadById($setupId);

			if ($setup->hasFullRefresh() && $setup->hasRefreshTime())
			{
				$now = new Main\Type\DateTime();
				$nextExec = $setup->getRefreshNextExec();

				$pPERIOD = $nextExec->getTimestamp() - $now->getTimestamp();
			}
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			// nothing
		}
	}

	public static function refresh($setupId)
	{
		$isNeedRepeatAgent = false;

		if (!Market\Utils::isCli())
		{
			$isNeedRepeatAgent = true;

			$logger = new Market\Logger\Logger();
			$logger->allowCheckExists();

			$logger->critical(Market\Config::getLang('EXPORT_RUN_AGENT_REFRESH_ONLY_CLI'), [
				'ENTITY_TYPE' => Market\Logger\Table::ENTITY_TYPE_EXPORT_AGENT,
				'ENTITY_PARENT' => $setupId,
				'ENTITY_ID' => 'refresh',
			]);
		}
		else if (static::isResourcesExpired())
		{
			$isNeedRepeatAgent = true;
		}
		else
		{
			$method = 'refresh';
			$interceptor = static::createInterceptor($method, $setupId);
			$state = static::getState($method, $setupId);
			$startTime = !empty($state['START_TIME']) ? $state['START_TIME'] : new Main\Type\DateTime();
			$isFinished = false;
			$isError = false;
			$progressStep = null;
			$progressOffset = null;

			static::checkEnvironment($setupId, $method);
			static::resolveGlobalVariables();
			$interceptor->bind();

			try
			{
				/** @var \Yandex\Market\Export\Setup\Model $setup */
				$setup = Market\Export\Setup\Model::loadById($setupId);
				$processor = new Market\Export\Run\Processor($setup, [
					'step' => isset($state['STEP']) ? $state['STEP'] : null,
					'stepOffset' => isset($state['OFFSET']) ? $state['OFFSET'] : null,
					'startTime' => static::getStartTime(),
					'timeLimit' => static::getTimeLimit(),
					'initTime' => $startTime,
					'usePublic' => true
				]);

				$processResult = $processor->run('refresh');

				if ($processResult->isFinished())
				{
					$isFinished = true;
				}
				else if (!$processResult->isSuccess())
				{
					$isError = true;
				}
				else
				{
					$progressStep = $processResult->getStep();
					$progressOffset = $processResult->getStepOffset();
				}

				if ($processor->isResourcesExpired())
				{
					static::markResourcesExpired();
				}
			}
			catch (\Exception $exception)
			{
				$isError = true;

				static::handleException($method, $setupId, $exception);
			}
			catch (\Throwable $exception)
			{
				$isError = true;

				static::handleException($method, $setupId, $exception);
			}

			if ($isFinished)
			{
				Manager::releaseChanges($setupId, $startTime);
				static::releaseState($method, $setupId, $state ?: false);
			}
			else if ($isError)
			{
				static::releaseState($method, $setupId, $state ?: false);
			}
			else
			{
				$isNeedRepeatAgent = true;
				static::setState($method, $setupId, $progressStep, $progressOffset, $startTime, $state);
			}

			$interceptor->unbind();
			static::revertGlobalVariables();
		}

		return $isNeedRepeatAgent;
	}

	protected static function checkEnvironment($setupId, $method)
	{
		if (static::$isEnvironmentChecked) { return; }

		static::$isEnvironmentChecked = true;

		$result = Market\Environment::check();

		if (!$result->isSuccess())
		{
			$logger = new Market\Logger\Logger();
			$logger->allowBatch();
			$logger->allowCheckExists();

			/** @var Market\Error\Base $error */
			foreach ($result->getErrors() as $error)
			{
				$logger->warning($error->getMessage(), [
					'ENTITY_TYPE' => Market\Logger\Table::ENTITY_TYPE_EXPORT_AGENT,
					'ENTITY_PARENT' => $setupId,
					'ENTITY_ID' => $method
				]);
			}

			$logger->flush();
			$logger->disallowBatch();
		}
	}

	protected static function resolveGlobalVariables()
	{
		if (!empty($GLOBALS['USER']) && !($GLOBALS['USER'] instanceof \CUser))
		{
			static::$originGlobalVariables['USER'] = $GLOBALS['USER'];
			unset($GLOBALS['USER']);
		}
	}

	protected static function revertGlobalVariables()
	{
		foreach (static::$originGlobalVariables as $key => $value)
		{
			$GLOBALS[$key] = $value;
		}

		static::$originGlobalVariables = [];
	}

	protected static function getNextSetupId($skipIds = [])
	{
		$result = null;
		$queryParameters = [
			'select' => [ 'SETUP_ID' ],
			'order' => [ 'TIMESTAMP_X' => 'asc' ],
			'limit' => 1
		];

		if (!empty($skipIds))
		{
			$queryParameters['filter'] = [ '!=SETUP_ID' => $skipIds ];
		}

		$query = Storage\ChangesTable::getList($queryParameters);

		if ($row = $query->fetch())
		{
			$result = (int)$row['SETUP_ID'];
		}

		return $result;
	}

	protected static function getSetupChanges($setupId, Main\Type\Date $startDate = null)
	{
		$result = [];
		$limit = Market\Config::getOption('export_run_agent_changes_limit', 1000);
		$filter = [
			'=SETUP_ID' => $setupId,
		];

		if ($startDate !== null)
		{
			$filter['<=TIMESTAMP_X'] = $startDate;
		}

		$query = Storage\ChangesTable::getList([
			'filter' => $filter,
			'select' => [
				'SETUP_ID',
				'ENTITY_TYPE',
				'ENTITY_ID'
		    ],
		    'order' => [
		        'TIMESTAMP_X' => 'asc'
		    ],
			'limit' => $limit
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	protected static function releaseChanges($setupId, $changes)
	{
		$changesByType = static::groupChangesByType($changes);
		$typeFilters = [];

		if (count($changesByType) > 0)
		{
			$typeFilters['LOGIC'] = 'OR';
		}

		foreach ($changesByType as $type => $ids)
		{
			$typeFilters[] = [
				'=ENTITY_TYPE' => $type,
				'=ENTITY_ID' => $ids,
			];
		}

		Storage\ChangesTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $setupId,
				$typeFilters,
			],
		]);
	}

	protected static function groupChangesByType($changes)
	{
		$result = [];

		foreach ($changes as $change)
		{
			if (!isset($result[$change['ENTITY_TYPE']]))
			{
				$result[$change['ENTITY_TYPE']] = [];
			}

			$result[$change['ENTITY_TYPE']][] = $change['ENTITY_ID'];
		}

		return $result;
	}

	protected static function getState($method, $setupId)
	{
		$result = null;

		$query = Storage\AgentTable::getList([
			'filter' => [
				'=METHOD' => $method,
				'=SETUP_ID' => $setupId
			]
		]);

		if ($row = $query->fetch())
		{
			if ((string)$row['STEP'] === '')
			{
				$row['START_TIME'] = null;
			}

			$result = $row;
		}

		return $result;
	}

	public static function setState($method, $setupId, $step, $offset, $startTime, $currentState = null)
	{
		$fields = [
			'METHOD' => $method,
			'SETUP_ID' => $setupId,
			'STEP' => $step !== null ? $step : '',
			'OFFSET' => $offset !== null ? $offset : '',
			'START_TIME' => $startTime
		];

		if (isset($currentState))
		{
			Storage\AgentTable::update(
				[
					'METHOD' => $method,
					'SETUP_ID' => $setupId
				],
				$fields
			);
		}
		else
		{
			Storage\AgentTable::add($fields);
		}
	}

	public static function releaseState($method, $setupId, $currentState = null)
	{
		$isExists = false;

		if ($currentState !== null)
		{
			$isExists = !empty($currentState);
		}
		else
		{
			$state = static::getState($method, $setupId);

			$isExists = !empty($state);
		}

		if ($isExists)
		{
			Storage\AgentTable::update(
				[
					'METHOD' => $method,
					'SETUP_ID' => $setupId
				],
				[
					'STEP' => '',
					'OFFSET' => '',
				]
			);
		}
	}

	protected static function markResourcesExpired()
	{
		static::$hasRunWithResourcesExpired = true;
	}

	protected static function isResourcesExpired()
	{
		return static::$hasRunWithResourcesExpired;
	}

	protected static function getStartTime()
	{
		if (static::$startTime === null)
		{
			static::$startTime = defined('START_EXEC_TIME') ? START_EXEC_TIME : microtime(true);
		}

		return static::$startTime;
	}

	protected static function getTimeLimit()
	{
		$result = null;

		if (static::$timeLimit !== null)
		{
			$result = static::$timeLimit;
		}
		else
		{
			$maxTime = (int)ini_get('max_execution_time') * 0.75;

			if (Market\Utils::isCli())
			{
				$result = (int)Market\Config::getOption('export_run_agent_time_limit_cli', 30);
			}
			else
			{
				$result = (int)Market\Config::getOption('export_run_agent_time_limit', 5);
			}

			if ($maxTime > 0 && $result > $maxTime)
			{
				$result = $maxTime;
			}

			static::$timeLimit = $result;
		}

		return $result;
	}

	protected static function createInterceptor($method, $setupId)
	{
		return new Diag\Interceptor(static function($exception) use ($method, $setupId) {
			Agent::handleException($method, $setupId, $exception);
		});
	}

	protected static function handleException($method, $setupId, $exception)
	{
		try
		{
			static::logException($method, $setupId, $exception);
			static::registerNotifyLog();
		}
		catch (\Exception $internalException)
		{
			throw $exception;
		}
		catch (\Throwable $internalException)
		{
			throw $exception;
		}
	}

	protected static function logException($method, $setupId, $exception)
	{
		$logger = new Market\Logger\Logger();
		$logger->critical($exception, [
			'ENTITY_TYPE' => Market\Logger\Table::ENTITY_TYPE_EXPORT_AGENT,
			'ENTITY_PARENT' => $setupId,
			'ENTITY_ID' => $method
		]);
	}

	protected static function registerNotifyLog()
	{
		Market\Ui\Checker\Notify::error();
	}
}