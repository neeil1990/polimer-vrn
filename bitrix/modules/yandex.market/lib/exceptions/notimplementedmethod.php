<?php

namespace Yandex\Market\Exceptions;

use Bitrix\Main;

class NotImplementedMethod extends NotImplemented
{
	public function __construct($className = '', $method = '', \Exception $previous = null)
	{
		parent::__construct('Method "' . $method . '" not implemented for ' . $className, $previous);
	}
}