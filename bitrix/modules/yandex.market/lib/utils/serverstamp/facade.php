<?php

namespace Yandex\Market\Utils\ServerStamp;

class Facade
{
	public static function reset()
	{
		$controller = new Controller();
		$controller->reset();
	}

	public static function check()
	{
		$controller = new Controller();
		$controller->check();
	}
}