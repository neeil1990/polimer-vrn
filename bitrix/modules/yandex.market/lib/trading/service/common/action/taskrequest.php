<?php

namespace Yandex\Market\Trading\Service\Common\Action;

class TaskRequest extends SendRequest
{
	public function isAutoSubmit()
	{
		return true;
	}

	public function getImmediate()
	{
		return false;
	}
}