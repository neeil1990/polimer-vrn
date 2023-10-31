<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Command;

use Yandex\Market;
use Bitrix\Main;

class DeliveryIntervalsNormalize
{
	/** @var array{date: Main\Type\Date, fromTime: string|null, toTime: string|null}[] */
	protected $intervals;
	/** @var \Bitrix\Main\Type\Date|null */
	protected $minDate;
	/** @var int|null */
	protected $minDuration;
	/** @var int|null */
	protected $maxDuration;
	/** @var int|null */
	protected $maxTimesCount;
	/** @var int|null */
	protected $maxDaysCount;

	public function __construct(array $intervals)
	{
		$this->intervals = $intervals;
	}

	public function setMinDuration($hours)
	{
		$this->minDuration = (int)$hours;
	}

	public function setMaxDuration($hours)
	{
		$this->maxDuration = (int)$hours;
	}

	public function setMaxTimesCount($count)
	{
		$this->maxTimesCount = (int)$count;
	}

	public function setMaxDaysCount($count)
	{
		$this->maxDaysCount = (int)$count;
	}

	public function setMinDate(Main\Type\Date $dateTime)
	{
		$this->minDate = $dateTime;
	}

	public function execute()
	{
		$dayGroups = [];

		foreach ($this->makeDayGroups() as $dayGroup)
		{
			$dayGroupWithTime = $this->sanitize($dayGroup);

			if (!empty($dayGroupWithTime))
			{
				$dayGroupWithTime = $this->sort($dayGroupWithTime);
				$dayGroupWithTime = $this->intersect($dayGroupWithTime);
				$dayGroupWithTime = $this->mergeByDuration($dayGroupWithTime);
				$dayGroupWithTime = $this->splitByDuration($dayGroupWithTime);
				$dayGroupWithTime = $this->mergeByMaxTimesCount($dayGroupWithTime);
				$dayGroupWithTime = $this->sliceInvalid($dayGroupWithTime);

				$dayGroup = $dayGroupWithTime;
			}

			$dayGroup = $this->expire($dayGroup);

			if (empty($dayGroup)) { continue; }

			$dayGroups[] = $dayGroup;

			if ($this->maxDaysCount !== null && count($dayGroups) >= $this->maxDaysCount) { break; }
		}

		return !empty($dayGroups) ? array_merge(...$dayGroups) : [];
	}

	protected function expire(array $dayGroup)
	{
		if ($this->minDate === null) { return $dayGroup; }

		foreach ($dayGroup as $index => $interval)
		{
			$dateCompare = Market\Data\Date::compare($interval['date'], $this->minDate);
			$isValid = true;

			if ($dateCompare === -1)
			{
				$isValid = false;
			}
			else if ($dateCompare === 0 && isset($interval['toTime']) && ($this->minDate instanceof Main\Type\DateTime))
			{
				$isValid = ($this->minDate->format('H:i') < $interval['toTime']);
			}

			if (!$isValid)
			{
				unset($dayGroup[$index]);
			}
		}

		return $dayGroup;
	}

	protected function sanitize(array $dayGroup)
	{
		$result = [];

		foreach ($dayGroup as $interval)
		{
			if (!isset($interval['fromTime'], $interval['toTime'])) { continue; }

			$interval['fromTime'] = $this->roundTime($interval['fromTime'], false);
			$interval['toTime'] = $this->roundTime($interval['toTime'], true);

			if ($interval['toTime'] === '00:00' && $interval['fromTime'] >= $interval['toTime'])
			{
				$interval['toTime'] = '23:59';
			}

			if ($interval['fromTime'] >= $interval['toTime']) { continue; }

			$result[] = $interval;
		}

		return $result;
	}

	protected function roundTime($time, $direction)
	{
		list($hour, $minutes) = explode(':', $time);

		if ($minutes === '00') { return $time; }
		if ($hour === '23' && $minutes === '59') { return $time; }

		if ($direction)
		{
			$hour = (++$hour % 24);
		}

		return Market\Data\Time::format($hour . ':00');
	}

	protected function sort($dayGroup)
	{
		usort($dayGroup, static function($intervalA, $intervalB)
		{
			if ($intervalA['fromTime'] !== $intervalB['fromTime'])
			{
				return $intervalA['fromTime'] < $intervalB['fromTime'] ? -1 : 1;
			}

			if ($intervalA['toTime'] === $intervalB['toTime'])
			{
				return 0;
			}

			return $intervalA['toTime'] < $intervalB['toTime'] ? -1 : 1;
		});

		return $dayGroup;
	}

	protected function intersect(array $dayGroup)
	{
		foreach ($dayGroup as $index => $interval)
		{
			foreach ($dayGroup as $nextIndex => $nextInterval)
			{
				if ($nextIndex <= $index) { continue; }
				if ($interval['toTime'] <= $nextInterval['fromTime']) { continue; }

				if ($interval['toTime'] <= $nextInterval['toTime'])
				{
					$dayGroup[$nextIndex]['fromTime'] = $interval['toTime'];
				}
				else
				{
					unset($dayGroup[$nextIndex]);
				}
			}
		}

		return $dayGroup;
	}

