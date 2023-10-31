<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpReturnDocTypeMismatchInspection */
namespace Yandex\Market\Api\Business\Bids\Recommendations\Model;

use Yandex\Market\Api\Reference\Model;

class Recommendation extends Model
{
	public function getSku()
	{
		return (string)$this->getRequiredField('sku');
	}

	public function getBid()
	{
		return (int)$this->getRequiredField('bid');
	}

	/** @return BidCollection */
	public function getBidRecommendations()
	{
		return $this->getChildCollection('bidRecommendations');
	}

	/** @return PriceCollection */
	public function getPriceRecommendations()
	{
		return $this->getChildCollection('priceRecommendations');
	}

	protected function getChildCollectionReference()
	{
		return [
			'bidRecommendations' => BidCollection::class,
			'priceRecommendations' => PriceCollection::class,
		];
	}
}