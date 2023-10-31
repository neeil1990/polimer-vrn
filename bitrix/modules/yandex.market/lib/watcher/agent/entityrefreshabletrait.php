<?php
namespace Yandex\Market\Watcher\Agent;

use Bitrix\Main;

/**
 * @method int|null getRefreshPeriod()
 * @method array|null getRefreshTime()
*/
trait EntityRefreshableTrait
{
	public function hasFullRefresh()
	{
		return $this->getRefreshPeriod() !== null;
	}

	public function hasRefreshTime()
	{
		return $this->getRefreshTime() !== null;
	}

	public function getRefreshNextExec()
	{
		$interval = $this->getRefreshPeriod();
		$time = $this->getRefreshTime();
		$now = new Main\Type\DateTime();
		$nowTimestamp = $now->getTimestamp();
		$date = new Main\Type\DateTime();

		if ($time !== null && $interval > 0)
		{
			$date->setTime(...$time);

			if ($date->getTimestamp() > $nowTimestamp)
			{
				$date->add('-P1D');
			}

			while ($date->getTimestamp() <= $nowTimestamp)
			{
				$date->add('PT' . $interval . 'S');
			}
		}
		else
		{
			$date->add('PT' . $interval . 'S');
		}

		return $date;
	}
}