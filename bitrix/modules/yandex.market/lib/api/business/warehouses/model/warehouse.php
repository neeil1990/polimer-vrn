<?php

namespace Yandex\Market\Api\Business\Warehouses\Model;

use Yandex\Market;

class Warehouse extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getRequiredField('id');
	}

	public function getName()
	{
		return (string)$this->getRequiredField('name');
	}

	public function getCampaignId()
	{
		return (int)$this->getRequiredField('campaignId');
	}
}