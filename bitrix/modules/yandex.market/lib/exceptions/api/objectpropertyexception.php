<?php

namespace Yandex\Market\Exceptions\Api;

class ObjectPropertyException extends InvalidOperation
{
	protected $parameter;

	public function __construct($parameter, \Exception $previous = null)
	{
		$this->parameter = $parameter;
		$message = sprintf('%s missing', $parameter);

		parent::__construct($message, $previous);
	}

	public function getParameter()
	{
		return $this->parameter;
	}
}