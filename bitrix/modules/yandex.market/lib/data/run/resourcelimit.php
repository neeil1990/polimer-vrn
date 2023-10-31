<?php

namespace Yandex\Market\Data\Run;

use Yandex\Market\Data\TextString;
use Yandex\Market\Export;

class ResourceLimit
{
	protected $startTime;
	protected $timeLimit;
	protected $systemTimeLimit;
	protected $systemMemoryLimit;
	protected $tickStartTime;
	protected $tickStartMemory;
	protected $tickDuration;
	protected $tickMemoryUsage;
	protected $isTickContainsInitialization;
	protected $memoryMethod;

	public function __construct($parameters = null)
	{
		$this->initializeTime($parameters);
		$this->initializeMemory($parameters);
	}

	public function getTimeLimit()
	{
		return $this->timeLimit;
	}

	public function getMemoryLimit()
	{
		return $this->getSystemMemoryLimit();
	}

	public function isExpired()
	{
		return ($this->isExpiredTime() || $this->isExpiredMemory());
	}

	public function tick()
	{
		$allowOverride = $this->isPreviousTickContainsInitialization();

		$this->tickTime($allowOverride);
		$this->tickMemory($allowOverride);
	}

	protected function isPreviousTickContainsInitialization()
	{
		$result = false;

		if ($this->isTickContainsInitialization === null) // is initialization step
		{
			$this->isTickContainsInitialization = true;
		}
		else if ($this->isTickContainsInitialization === true) // is after initialization step
		{
			$this->isTickContainsInitialization = false;
			$result = true;
		}

		return $result;
	}

	protected function initializeTime($parameters)
	{
		$timeLimitParameter = isset($parameters['timeLimit']) ? $parameters['timeLimit'] : null;

		$this->startTime = isset($parameters['startTime']) ? $parameters['startTime'] : microtime(true);
		$this->timeLimit = $this->normalizeTimeLimit($timeLimitParameter);
		$this->tickStartTime = microtime(true);
	}

	protected function isExpiredTime()
	{
		$now = microtime(true);
		$nextTickFinishTime = ($now + $this->tickDuration);
		$expireTime = $this->startTime + $this->timeLimit;
		$timeGap = $this->getTimeGap();

		return ($nextTickFinishTime + $timeGap >= $expireTime);
	}

	protected function tickTime($allowOverride = false)
	{
		$now = microtime(true);
		$duration = $now - $this->tickStartTime;

		if ($allowOverride || $this->tickDuration === null || $duration > $this->tickDuration)
		{
			$this->tickDuration = $duration;
		}

		$this->tickStartTime = $now;
	}

	protected function getTimeGap()
	{
		$systemLimit = $this->getSystemTimeLimit();
		$result = 0;

		if ($systemLimit > 0 && $this->timeLimit + $this->tickDuration >= $systemLimit)
		{
			$result = min(max(2, $this->tickDuration * 0.5), 5);
		}

		return $result;
	}

	protected function normalizeTimeLimit($timeLimit)
	{
		$result = max(0, (int)$timeLimit);
		$isNotSetParameter = ($result === 0);
		$systemLimit = $this->getSystemTimeLimit();
        $systemUsed = $this->getSystemUsedTime();

		if ($systemLimit > 0 && ($isNotSetParameter || $result > ($systemLimit - $systemUsed)))
		{
			$result = $systemLimit - $systemUsed;
		}
		else if ($isNotSetParameter)
		{
			$result = Export\Run\Admin::getTimeLimit();
		}

		return $result;
	}

	protected function getSystemTimeLimit()
	{
		if ($this->systemTimeLimit === null)
		{
			$this->systemTimeLimit = $this->fetchSystemTimeLimit();
		}

		return $this->systemTimeLimit;
	}

	protected function fetchSystemTimeLimit()
	{
		return (int)ini_get('max_execution_time');
	}

    protected function getSystemUsedTime()
    {
        if (!defined('START_EXEC_TIME')) { return 0; }

        return (int)max(0, ceil(microtime(true) - START_EXEC_TIME));
    }

	protected function initializeMemory($parameters)
	{
		$this->tickStartMemory = $this->getMemoryUsage();
	}

	protected function isExpiredMemory()
	{
		$nowMemoryUsage = $this->getMemoryUsage();
		$nextTickMemoryUsage = $nowMemoryUsage + $this->tickMemoryUsage;
		$memoryLimit = $this->getMemoryLimit();
		$memoryGap = $this->getMemoryGap();

		return ($memoryLimit > 0 && $nextTickMemoryUsage + $memoryGap >= $memoryLimit);
	}

	protected function tickMemory($allowOverride = false)
	{
		$nowMemoryUsage = $this->getMemoryUsage();
		$memoryForOneTick = max(0, $nowMemoryUsage - $this->tickStartMemory);

		if ($allowOverride || $this->tickMemoryUsage === null || $memoryForOneTick > $this->tickMemoryUsage)
		{
			$this->tickMemoryUsage = $memoryForOneTick;
		}
	}

	protected function getMemoryUsage()
	{
		switch ($this->getMemoryMethod())
		{
			case 'current':
				$result = memory_get_usage();
			break;

			case 'peak':
			default:
				$result = memory_get_peak_usage();
			break;
		}

		return $result;
	}

	protected function getMemoryMethod()
	{
		if ($this->memoryMethod === null)
		{
			$this->memoryMethod = $this->fetchMemoryMethod();
		}

		return $this->memoryMethod;
	}

	protected function fetchMemoryMethod()
	{
		$currentUsage = memory_get_usage();
		$peakUsage = memory_get_peak_usage();

		if ($currentUsage / $peakUsage <= 0.75)
		{
			$result = 'current';
		}
		else
		{
			$result = 'peak';
		}

		return $result;
	}

	protected function getMemoryGap()
	{
		$multiply = $this->isTickContainsInitialization ? 0.2 : 0.33;

		return $multiply * $this->tickMemoryUsage;
	}

	protected function getSystemMemoryLimit()
	{
		if ($this->systemMemoryLimit === null)
		{
			$this->systemMemoryLimit = $this->fetchSystemMemoryLimit();
		}

		return $this->systemMemoryLimit;
	}

	protected function fetchSystemMemoryLimit()
	{
		$iniValue = ini_get('memory_limit');
		$iniValue = trim($iniValue);
		$valueUnit = TextString::toUpper(TextString::getSubstring($iniValue, -1));
		$result = (int)$iniValue;

		switch ($valueUnit)
		{
			case 'G':
				$result *= 1024 * 1024 * 1024;
			break;

			case 'M':
				$result *= 1024 * 1024;
			break;

			case 'K':
				$result *= 1024;
			break;
		}

		return $result;
	}
}