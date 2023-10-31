<?php

namespace Yandex\Market\Api\Model;

use Bitrix\Main;
use Yandex\Market;

class OutletCollection extends Market\Api\Reference\Collection
{
	protected $paging;

	public static function getItemReference()
	{
		return Outlet::class;
	}

	/** @return Paging|null */
	public function getPaging()
	{
		return $this->paging;
	}

	public function setPaging(Paging $paging)
	{
		$this->paging = $paging;
	}
}
