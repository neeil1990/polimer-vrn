<?php
namespace Yandex\Market\Api\Business\Bids\Recommendations\Model;

use Yandex\Market\Api\Reference\Collection;

/** @property Recommendation[] $collection */
class RecommendationCollection extends Collection
{
	public static function getItemReference()
	{
		return Recommendation::class;
	}
}