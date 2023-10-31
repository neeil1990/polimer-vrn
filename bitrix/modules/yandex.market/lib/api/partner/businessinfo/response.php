<?php

namespace Yandex\Market\Api\Partner\BusinessInfo;

use Yandex\Market;

/** @deprecated */
class Response extends Market\Api\Reference\ResponseWithResult
{
	public function getBusinessId()
	{
		return (int)$this->getField('result.businessId');
	}

	public function getName()
	{
		return (string)$this->getField('result.name');
	}

	/** @return Model\CampaignCollection */
	public function getCampaigns()
	{
		return $this->getChildCollection('result.campaigns');
	}

	protected function getChildCollectionReference()
	{
		return [
			'result.campaigns' => Model\CampaignCollection::class,
		];
	}
}