<?php

namespace Yandex\Market\Utils;

class DummyUser extends \CUser
{
	public function GetParam($name)
	{
		if ($name === 'USER_ID')
		{
			return '0';
		}

		return null;
	}

	public function SetParam($name, $value)
	{
		// nothing
	}
}