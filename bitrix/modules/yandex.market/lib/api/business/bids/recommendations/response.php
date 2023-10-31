<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpReturnDocTypeMismatchInspection */
namespace Yandex\Market\Api\Business\Bids\Recommendations;

use Yandex\Market\Api;

class Response extends Api\Reference\ResponseWithResult
{
	/** @return Model\RecommendationCollection */
	public function getRecommendations()
	{
		return $this->getRequiredCollection('result.recommendations');
	}

	protected function getChildCollectionReference()
	{
		return [
			'result.recommendations' => Model\RecommendationCollection::class,
		];
	}
}