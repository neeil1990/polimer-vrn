<?php

namespace Yandex\Market\Api\Model;

use Bitrix\Main;
use Yandex\Market;

class OrderCollection extends Market\Api\Reference\Collection
{
	protected $pager;

	public static function getItemReference()
	{
		return Order::class;
	}

	/**
	 * @return Pager|null
	 */
	public function getPager()
	{
		return $this->pager;
	}

	public function setPager(Pager $pager)
	{
		$this->pager = $pager;
	}
}
