<?php

namespace Yandex\Market\Trading\Facade;

use Yandex\Market;
use Bitrix\Main;

class AnonymousUser
{
	public static function getUsedIds()
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = 86400;
		$cacheTable = Market\Trading\Setup\Table::getTableName();
		$cacheId = $cacheTable . ':anonymous_user_used';

		if ($cache->read($cacheTtl, $cacheId, $cacheTable))
		{
			$result = $cache->get($cacheId);
		}
		else
		{
			$result = static::fetchUsedIds();

			$cache->set($cacheId, $result);
		}

		return $result;
	}

	protected static function fetchUsedIds()
	{
		$result = [];
		$collection = Market\Trading\Setup\Collection::loadByFilter([
			'filter' => [ '=ACTIVE' => Market\Trading\Setup\Table::BOOLEAN_Y ],
		]);

		/** @var Market\Trading\Setup\Model $setup */
		foreach ($collection as $setup)
		{
			$serviceCode = $setup->getServiceCode();
			$siteId = $setup->getSiteId();
			$user = $setup->getEnvironment()->getUserRegistry()->getAnonymousUser($serviceCode, $siteId);

			if ($user->isInstalled())
			{
				$result[] = $user->getId();
			}
		}

		return array_unique($result);
	}
}