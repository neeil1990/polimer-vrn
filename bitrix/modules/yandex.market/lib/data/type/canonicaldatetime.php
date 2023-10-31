<?php

namespace Yandex\Market\Data\Type;

use Bitrix\Main;

class CanonicalDateTime extends Main\Type\DateTime
{
	public function __construct($time = null, $format = null, \DateTimeZone $timezone = null)
	{
		if ($timezone === null) { $timezone = new \DateTimeZone('UTC'); }

		parent::__construct($time, $format, $timezone);
	}

	public function setServerTimeZone()
	{
		return parent::setDefaultTimeZone();
	}

	public function setDefaultTimeZone()
	{
		$this->setTimezone(new \DateTimeZone('UTC'));
		return $this;
	}
}