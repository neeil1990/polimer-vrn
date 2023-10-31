<?php

namespace Yandex\Market\Migration;

use Bitrix\Main;
use Yandex\Market;

class Agent
{
	public static function canRestore($exception)
	{
		return false;
	}

	public static function check()
	{
		return false;
	}

	public static function reset()
	{
		Market\Reference\Agent\Controller::deleteAll();
		Market\Reference\Agent\Controller::updateRegular();
	}
}