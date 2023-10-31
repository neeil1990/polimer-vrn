<?php
namespace Yandex\Market\Api\Campaigns\Model;

use Yandex\Market\Api;

/** @property Campaign[] $collection */
class CampaignCollection extends Api\Reference\Collection
{
	public static function getItemReference()
	{
		return Campaign::class;
	}

	public function getItemByCampaignId($campaignId)
	{
		$result = null;

		foreach ($this->collection as $campaign)
		{
			if ($campaign->getId() === (int)$campaignId)
			{
				$result = $campaign;
				break;
			}
		}

		return $result;
	}

	public function sameBusiness($businessId)
	{
		$result = new static();

		foreach ($this->collection as $campaign)
		{
			if ($campaign->getBusiness()->getId() === (int)$businessId)
			{
				$result->addItem($campaign);
				$campaign->setCollection($result);
			}
		}

		return $result;
	}
}