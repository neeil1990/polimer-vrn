<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

trait HasBuyerProfile
{
	public function getBuyerProfileRule()
	{
		return (string)$this->getValue('BUYER_PROFILE_RULE');
	}

	protected function getBuyerProfileRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return BuyerProfile::getFields($environment, $siteId);
	}
}