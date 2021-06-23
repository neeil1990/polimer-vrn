<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class BuyerProfileEdit extends Market\Ui\Reference\Page
{
	public function show()
	{
		$environment = Market\Trading\Entity\Manager::createEnvironment();
		$profileType = $environment->getProfile();
		$profileId = $this->getProfileId();

		if ($profileId === null)
		{
			$userId = $this->getUserId();
			$personTypeId = $this->getPersonTypeId();
			$service = $this->getService();
			$serviceValues = $service->getInfo()->getProfileValues();

			$profileId = $profileType->createProfile($userId, $personTypeId, $serviceValues);
		}

		$editUrl = $profileType->getEditUrl($profileId);

		if ($editUrl === null)
		{
			$message = Market\Config::getLang('ADMIN_TRADING_BUYER_PROFILE_EDIT_NO_EDIT_URL');
			throw new Main\SystemException($message);
		}

		LocalRedirect($editUrl);
	}

	protected function getUserId()
	{
		$result = (string)$this->request->getQuery('userId');

		if ($result === '')
		{
			$message = Market\Config::getLang('ADMIN_TRADING_BUYER_PROFILE_EDIT_NOT_SET_USER_ID');

			throw new Main\ArgumentException($message);
		}

		return $result;
	}

	protected function getPersonTypeId()
	{
		$result = (string)$this->request->getQuery('personType');

		if ($result === '')
		{
			$message = Market\Config::getLang('ADMIN_TRADING_BUYER_PROFILE_EDIT_NOT_SET_PERSON_TYPE');

			throw new Main\ArgumentException($message);
		}

		return $result;
	}

	protected function getService()
	{
		$code = $this->getServiceCode();

		return Market\Trading\Service\Manager::createProvider($code);
	}

	protected function getServiceCode()
	{
		$result = (string)$this->request->getQuery('service');

		if ($result === '')
		{
			$message = Market\Config::getLang('ADMIN_TRADING_BUYER_PROFILE_EDIT_NOT_SET_SERVICE');

			throw new Main\ArgumentException($message);
		}

		return $result;
	}

	protected function getProfileId()
	{
		$result = (string)$this->request->getQuery('id');

		if ($result === '')
		{
			$result = null;
		}

		return $result;
	}
}
