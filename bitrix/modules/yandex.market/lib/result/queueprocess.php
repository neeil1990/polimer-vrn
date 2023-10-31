<?php

namespace Yandex\Market\Result;

use Bitrix\Main;

class QueueProcess extends Base
{
	protected $offset;
	protected $tickCount = 0;

	public function tick()
	{
		++$this->tickCount;
	}

	public function getTickCount()
	{
		return $this->tickCount;
	}

	public function hasInterruption()
	{
		return $this->offset !== null;
	}

	public function interrupt($offset)
	{
		$this->offset = $offset;
	}

	public function getOffset()
	{
		return $this->offset;
	}
}