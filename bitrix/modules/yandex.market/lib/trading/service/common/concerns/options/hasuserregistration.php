<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

trait HasUserRegistration
{
	public function resetUserRule()
	{
		$this->values['ORDER_USER_RULE'] = $this->getUserRuleDefault();

		return $this->getUserRule();
	}

	public function getUserRule()
	{
		return (string)$this->getValue('ORDER_USER_RULE') ?: $this->getUserRuleDefault();
	}

	protected function getUserRuleDefault()
	{
		return UserRegistrationInterface::USER_RULE_MATCH_ANY;
	}

	protected function getUserRuleDisabled()
	{
		return [];
	}

	protected function getOrderUserRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$fields = UserRegistration::getFields($environment, $siteId);

		return $this->extendOrderUserRuleFields($fields);
	}

	protected function extendOrderUserRuleFields(array $fields)
	{
		if (isset($fields['ORDER_USER_RULE']))
		{
			$fields['ORDER_USER_RULE']['SETTINGS']['DEFAULT_VALUE'] = $this->getUserRuleDefault();
		}

		if (isset($fields['ORDER_USER_RULE']['VALUES']))
		{
			$disabled = $this->getUserRuleDisabled();
			$disabledMap = array_flip($disabled);

			$fields['ORDER_USER_RULE']['VALUES'] = array_filter($fields['ORDER_USER_RULE']['VALUES'], static function($option) use ($disabledMap) {
				return !isset($disabledMap[$option['ID']]);
			});
		}

		return $fields;
	}
}