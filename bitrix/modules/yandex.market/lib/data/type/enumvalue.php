<?php

namespace Yandex\Market\Data\Type;

class EnumValue
{
	public $code;
	public $title;

	public function __construct($code, $title)
	{
		$this->code = $code;
		$this->title = $title;
	}

	public function __toString()
	{
		return $this->title;
	}
}