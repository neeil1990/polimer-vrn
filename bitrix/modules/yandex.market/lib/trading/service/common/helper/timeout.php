<?php

namespace Yandex\Market\Trading\Service\Common\Helper;

class Timeout
{
	protected $start;
	protected $limit;
	protected $tickTime;
	protected $tickDuration;

	public function __construct($limit)
	{
		$this->start = defined('START_EXEC_TIME') ? (float)START_EXEC_TIME : microtime(true);
		$this->limit = (int)$limit;
		$this->tickTime = microtime(true);
	}

	public function tick()
	{
		$this->tickTime = microtime(true);
	}

	public function check($gap = 0)
	{
		$nextFinishTime = (microtime(true) + $this->getDuration());
		$expireTime = $this->start + $this->limit;

		return ($nextFinishTime + $gap >= $expireTime);
	}

	protected function getDuration()
	{
		$duration = microtime(true) - $this->tickTime;

		if ($this->tickDuration === null || $duration > $this->tickDuration)
		{
			$this->tickDuration = $duration;
		}

		return $this->tickDuration;
	}
}