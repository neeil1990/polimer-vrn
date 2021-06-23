<?php

namespace Yandex\Market\Exceptions;

use Yandex\Market;
use Bitrix\Main;

class Facade
{
	public static function fromApplication($exceptionClassName = Main\SystemException::class)
	{
		global $APPLICATION;

		$exception = $APPLICATION->GetException();
		$message = $exception ? $exception->GetString() : 'Unknown application exception';

		return new $exceptionClassName($message);
	}
}