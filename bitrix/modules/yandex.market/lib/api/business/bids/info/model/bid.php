<?php
namespace Yandex\Market\Api\Business\Bids\Info\Model;

use Yandex\Market\Api\Reference\Model;

class Bid extends Model
{
	public function getSku()
	{
		return (string)$this->getRequiredField('sku');
	}

	public function getBid()
	{
		return (int)$this->getRequiredField('bid');
	}
}