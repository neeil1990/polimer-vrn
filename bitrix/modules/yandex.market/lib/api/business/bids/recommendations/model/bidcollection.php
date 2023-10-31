<?php
namespace Yandex\Market\Api\Business\Bids\Recommendations\Model;

use Yandex\Market\Api\Reference\Collection;

/** @property Bid[] $collection */
class BidCollection extends Collection
{
	public static function getItemReference()
	{
		return Bid::class;
	}
}