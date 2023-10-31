<?php

namespace Yandex\Market\Ui;

use Bitrix\Main;
use Yandex\Market\Data\TextString;

class Extension
{
	public static function register($extension, $name = null)
	{
		if (static::hasRegisteredExtension($extension)) { return true; }

		$relativePath = Assets::getPluginDirectory($extension);
		$configPath = $relativePath . '/config.php';
		$configPath = Main\IO\Path::convertRelativeToAbsolute($configPath);
		$configFile = new Main\IO\File($configPath);

		if (!$configFile->isExists()) { return false; }

		$config = include $configFile->getPath();
		$config = static::makeConfigPath($config, $relativePath);

		\CJSCore::RegisterExt($name ?: $extension, $config);

		return true;
	}

	protected static function hasRegisteredExtension($extension)
	{
		return (
			\CJSCore::IsExtRegistered($extension)
			|| (
				class_exists(Main\UI\Extension::class)
				&& Main\UI\Extension::register($extension)
			)
		);
	}

	protected static function makeConfigPath($config, $relativePath)
	{
		$chains = [ 'js', 'css' ];

		foreach ($chains as $chain)
		{
			if (!isset($config[$chain])) { continue; }

			$newChain = [];
			$isChanged = false;

			foreach ((array)$config[$chain] as $path)
			{
				if (TextString::getPosition($path, '/') !== 0)
				{
					$isChanged = true;
					$path = $relativePath . '/' . $path;
				}

				$newChain[] = $path;
			}

			if ($isChanged)
			{
				$config[$chain] = $newChain;
			}
		}

		return $config;
	}

	public static function registerCompatible($extension)
	{
		if (static::hasRegisteredExtension($extension)) { return $extension; }

		$extension = 'compatible.' . $extension;
		$name = str_replace('.', '_', $extension);

		return static::register($extension, $name) ? $name : null;
	}

	public static function load($extension)
	{
		\CJSCore::Init([$extension]);
	}

	public static function loadConditional($extension, $varName, $location = Main\Page\AssetLocation::AFTER_CSS)
	{
		$assets = \CJSCore::getExtInfo($extension);
		$loadJs = static::getConditionLoad($assets);
		$script = sprintf(
			'<script data-bxrunfirst>if (!window.%s && (!top.%s || (window.frameElement && /side-panel-iframe/.test(window.frameElement.className)))) { %s }</script>',
			$varName,
			$varName,
			$loadJs
		);

		$assets = Main\Page\Asset::getInstance();
		$assets->addString($script, true, $location);
	}

	protected static function getConditionLoad($assets)
	{
		$loadJs = '';

		if (isset($assets['css']))
		{
			$loadJs .= static::getConditionLoadCss((array)$assets['css']);
		}

		if (isset($assets['js']))
		{
			$loadJs .= static::getConditionLoadJs((array)$assets['js']);
		}

		return $loadJs;
	}

	protected static function getConditionLoadCss($pathList)
	{
		return static::getConditionLoadByBitrix($pathList, 'loadCSS');
	}

	protected static function getConditionLoadJs($pathList)
	{
		$beforeLoad = static::getConditionLoadJsSync($pathList);
		$afterLoad = static::getConditionLoadByBitrix($pathList, 'loadScript');

		return sprintf('
			if (document.readyState === "loading") { 
				%s 
			} else { 
				%s 
			}
			',
			$beforeLoad,
			$afterLoad
		);
	}

	protected static function getConditionLoadByBitrix($pathList, $method)
	{
		$pathString = implode('", "', $pathList);

		return sprintf('(window.BX||top.BX).%s(["%s"]);', $method, $pathString);
	}

	protected static function getConditionLoadJsSync($pathList)
	{
		$tags = array_map(static function($path) { return sprintf('<script src="%s"></script>', $path); }, $pathList);
		$tagsString = implode(PHP_EOL, $tags);
		$tagsString = str_replace(['"', '</'], ['\\"', '<\\/'], $tagsString);

		return sprintf('document.write("%s");', $tagsString);
	}

	public static function loadOne(array $variants, $fallbackFirst = false)
	{
		$name = static::getOne($variants, $fallbackFirst);

		static::load($name);
	}

	public static function getOne(array $variants, $fallbackFirst = false)
	{
		$canLoadVariants = array_filter($variants, [__CLASS__, 'canLoad']);

		if (!empty($canLoadVariants))
		{
			$loadedVariants = array_filter($canLoadVariants, [__CLASS__, 'isLoaded']);

			if (!empty($loadedVariants))
			{
				$result = reset($loadedVariants);
			}
			else
			{
				$result = reset($canLoadVariants);
			}
		}
		else if ($fallbackFirst)
		{
			$result = reset($variants);
		}
		else
		{
			throw new Main\SystemException(sprintf(
				'cant find valid extension from %s',
				implode(', ', $variants)
			));
		}

		return $result;
	}

	public static function canLoad($name)
	{
		$result = true;

		if (!\CJSCore::IsExtRegistered($name))
		{
			$result = false;
		}
		else
		{
			$info = \CJSCore::getExtInfo($name);
			$types = [ 'css', 'js' ];
			$docRoot = Main\Loader::getDocumentRoot();

			foreach ($types as $type)
			{
				if (!isset($info[$type])) { continue; }

				$pathList = (array)$info[$type];

				foreach ($pathList as $path)
				{
					$absolutePath = $docRoot . $path;

					if (!file_exists($absolutePath))
					{
						$result = false;
						break;
					}
				}
			}
		}

		return $result;
	}

	public static function isLoaded($name)
	{
		return method_exists('CJSCore', 'isExtensionLoaded') && \CJSCore::isExtensionLoaded($name);
	}
}