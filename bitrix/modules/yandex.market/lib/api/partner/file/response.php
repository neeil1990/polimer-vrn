<?php

namespace Yandex\Market\Api\Partner\File;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Reference\ResponseWithResult
{
	public function getType()
	{
		return $this->getField('type');
	}

	public function getContents()
	{
		return $this->getField('contents');
	}
}