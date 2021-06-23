<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class SaveBuyerProfile
{
	protected $provider;
	protected $environment;
	protected $userId;
	protected $personType;
	protected $profileName;
	protected $profileValues;

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		$userId,
		$personType,
		$profileName,
		array $profileValues
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
		$this->userId = $userId;
		$this->personType = $personType;
		$this->profileName = $profileName;
		$this->profileValues = $profileValues;
	}

	public function execute()
	{
		if (!$this->need()) { return; }

		$profileId = $this->search();

		if ($profileId !== null)
		{
			$this->update($profileId);
		}
		else
		{
			$this->add();
		}
	}

	protected function need()
	{
		$rule = $this->getRule();

		return (string)$rule !== '' && $this->userId !== null && !empty($this->profileValues);
	}

	protected function search()
	{
		$rule = $this->getRule();

		if ($rule === TradingService\Common\Concerns\Options\BuyerProfileInterface::BUYER_PROFILE_RULE_NEW)
		{
			$result = null;
		}
		else if ($rule === TradingService\Common\Concerns\Options\BuyerProfileInterface::BUYER_PROFILE_RULE_FIRST)
		{
			$enum = $this->environment->getProfile()->getEnum($this->userId, $this->personType);
			$first = reset($enum);

			$result = $first !== false ? $first['ID'] : null;
		}
		else if ($rule === TradingService\Common\Concerns\Options\BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_NAME)
		{
			$enum = $this->environment->getProfile()->getEnum($this->userId, $this->personType);
			$enumMap = array_column($enum, 'VALUE', 'ID');
			$enumKey = array_search($this->profileName, $enumMap, true);

			$result = $enumKey !== false ? $enumKey : null;
		}
		else
		{
			$meaningfulFields = $this->getMeaningfulFields($rule);
			$searchValues = $this->profileValues;

			if ($meaningfulFields !== null)
			{
				$propertiesForCompare = $this->getMeaningfulProperties($meaningfulFields);
				$searchValues = array_intersect_key($searchValues, array_flip($propertiesForCompare));
			}

			$result = !empty($searchValues)
				? $this->environment->getProfile()->searchRaw($this->userId, $this->personType, $searchValues)
				: null;
		}

		return $result;
	}

	protected function getMeaningfulFields($rule)
	{
		switch ($rule)
		{
			case TradingService\Common\Concerns\Options\BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_EMAIL:
				$result = [
					'EMAIL' => true,
				];
			break;

			case TradingService\Common\Concerns\Options\BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_PHONE:
				$result = [
					'PHONE' => true,
				];
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

	protected function getMeaningfulProperties($meaningfulFields)
	{
		$result = [];

		foreach ($this->environment->getProperty()->getEnum($this->personType) as $option)
		{
			if (isset($option['TYPE'], $meaningfulFields[$option['TYPE']]))
			{
				$result[] = $option['ID'];
			}
		}

		return $result;
	}

	protected function add()
	{
		$profile = $this->environment->getProfile();

		$profile->addRaw(
			$this->userId,
			$this->personType,
			$this->profileName,
			$this->profileValues
		);
	}

	protected function update($profileId)
	{
		$profile = $this->environment->getProfile();

		$profile->updateRaw(
			$profileId,
			$this->profileName,
			$this->profileValues
		);
	}

	protected function getRule()
	{
		$options = $this->provider->getOptions();

		return $options instanceof TradingService\Common\Concerns\Options\BuyerProfileInterface
			? $options->getBuyerProfileRule()
			: null;
	}
}