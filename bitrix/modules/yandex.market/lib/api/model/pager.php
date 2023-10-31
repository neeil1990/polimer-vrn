<?php

namespace Yandex\Market\Api\Model;

use Yandex\Market;

class Pager extends Market\Api\Reference\Model
{
	public function hasNext()
	{
		return ($this->getCurrentPage() < $this->getPagesCount());
	}

	public function getCurrentPage()
	{
		return $this->getField('currentPage');
	}

	public function getPagesCount()
	{
		return $this->getField('pagesCount');
	}

	public function getPageSize()
	{
		return $this->getField('pageSize');
	}

	public function getTotal()
	{
		return $this->getField('total');
	}
}
