<?php

namespace Yandex\Market\Data;

use Yandex\Market;
use Bitrix\Main;

class SiteDomain
{
	protected static $domainToIdCache = [];

	public static function getSite($domain, $path = '')
	{
		$result = null;
		$domain = trim($domain);
		$path = trim($path);
		$cacheKey = $domain . ':' . $path;

		if ($domain === '')
		{
			// nothing
		}
		else if (array_key_exists($cacheKey, static::$domainToIdCache))
		{
			$result = static::$domainToIdCache[$cacheKey];
		}
		else
		{
			$result = static::getSiteFromDomainTable($domain, $path);

			if ($result === null)
			{
				$result = static::getSiteFromSiteTable($domain, $path);
			}

			static::$domainToIdCache[$cacheKey] = $result;
		}

		return $result;
	}

	protected static function getSiteFromSiteTable($domain, $path)
	{
		$result = null;

		$entity = Main\SiteTable::getEntity();
		$connection = $entity->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$domainVariants = static::splitDomain($domain);

		$query = Main\SiteTable::getList([
			'filter' => [
				'=SERVER_NAME' => $domainVariants,
				'=ACTIVE' => 'Y',
			],
			'select' => [ 'LID', 'DIR' ],
			'order' => [
				'DIR_LENGTH' => 'DESC',
				'SORT' => 'ASC',
			],
			'runtime' => [
				new Main\Entity\ExpressionField('DIR_LENGTH', $sqlHelper->getLengthFunction('%s'), [ 'DIR' ]),
			],
		]);

		while ($row = $query->fetch())
		{
			if (static::compareDir($row['DIR'], $path) === 0)
			{
				$result = (string)$row['LID'];
				break;
			}
		}

		return $result;
	}

	protected static function getSiteFromDomainTable($domain, $path)
	{
		$result = null;

		$entity = Main\SiteTable::getEntity();
		$connection = $entity->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$domainVariants = static::splitDomain($domain);
		$domainVariantsEncoded = array_map(
			function($domain) { return Domain::encode($domain); },
			$domainVariants
		);

		$query = Main\SiteDomainTable::getList([
			'filter' => [
				'=DOMAIN' => $domainVariantsEncoded,
				'=SITE.ACTIVE' => 'Y',
			],
			'select' => [ 'LID', 'DIR' => 'SITE.DIR' ],
			'order' => [
				'DIR_LENGTH' => 'DESC',
				'SITE.SORT' => 'ASC',
			],
			'runtime' => [
				new Main\Entity\ExpressionField('DIR_LENGTH', $sqlHelper->getLengthFunction('%s'), [ 'SITE.DIR' ])
			],
		]);

		while ($row = $query->fetch())
		{
			if (static::compareDir($row['DIR'], $path) === 0)
			{
				$result = (string)$row['LID'];
				break;
			}
		}

		return $result;
	}

	public static function getHost($siteId)
	{
		$result = static::getHostFromSiteTable($siteId);

		if ($result === null)
		{
			$result = static::getHostFromDomainTable($siteId);
		}

		if ($result !== null)
		{
			$result = rtrim($result, '/');
		}

		return $result;
	}

	protected static function getHostFromSiteTable($siteId)
	{
		$result = null;

		$query = Main\SiteTable::getList([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'SERVER_NAME' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$serverName = trim($row['SERVER_NAME']);

			if ($serverName !== '')
			{
				$result = $serverName;
			}
		}

		return $result;
	}

	protected static function getHostFromDomainTable($siteId)
	{
		$result = null;

		$query = Main\SiteDomainTable::getList([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'DOMAIN' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$domain = trim($row['DOMAIN']);

			if ($domain !== '')
			{
				$result = Domain::decode($domain);
			}
		}

		return $result;
	}

	protected static function splitDomain($domain)
	{
		$parts = explode('.', $domain);
		$result = [];

		if (count($parts) > 2)
		{
			$lastVariant = null;

			foreach (array_reverse($parts) as $part)
			{
				if ($lastVariant === null)
				{
					$lastVariant = $part;
				}
				else
				{
					$variant = $part . '.' . $lastVariant;

					$result[] = $variant;
					$lastVariant = $variant;
				}
			}
		}
		else
		{
			$result[] = $domain;
		}

		return $result;
	}

	protected static function compareDir($firstPath, $secondPath)
	{
		$firstPath = rtrim($firstPath, '/');
		$secondPath = rtrim($secondPath, '/');

		return strcasecmp($firstPath, $secondPath);
	}
}