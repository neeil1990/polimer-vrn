<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Yandex\Market;
use Bitrix\Main;

abstract class AbstractRequest extends Market\Api\Reference\Model
{
	public function getRaw()
	{
		return $this->fields;
	}
}