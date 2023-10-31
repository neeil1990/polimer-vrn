<?php

namespace Yandex\Market\Trading\Settings;

use Bitrix\Main;
use Yandex\Market;

class Model extends Market\Reference\Storage\Model
{
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getName()
	{
		return $this->getField('NAME');
	}

	public function getValue()
	{
		return $this->getField('VALUE');
	}
}