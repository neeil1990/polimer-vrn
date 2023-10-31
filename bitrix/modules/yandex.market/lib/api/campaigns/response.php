<?php
/** @noinspection PhpReturnDocTypeMismatchInspection */
/** @noinspection PhpIncompatibleReturnTypeInspection */
namespace Yandex\Market\Api\Campaigns;

use Yandex\Market;

class Response extends Market\Api\Reference\Response
{
	/** @return Model\CampaignCollection */
	public function getCampaigns()
	{
		return $this->getRequiredCollection('campaigns');
	}

	/** @return Market\Api\Model\Pager */
	public function getPager()
	{
		return $this->getRequiredModel('pager');
	}

	protected function getChildModelReference()
	{
		return [
			'pager' => Market\Api\Model\Pager::class,
		];
	}

	protected function getChildCollectionReference()
	{
		return [
			'campaigns' => Model\CampaignCollection::class,
		];
	}
}