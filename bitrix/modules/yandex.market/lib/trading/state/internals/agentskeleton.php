<?php

namespace Yandex\Market\Trading\State\Internals;

use Bitrix\Main;
use Yandex\Market;

class AgentSkeleton extends Market\Reference\Agent\Base
{
	const PERIOD_STEP_DEFAULT = 60;
	const PERIOD_TIMEOUT_DEFAULT = 600;

	protected static $startTime;
	protected static $timeLimit;
	protected static $lastSetup;

	public static function optionName($name)
	{
		$prefix = static::getOptionPrefix();

		return $prefix . '_' . $name;
	}

	protected static function wrapAction(callable $action, array $arguments, $errorCount = 0)
	{
		global $pPERIOD;

		try
		{
			if (static::isTimeExpired()) { return true; }

			Market\Environment::restore();
			Market\Environment::makeUserPlaceholder(); // required for Sale\Payment::onFieldModify()

			$pPERIOD = static::getPeriod('step', static::PERIOD_STEP_DEFAULT);

			$result = $action(...$arguments);

			Market\Environment::reset();

			return $result;
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			Market\Environment::reset();

			if (static::canRepeat($exception, $errorCount))
			{
				$pPERIOD = static::getPeriod('timeout', static::PERIOD_TIMEOUT_DEFAULT);

				return array_merge($arguments, [ $errorCount + 1 ]); // wait for service up
			}

			static::actionError($exception, $arguments);
		}
		catch (Main\SystemException $exception)
		{
			Market\Environment::reset();
			static::actionError($exception, $arguments);
		}
		catch (\Exception $exception)
		{
			Market\Environment::reset();
			static::actionError($exception, $arguments);
			throw $exception;
		}
		catch (\Throwable $exception)
		{
			Market\Environment::reset();
			static::actionError($exception, $arguments);
			throw $exception;
		}

		return false; // stop
	}

	protected static function canRepeat($exception, $errorCount)
	{
		return $errorCount < static::getErrorRepeatLimit();
	}

	protected static function actionError($message, $arguments = null)
	{
		try
		{
			$setup = static::getLastSetup();

			if ($setup === null) { return; }

			static::logError($setup, $message, $arguments);
		}
		catch (\Exception $exception)
		{
			$passException = $message instanceof \Exception ? $message : new \Exception($message, 0, $exception);
			throw $passException;
		}
		catch (\Throwable $exception)
		{
			$passException = $message instanceof \Throwable ? $message : new \Exception($message, 0, $exception);
			throw $passException;
		}
	}

	protected static function logError(Market\Trading\Setup\Model $setup, $message, $arguments = null)
	{
		$setup->wakeupService()->getLogger()->error($message, [
			'AUDIT' => Market\Logger\Trading\Audit::PROCEDURE,
		]);
	}

	/** @return string */
	protected static function getOptionPrefix()
	{
		throw new Main\NotImplementedException('Method getOptionPrefix not implemented');
	}

	protected static function getPeriod($type, $default)
	{
		$name = static::optionName('period_' . $type);
		$option = (int)Market\Config::getOption($name, 0);

		return $option > 0 ? $option : $default;
	}

	protected static function getErrorRepeatLimit()
	{
		$name = static::optionName('repeat_limit');

		return (int)Market\Config::getOption($name, 10);
	}

	protected static function isTimeExpired()
	{
		$limit = static::getTimeLimit();
		$startTime = static::getStartTime();
		$passedTime = microtime(true) - $startTime;

		return ($passedTime >= $limit);
	}

	protected static function getStartTime()
	{
		if (static::$startTime === null)
		{
			static::$startTime = microtime(true);
		}

		return static::$startTime;
	}

	protected static function getTimeLimit()
	{
		if (static::$timeLimit === null)
		{
			$maxExecutionTime = (int)ini_get('max_execution_time') * 0.75;
            $systemUsedTime = static::getSystemUsedTime();
			$optionName = static::optionName('time_limit');
			$optionDefault = 5;

			if (Market\Utils::isCli())
			{
				$optionName .= '_cli';
				$optionDefault = 30;
			}

			static::$timeLimit = (int)Market\Config::getOption($optionName, $optionDefault);

			if ($maxExecutionTime > 0 && static::$timeLimit > ($maxExecutionTime - $systemUsedTime))
			{
				static::$timeLimit = ($maxExecutionTime - $systemUsedTime);
			}
		}

		return static::$timeLimit;
	}

    protected static function getSystemUsedTime()
    {
        if (!defined('START_EXEC_TIME')) { return 0; }

        return max(0, microtime(true) - START_EXEC_TIME);
    }

	protected static function getSetup($setupId)
	{
		$setup = Market\Trading\Setup\Model::loadById($setupId);

		static::$lastSetup = $setup;

		if (!$setup->isActive())
		{
			throw new Main\SystemException(sprintf('setup %s is inactive', $setupId));
		}

		return $setup;
	}

	/** @return Market\Trading\Setup\Model|null */
	protected static function getLastSetup()
	{
		return static::$lastSetup;
	}
}