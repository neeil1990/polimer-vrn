<?php
namespace Yandex\Market\Api\Business\Bids\Recommendations\Model;

use Yandex\Market\Api\Reference\Collection;

/** @property Price[] $collection */
class PriceCollection extends Collection
{
	public static function getItemReference()
	{
		return Price::class;
	}
}