	protected function mergeByDuration(array $dayGroup)
	{
		if ($this->minDuration === null) { return $dayGroup; }

		$newGroup = $dayGroup;

		foreach ($dayGroup as $index => $interval)
		{
			if (!isset($newGroup[$index])) { continue; }

			$duration = $this->getDuration($interval);
			$fromTime = $interval['fromTime'];
			$toTime = $interval['toTime'];

			if ($duration >= $this->minDuration) { continue; }

			// merge with next

			foreach ($newGroup as $nextIndex => $nextInterval)
			{
				if ($duration >= $this->minDuration) { break; }
				if ($nextIndex <= $index) { continue; }
				if ($nextInterval['fromTime'] !== $toTime) { break; }

				$nextDuration = $this->getDuration($nextInterval);
				$toTime = $nextInterval['toTime'];

				$newGroup[$index]['toTime'] = $toTime;
				$duration += $nextDuration;

				unset($newGroup[$nextIndex]);
			}

			// merge with previous

			foreach (array_reverse($newGroup, true) as $previousIndex => $previousInterval)
			{
				if ($duration >= $this->minDuration) { break; }
				if ($previousIndex >= $index) { continue; }
				if ($previousInterval['toTime'] !== $fromTime) { break; }

				$previousDuration = $this->getDuration($previousInterval);
				$fromTime = $previousInterval['fromTime'];

				$newGroup[$index]['fromTime'] = $fromTime;
				$duration += $previousDuration;

				unset($newGroup[$previousIndex]);
			}
		}

		return $newGroup;
	}

	protected function mergeByMaxTimesCount(array $dayGroup)
	{
		if ($this->maxTimesCount === null) { return $dayGroup; }

		$count = count($dayGroup);

		if ($count <= $this->maxTimesCount) { return $dayGroup; }

		$mergeCount = 1;

		while (($count / ($mergeCount + 1)) > $this->maxTimesCount)
		{
			++$mergeCount;
		}

		$newGroup = $dayGroup;
		$leftCount = 0;

		foreach ($dayGroup as $index => $interval)
		{
			if (!isset($newGroup[$index])) { continue; }

			$leftCount = min($leftCount + $mergeCount, $count - $this->maxTimesCount);
			$duration = $this->getDuration($interval);
			$fromTime = $interval['fromTime'];
			$toTime = $interval['toTime'];

			// merge with next

			foreach ($newGroup as $nextIndex => $nextInterval)
			{
				if ($leftCount <= 0) { break; }
				if ($nextIndex <= $index) { continue; }
				if ($nextInterval['fromTime'] !== $toTime) { break; }

				$nextDuration = $this->getDuration($nextInterval);

				if ($duration + $nextDuration > $this->maxDuration) { break; }

				$toTime = $nextInterval['toTime'];
				$newGroup[$index]['toTime'] = $toTime;

				--$leftCount;
				--$count;

				unset($newGroup[$nextIndex]);
			}

			// merge with previous

			foreach (array_reverse($newGroup, true) as $previousIndex => $previousInterval)
			{
				if ($leftCount <= 0) { break; }
				if ($previousIndex >= $index) { continue; }
				if ($previousInterval['toTime'] !== $fromTime) { break; }

				$previousDuration = $this->getDuration($previousInterval);

				if ($duration + $previousDuration > $this->maxDuration) { break; }

				$fromTime = $previousInterval['fromTime'];
				$newGroup[$index]['fromTime'] = $fromTime;

				--$leftCount;
				--$count;

				unset($newGroup[$previousIndex]);
			}
		}

		return $newGroup;
	}

	protected function splitByDuration(array $dayGroup)
	{
		if ($this->maxDuration === null) { return $dayGroup; }

		$newGroup = [];

		foreach ($dayGroup as $interval)
		{
			$duration = $this->getDuration($interval);

			if ($duration <= $this->maxDuration)
			{
				$newGroup[] = $interval;
				continue;
			}

			$newDuration = $duration;

			do
			{
				$newDuration /= 2;
			}
			while ($newDuration > $this->maxDuration);

			$fromTime = Market\Data\Time::toNumber($interval['fromTime']);
			$toTime = Market\Data\Time::toNumber($interval['toTime']);

			for ($iteratorTime = $fromTime; $iteratorTime < $toTime; $iteratorTime += $newDuration)
			{
				$newGroup[] = [
					'date' => $interval['date'],
					'fromTime' => Market\Data\Time::fromNumber($iteratorTime),
					'toTime' => Market\Data\Time::fromNumber($iteratorTime + $newDuration),
				];
			}
		}

		return $newGroup;
	}

	protected function sliceInvalid(array $dayGroup)
	{
		$result = [];

		foreach ($dayGroup as $interval)
		{
			$fromTime = Market\Data\Time::toNumber($interval['fromTime']);
			$toTime = Market\Data\Time::toNumber($interval['toTime']);

			if ($fromTime < $toTime)
			{
				$result[] = $interval;
			}
		}

		return $result;
	}

	protected function makeDayGroups()
	{
		$groupDate = null;
		$group = null;
		$groupIntervals = [];

		foreach ($this->intervals as $interval)
		{
			if ($group === null)
			{
				$group = [];
				$groupDate = $interval['date'];
			}
			else if (Market\Data\Date::compare($interval['date'], $groupDate) !== 0)
			{
				$groupIntervals[] = $group;

				$group = [];
				$groupDate = $interval['date'];
			}

			$group[] = $interval;
		}

		if (!empty($group))
		{
			$groupIntervals[] = $group;
		}

		return $groupIntervals;
	}

	protected function getDuration($interval)
	{
		if (!isset($interval['fromTime'], $interval['toTime'])) { return null; }

		$fromParts = Market\Data\Time::toNumber($interval['fromTime']);
		$toParts = Market\Data\Time::toNumber($interval['toTime']);

		if ($fromParts === null || $toParts === null) { return null; }

		return $toParts - $fromParts;
	}
}