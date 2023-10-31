<?php
namespace Yandex\Market\Api\Business\Bids\Recommendations\Model;

use Yandex\Market\Api\Reference\Model;
use Yandex\Market\Data\Number;

class Price extends Model
{
	public function getCampaignId()
	{
		return (int)$this->getField('campaignId');
	}

	public function getPrice()
	{
		return Number::normalize($this->getField('price'));
	}
}