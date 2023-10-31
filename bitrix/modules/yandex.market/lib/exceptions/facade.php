<?php

namespace Yandex\Market\Exceptions;

use Bitrix\Main;

class Facade
{
	public static function fromApplication()
	{
		global $APPLICATION;

		$exception = $APPLICATION->GetException();
		$message = $exception ? $exception->GetString() : 'Unknown application exception';

		return new Main\SystemException($message);
	}
}