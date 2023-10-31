<?php

namespace Yandex\Market\Api\Partner\Reference;

use Bitrix\Main;
use Yandex\Market;

abstract class Request extends Market\Api\Reference\RequestClientTokenized
{
	protected $campaignId;

	public function getHost()
	{
		return 'api.partner.market.yandex.ru';
	}

	public function setCampaignId($campaignId)
	{
		$this->campaignId = $campaignId;
	}

	public function getCampaignId()
	{
		if ($this->campaignId === null)
		{
			throw new Main\SystemException('campaignId not set');
		}

		return (string)$this->campaignId;
	}

	protected function createLocker()
	{
		$key = $this->getHost() . '_' . $this->getOauthToken();
		$limit = 2;

		return new Market\Api\Locker($key, $limit);
	}
}