<?php

namespace Yandex\Market\Error;

class Base
{
	const XML_NODE_VALIDATE_EMPTY = 1;
	const XML_NODE_TAG_EMPTY = 2;
	const XML_NODE_HASH_COLLISION = 3;

	protected $code;
	protected $message;
	protected $customData;
	protected $isCritical = false;

	public function __construct($message, $code = 0, $customData = null)
	{
		$this->message = $message;
		$this->code = $code;
		$this->customData = $customData;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function getCustomData()
	{
		return $this->customData;
	}

	public function getUniqueKey()
	{
		return $this->code . '|' . $this->message;
	}

	public function __toString()
	{
		return $this->getMessage();
	}

	public function isCritical()
	{
		return $this->isCritical;
	}

	public function markCritical()
	{
		$this->isCritical = true;
	}
}