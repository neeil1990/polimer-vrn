<?php

namespace Yandex\Market\Api\Model;

use Yandex\Market;

class Paging extends Market\Api\Reference\Model
{
	public function hasNext()
	{
		return ((string)$this->getNextPageToken() !== '');
	}

	public function getNextPageToken()
	{
		return $this->getField('nextPageToken');
	}
}