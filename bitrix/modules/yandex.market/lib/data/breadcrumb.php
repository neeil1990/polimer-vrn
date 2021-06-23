<?php

namespace Yandex\Market\Data;

use Yandex\Market;
use Bitrix\Main;

class Breadcrumb
{
	const PROPERTY_NAME = 'YAMARKET_NAME';
	const PROPERTY_START = 'YAMARKET_START';
	const PROPERTY_EXCLUDE = 'YAMARKET_EXCLUDE';

	public static function getCrumbs($path, $siteId = SITE_ID)
	{
		$result = [];
		$docRoot = Main\SiteTable::getDocumentRoot($siteId);
		$siteDir = static::getSiteDir($siteId);
		$siteDir = static::sanitizeDirectoryPath($siteDir);
		$path = static::sanitizeDirectoryPath($path);
		$pathParts = explode('/', $path);
		$iteratorPath = '';

		foreach ($pathParts as $pathPart)
		{
			$iteratorPath .= ($iteratorPath !== '/' ? '/' : '') . $pathPart;

			if (!static::isSubDirectory($siteDir, $iteratorPath)) { continue; }

			$directoryData = static::getDirectoryData($iteratorPath, $docRoot);

			if ($directoryData !== null && !$directoryData['EXCLUDE'])
			{
				if ($directoryData['START'])
				{
					$result = [];
				}

				$directoryName = trim($directoryData['NAME']);

				if ($directoryName !== '')
				{
					$result[] = $directoryName;
				}
			}
		}

		return $result;
	}

	protected static function getDirectoryData($path, $docRoot)
	{
		$io = \CBXVirtualIo::GetInstance();
		$chainFilePath = $docRoot . $path . '/.section.php';
		$result = null;

		if ($io->FileExists($chainFilePath))
		{
			$sSectionName = '';
			$arDirProperties = [];

			include $io->GetPhysicalName($chainFilePath);

			$result = [
				'NAME' => $sSectionName,
				'EXCLUDE' => (
					isset($arDirProperties[static::PROPERTY_EXCLUDE])
					&& $arDirProperties[static::PROPERTY_EXCLUDE] === 'Y'
				),
				'START' => (
					isset($arDirProperties[static::PROPERTY_START])
					&& $arDirProperties[static::PROPERTY_START] === 'Y'
				),
			];

			if (
				isset($arDirProperties[static::PROPERTY_NAME])
				&& trim($arDirProperties[static::PROPERTY_NAME]) !== ''
			)
			{
				$result['NAME'] = $arDirProperties[static::PROPERTY_NAME];
			}
		}

		return $result;
	}

	protected static function getSiteDir($siteId)
	{
		$querySite = Main\SiteTable::getList([
			'select' => [ 'DIR' ],
			'filter' => [ '=LID' => $siteId ],
			'limit' => 1,
		]);

		if ($site = $querySite->fetch())
		{
			$result = trim($site['DIR']);
		}
		else
		{
			$result = '/';
		}

		return $result;
	}

	protected static function sanitizeDirectoryPath($path)
	{
		$path = trim($path);
		$path = '/' . trim($path, '/');

		return $path;
	}

	protected static function isSubDirectory($root, $path)
	{
		$rootFull = $root . ($root !== '/' ? '/' : '');
		$pathFull = $path . ($path !== '/' ? '/' : '');

		return TextString::getPositionCaseInsensitive($pathFull, $rootFull) === 0;
	}
}
