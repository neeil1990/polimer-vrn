<?php

namespace Yandex\Market\Data;

use Yandex\Market;
use Bitrix\Main;

class Site
{
	protected static $enum;
	protected static $urlVariables = [];
	protected static $languages = [];
	protected static $culture = [];

	public static function getVariants()
	{
		$enum = static::getEnum();

		return array_column($enum, 'ID');
	}

	public static function isCrm($siteId)
	{
		if (defined('BX24_HOST_NAME'))
		{
			$result = ($siteId === SiteDomain::getSite(BX24_HOST_NAME));
		}
		else
		{
			$result = static::hasCrmTemplate($siteId);
		}

		return $result;
	}

	protected static function hasCrmTemplate($siteId)
	{
		$query = Main\SiteTemplateTable::getList([
			'filter' => [
				'=SITE_ID' => $siteId,
				'%TEMPLATE' => 'bitrix24',
			],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	public static function getDocumentRoot($siteId)
	{
		$parts = [
			'DOC_ROOT' => Main\Context::getCurrent()->getServer()->getDocumentRoot(),
			'DIR' => '/',
		];

		$query = Main\SiteTable::getList([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'DOC_ROOT', 'DIR' ],
		]);

		if ($row = $query->fetch())
		{
			foreach ($parts as $key => $value)
			{
				$siteValue = trim($row[$key]);

				if ($siteValue === '') { continue; }

				$parts[$key] = $siteValue;
			}
		}

		$parts['DOC_ROOT'] = rtrim($parts['DOC_ROOT'], '/');
		$parts['DIR'] = '/' . ltrim($parts['DIR'], '/');

		return [
			$parts['DOC_ROOT'],
			$parts['DIR'],
		];
	}

	public static function getTitle($siteId)
	{
		$siteId = (string)$siteId;
		$result = null;

		foreach (static::getEnum() as $option)
		{
			if ($option['ID'] === $siteId)
			{
				$result = $option['NAME'];
				break;
			}
		}

		return $result;
	}

	public static function getDefault()
	{
		$enum = static::getEnum();
		$option = reset($enum);

		return $option !== false ? $option['ID'] : null;
	}

	protected static function getEnum()
	{
		if (static::$enum === null)
		{
			static::$enum = static::loadEnum();
		}

		return static::$enum;
	}

	protected static function loadEnum()
	{
		$result = [];

		$query = Main\SiteTable::getList([
			'filter' => [ '=ACTIVE' => 'Y' ],
			'order' => [ 'DEF' => 'DESC', 'SORT' => 'ASC' ],
			'select' => [ 'LID', 'NAME' ]
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => (string)$row['LID'],
				'NAME' => $row['NAME'],
			];
		}

		return $result;
	}

	public static function getUrlVariables($siteId)
	{
		if (!isset(static::$urlVariables[$siteId]))
		{
			static::$urlVariables[$siteId] = static::loadUrlVariables($siteId);
		}

		return static::$urlVariables[$siteId];
	}

	protected static function loadUrlVariables($siteId)
	{
		$result = false;

		$query = Main\SiteTable::getList([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'SERVER_NAME', 'DIR' ]
		]);

		if ($site = $query->fetch())
		{
			$result = [
				'from' => [ '#SITE_DIR#', '#SERVER_NAME#', '#LANG#', '#SITE#' ],
				'to' => [ $site['DIR'], $site['SERVER_NAME'], $site['DIR'], $siteId ]
			];
		}

		return $result;
	}

	public static function getLanguage($siteId)
	{
		if ($siteId === SITE_ID) { return LANGUAGE_ID; }

		if (!isset(static::$languages[$siteId]))
		{
			static::$languages[$siteId] = static::loadLanguage($siteId);
		}

		return static::$languages[$siteId];
	}

	protected static function loadLanguage($siteId)
	{
		$result = LANGUAGE_ID;

		$query = Main\SiteTable::getList([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'LANGUAGE_ID' ],
		]);

		if ($row = $query->fetch())
		{
			$result = $row['LANGUAGE_ID'];
		}

		return $result;
	}

	public static function getCultureValue($siteId, $name)
	{
		$culture = static::getCulture($siteId);

		if ($culture === null) { return null; }

		if ($name === 'FORMAT_DATETIME')
		{
			$result = $culture->getDateTimeFormat();
		}
		else if ($name === 'FORMAT_DATE')
		{
			$result = $culture->getDateFormat();
		}
		else if ($name === 'FORMAT_NAME')
		{
			$result = $culture->getNameFormat();
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown culture field %s', $name));
		}

		return $result;
	}

	protected static function getCulture($siteId)
	{
		if (isset(static::$culture[$siteId]) || array_key_exists($siteId, static::$culture))
		{
			$result = static::$culture[$siteId];
		}
		else if (SITE_ID === $siteId)
		{
			$result = Main\Context::getCurrent()->getCulture();
		}
		else
		{
			$result = static::loadCulture($siteId);
			static::$culture[$siteId] = $result;
		}

		return $result;
	}

	protected static function loadCulture($siteId)
	{
		$query = Main\SiteTable::getList([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'CULTURE_ID' ],
		]);
		$site = $query->fetch();

		if (empty($site['CULTURE_ID'])) { return null; }

		$culture = Main\Context\Culture::wakeUp($site['CULTURE_ID']);

		if ($culture === null) { return null; }

		if (class_exists(Main\ORM\Objectify\EntityObject::class) && $culture instanceof Main\ORM\Objectify\EntityObject)
		{
			$culture->fill();
		}

		return $culture;
	}
}
