<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpReturnDocTypeMismatchInspection */
namespace Yandex\Market\Api\Business\Bids\Info;

use Yandex\Market\Api;
use Yandex\Market\Reference\Concerns;

class Response extends Api\Reference\ResponseWithResult
{
	use Concerns\HasOnce;

	/** @return Model\BidCollection */
	public function getBids()
	{
		return $this->getRequiredCollection('result.bids');
	}

	/** @return Api\Model\Paging */
	public function getPaging()
	{
		return $this->once('getPaging', function() {
			$paging = $this->getChildModel('result.paging');

			return $paging !== null ? $this->getRequiredModel('result.paging') : new Api\Model\Paging();
		});
	}

	protected function getChildCollectionReference()
	{
		return [
			'result.bids' => Model\BidCollection::class,
		];
	}

	protected function getChildModelReference()
	{
		return [
			'result.paging' => Api\Model\Paging::class,
		];
	}
}