<?php

namespace Yandex\Market\Exceptions;

use Bitrix\Main;

class NotImplementedEntity extends NotImplemented
{
	public function __construct($className = '', $entityName = '', \Exception $previous = null)
	{
		parent::__construct('Entity "' . $entityName . '" not implemented for ' . $className, $previous);
	}
}