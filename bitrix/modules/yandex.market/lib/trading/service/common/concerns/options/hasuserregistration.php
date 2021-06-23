<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

trait HasUserRegistration
{
	public function getUserRule()
	{
		return (string)$this->getValue('ORDER_USER_RULE') ?: UserRegistration::getDefault();
	}

	protected function getOrderUserRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return UserRegistration::getFields($environment, $siteId);
	}
}