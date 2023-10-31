<?php
namespace Yandex\Market\Data\Run;

use Yandex\Market\Result;

interface Processor
{
	const ACTION_FULL = 'full';
	const ACTION_CHANGE = 'change';
	const ACTION_REFRESH = 'refresh';

	/** @return Result\StepProcessor */
	public function run($action = self::ACTION_FULL);
}