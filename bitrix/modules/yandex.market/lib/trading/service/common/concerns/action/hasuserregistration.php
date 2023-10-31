<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasUserRegistration
 * @method string getSiteId()
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingEntity\Reference\Order $order
 */
trait HasUserRegistration
{
	protected function needUserRegister()
	{
		$options = $this->provider->getOptions();

		if ($options instanceof TradingService\Common\Concerns\Options\UserRegistrationInterface)
		{
			$result = ($options->getUserRule() !== TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_ANONYMOUS);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	protected function configureUserRule(TradingEntity\Reference\User $user)
	{
		$options = $this->provider->getOptions();

		if (!($options instanceof TradingService\Common\Concerns\Options\UserRegistrationInterface)) { return; }

		$rule = $options->getUserRule();
		$behaviorMap = $this->getUserRuleBehaviorMap();

		if (!isset($behaviorMap[$rule])) { return; }

		$field = $behaviorMap[$rule];

		$user->searchOnly([$field]);
	}

	/** @deprecated */
	protected function filterUserData($userData)
	{
		$options = $this->provider->getOptions();

		if ($options instanceof TradingService\Common\Concerns\Options\UserRegistrationInterface)
		{
			$rule = $options->getUserRule();
			$result = $this->filterUserDataByRule($userData, $rule);
		}
		else
		{
			$result = $userData;
		}

		return $result;
	}

	protected function filterUserDataByRule($userData, $rule)
	{
		$disabledFields = $this->getUserRuleDisabledFields($rule);

		return array_diff_key($userData, $disabledFields);
	}

	protected function getUserRuleDisabledFields($rule)
	{
		$behaviorMap = $this->getUserRuleBehaviorMap();

		if (!isset($behaviorMap[$rule])) { return []; }

		$fieldsMap = $this->getUserRuleFieldsMap();
		$matchBehavior = $behaviorMap[$rule];
		$result = [];

		foreach ($fieldsMap as $behavior => $fields)
		{
			if ($behavior === $matchBehavior) { continue; }

			$result += array_fill_keys($fields, true);
		}

		return $result;
	}

	protected function getUserRuleFieldsMap()
	{
		return [
			'NAME' => [ 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME' ],
			'PHONE' => [ 'PHONE' ],
			'EMAIL' => [ 'EMAIL' ],
		];
	}

	protected function getUserRuleBehaviorMap()
	{
		return [
			TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_MATCH_NAME => 'NAME',
			TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_MATCH_EMAIL => 'EMAIL',
			TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_MATCH_PHONE => 'PHONE',
			TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_MATCH_ID => 'ID',
		];
	}

	protected function registerUser(TradingEntity\Reference\User $user)
	{
		$installResult = $user->install([
			'SITE_ID' => $this->getSiteId(),
		]);

		Market\Result\Facade::handleException($installResult);
	}

	protected function attachUserToGroup(TradingEntity\Reference\User $user)
	{
		$groupRegistry = $this->environment->getUserGroupRegistry();
		$group = $groupRegistry->getGroup($this->provider->getServiceCode(), $this->getSiteId());

		if ($group->isInstalled())
		{
			$user->attachGroup($group->getId());
		}
	}

	protected function changeOrderUser(TradingEntity\Reference\User $user)
	{
		$setResult = $this->order->setUserId($user->getId());

		Market\Result\Facade::handleException($setResult);
	}
}