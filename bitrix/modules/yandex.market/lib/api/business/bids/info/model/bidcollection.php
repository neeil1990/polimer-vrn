<?php
namespace Yandex\Market\Api\Business\Bids\Info\Model;

use Yandex\Market\Api\Reference\Collection;

/** @property Bid[] $collection */
class BidCollection extends Collection
{
	public static function getItemReference()
	{
		return Bid::class;
	}

	public function skus()
	{
		$result = [];

		foreach ($this->collection as $bid)
		{
			$result[] = $bid->getSku();
		}

		return $result;
	}
}