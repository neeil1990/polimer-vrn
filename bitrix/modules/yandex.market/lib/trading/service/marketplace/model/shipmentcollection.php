<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;

class ShipmentCollection extends Market\Api\Reference\Collection
{
	protected $paging;

	public static function getItemReference()
	{
		return Shipment::class;
	}

	/** @return Market\Api\Model\Paging|null */
	public function getPaging()
	{
		return $this->paging;
	}

	public function setPaging(Market\Api\Model\Paging $paging)
	{
		$this->paging = $paging;
	}
}