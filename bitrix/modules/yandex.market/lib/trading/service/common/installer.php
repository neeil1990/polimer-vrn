<?php

namespace Yandex\Market\Trading\Service\Common;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

abstract class Installer extends Market\Trading\Service\Reference\Installer
{
	public function install(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		$this->installRoute($environment, $siteId, $context);
		$this->installUserEnvironment($environment, $siteId, $context);
	}

	protected function installRoute(TradingEntity\Reference\Environment $environment, $siteId, array $context)
	{
		$route = $environment->getRoute();

		$route->installPublic($siteId);
	}

	protected function installUserEnvironment(TradingEntity\Reference\Environment $environment, $siteId, array $context)
	{
		$group = $this->installUserGroup($environment, $siteId);
		$user = $this->installAnonymousUser($environment, $siteId);

		$this->attachUserToGroup($user, $group);
	}

	protected function installUserGroup(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$userGroup = $this->getUserGroup($environment, $siteId);

		if (!$userGroup->isInstalled())
		{
			$data = $this->getUserGroupData();
			$installResult = $userGroup->install($data);

			Market\Result\Facade::handleException($installResult);
		}

		return $userGroup;
	}

	protected function getUserGroup(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$userGroupRegistry = $environment->getUserGroupRegistry();

		return $userGroupRegistry->getGroup($this->provider->getServiceCode(), $siteId);
	}

	protected function getUserGroupData()
	{
		return $this->provider->getInfo()->getUserGroupData();
	}

	protected function installAnonymousUser(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$user = $this->getAnonymousUser($environment, $siteId);

		$user->checkInstall();

		if (!$user->isInstalled())
		{
			$data = $this->getAnonymousUserData();
			$installResult = $user->install($data);

			Market\Result\Facade::handleException($installResult);
		}

		return $user;
	}

	protected function getAnonymousUser(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$userRegistry = $environment->getUserRegistry();

		return $userRegistry->getAnonymousUser($this->provider->getServiceCode(), $siteId);
	}

	protected function getAnonymousUserData()
	{
		return $this->provider->getInfo()->getAnonymousUserData();
	}

	protected function attachUserToGroup(TradingEntity\Reference\User $user, TradingEntity\Reference\UserGroup $group)
	{
		$groupId = $group->getId();
		$attachResult = $user->attachGroup($groupId);

		Market\Result\Facade::handleException($attachResult);
	}

	public function uninstall(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		// nothing
	}

	protected function uninstallRoute(TradingEntity\Reference\Environment $environment, $siteId, array $context)
	{
		if (!$context['SITE_USED'])
		{
			$route = $environment->getRoute();
			$route->uninstallPublic($siteId);
		}
	}

	public function migrate(TradingEntity\Reference\Environment $environment, $siteId, TradingService\Reference\Provider $provider = null, array $context = [])
	{
		$this->migrateUserGroup($environment, $siteId, $provider);
		$this->migrateAnonymousUser($environment, $siteId, $provider);
		$this->migrateProfiles($environment, $siteId, $provider);
	}

	protected function migrateUserGroup(TradingEntity\Reference\Environment $environment, $siteId, TradingService\Reference\Provider $provider = null)
	{
		if ($provider === null) { $provider = $this->provider; }

		$userGroup = $this->getUserGroup($environment, $siteId);

		if (!$userGroup->isInstalled()) { return; }

		$code = $provider->getServiceCode();
		$data = $provider->getInfo()->getUserGroupData();

		$this->updateUserGroupCode($userGroup, $code);
		$this->updateUserGroupData($userGroup, $data);
	}

	protected function updateUserGroupCode(TradingEntity\Reference\UserGroup $userGroup, $code)
	{
		if ($code === $this->provider->getServiceCode()) { return; }

		$migrateResult = $userGroup->migrate($code);

		Market\Result\Facade::handleException($migrateResult);
	}

	protected function updateUserGroupData(TradingEntity\Reference\UserGroup $userGroup, $data)
	{
		if (empty($data)) { return; }

		$updateResult = $userGroup->update($data);

		Market\Result\Facade::handleException($updateResult);
	}

	protected function migrateAnonymousUser(TradingEntity\Reference\Environment $environment, $siteId, TradingService\Reference\Provider $provider = null)
	{
		if ($provider === null) { $provider = $this->provider; }

		$user = $this->getAnonymousUser($environment, $siteId);

		if (!$user->isInstalled()) { return; }

		$code = $provider->getServiceCode();
		$data = $provider->getInfo()->getAnonymousUserData();

		$this->updateUserCode($user, $code);
		$this->updateUserData($user, $data);
	}

	protected function updateUserCode(TradingEntity\Reference\User $user, $code)
	{
		if ($code === $this->provider->getServiceCode()) { return; }

		$migrateResult = $user->migrate($code);

		Market\Result\Facade::handleException($migrateResult);
	}

	protected function updateUserData(TradingEntity\Reference\User $user, $data)
	{
		if (empty($data)) { return; }

		$updateResult = $user->update($data);

		Market\Result\Facade::handleException($updateResult);
	}

	protected function migrateProfiles(TradingEntity\Reference\Environment $environment, $siteId, TradingService\Reference\Provider $provider = null)
	{
		if ($provider === null) { $provider = $this->provider; }

		$profileValues = $provider->getInfo()->getProfileValues();
		$user = $this->getAnonymousUser($environment, $siteId);
		$profileEntity =  $environment->getProfile();
		$personTypeEntity = $environment->getPaySystem();

		if (empty($profileValues) || !$user->isInstalled()) { return; }

		$userId = $user->getId();

		foreach ($personTypeEntity->getEnum($siteId) as $personTypeOption)
		{
			foreach ($profileEntity->getEnum($userId, $personTypeOption['ID']) as $profileOption)
			{
				$updateResult = $profileEntity->update($profileOption['ID'], $profileValues);

				Market\Result\Facade::handleException($updateResult);
			}
		}
	}
}