<?php

namespace Yandex\Market\Utils\IO;

use Bitrix\Main;
use Yandex\Market;

class Path
{
	public static function absoluteToRelative($absolute, $root = null)
	{
		if ($root === null) { $root = Main\Application::getDocumentRoot(); }

		$root = Main\IO\Path::normalize($root);
		$absolute = Main\IO\Path::normalize($absolute);

		if (Market\Data\TextString::getPosition($absolute, $root) === 0)
		{
			$rootLength = Market\Data\TextString::getLength($root);
			$result = Market\Data\TextString::getSubstring($absolute, $rootLength);
			$result = ltrim($result, Main\IO\Path::DIRECTORY_SEPARATOR);
		}
		else
		{
			$rootDirs = [ static::getBitrixFolderName(), 'local' ];
			$result = null;

			foreach ($rootDirs as $rootDir)
			{
				$rootDirSearch = Main\IO\Path::DIRECTORY_SEPARATOR . $rootDir . Main\IO\Path::DIRECTORY_SEPARATOR;
				$rootDirPosition = Market\Data\TextString::getPosition($absolute, $rootDirSearch);

				if ($rootDirPosition === false) { continue; }

				$result = Market\Data\TextString::getSubstring($absolute, $rootDirPosition);
				$result = ltrim($result, Main\IO\Path::DIRECTORY_SEPARATOR);
				break;
			}

			if ($result === null)
			{
				throw new Main\IO\InvalidPathException($absolute);
			}
		}

		return $result;
	}

	protected static function getBitrixFolderName()
	{
		return trim(BX_ROOT, Main\IO\Path::DIRECTORY_SEPARATOR);
	}
}