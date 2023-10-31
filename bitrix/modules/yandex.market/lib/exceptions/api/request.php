<?php

namespace Yandex\Market\Exceptions\Api;

use Bitrix\Main;

class Request extends Main\SystemException
{
	protected $errorCode;

	public function __construct($message = "", $errorCode = null, $file = "", $line = 0, \Exception $previous = null)
	{
		parent::__construct($message, 0, $file, $line, $previous);
		$this->errorCode = $errorCode;
	}

	public function getErrorCode()
	{
		return $this->errorCode;
	}
}