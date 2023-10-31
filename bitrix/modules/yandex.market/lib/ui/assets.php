<?php

namespace Yandex\Market\Ui;

use Bitrix\Main;
use Yandex\Market;

class Assets
{
	protected static $rootNamespace = 'YandexMarket.';

	public static function loadPluginCore()
	{
		static::loadPlugins([
			'utils',
			'plugin.base',
			'plugin.manager',
		]);
	}

	public static function loadFieldsCore()
	{
		\CJSCore::Init(['window']);

		static::loadPlugins([
			'lib.editdialog',
			'lib.corespeedup',
			'Field.Reference.Base',
			'Field.Reference.Collection',
			'Field.Reference.Complex',
			'Field.Reference.Summary',
		]);
	}

	public static function loadPlugins($plugins, $resourceType = 'js')
	{
		foreach ($plugins as $plugin)
		{
			static::loadPlugin($plugin, $resourceType);
		}
	}

	public static function loadPlugin($plugin, $resourceType = 'js')
	{
		global $APPLICATION;

		$assets = Main\Page\Asset::getInstance();
		$path = static::getPluginPath($plugin, $resourceType);

		switch ($resourceType)
		{
			case 'css':
				$APPLICATION->SetAdditionalCSS($path); // admin old behavior
				$assets->addCss($path);
			break;

			case 'js':
				$assets->addJs($path);
			break;
		}
	}

	public static function loadMessages($keys)
	{
		$messages = [];
		$prefix = Market\Config::getLangPrefix();
		$assets = Main\Page\Asset::getInstance();

		foreach ($keys as $key)
		{
			$messages[$prefix . $key] = Market\Config::getLang($key);
		}

		$assets->addString('<script data-bxrunfirst>(window.BX||top.BX).message(' . \CUtil::PhpToJSObject($messages) . ');</script>', false, Main\Page\AssetLocation::AFTER_CSS);
	}

	public static function getRootDirectoryPath($resourceType = 'js')
	{
		return BX_ROOT . '/' . $resourceType . '/' . Market\Config::getModuleName();
	}

	public static function getPluginPath($pluginName, $resourceType = 'js')
	{
		$base = static::makePluginPath($pluginName, $resourceType);
		$baseAbsolute = Main\IO\Path::convertRelativeToAbsolute($base);

		if (!is_dir($baseAbsolute)) { return $base . '.' . $resourceType; }

		if ($resourceType === 'js')
		{
			$result = $base . '/script.js';
		}
		else if ($resourceType === 'css')
		{
			$result = $base . '/style.css';
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown %s resource type', $resourceType));
		}

		return $result;
	}

	public static function getPluginDirectory($pluginName, $resourceType = 'js')
	{
		return static::makePluginPath($pluginName, $resourceType);
	}

	protected static function makePluginPath($pluginName, $resourceType = 'js')
	{
		$relativeName = str_replace(static::$rootNamespace, '', $pluginName);
		$relativeName = Market\Data\TextString::toLower($relativeName);
		$relativePath = preg_replace('#(?<!\\\)\.#', '/', $relativeName);
		$relativePath = str_replace('\\.', '.', $relativePath);
		$directoryType = $resourceType;

		if (preg_match('#^/(.*?)/(.*)$#', $relativePath, $matches))
		{
			list(, $directoryType, $relativePath) = $matches;
		}

		return static::getRootDirectoryPath($directoryType) . '/' . $relativePath;
	}

	public static function initPlugin($plugin, $selector, $options = null)
	{
		$plugin = static::absolutizePluginName($plugin);

		return '<script>
			(function() {
				var Plugin = BX.namespace("' . $plugin . '");
				
				' . static::initPluginBody($plugin, $selector, $options) . '
			})();
		</script>';
	}

	public static function initDelayedPlugin($plugin, $selector, $options = null)
	{
		$plugin = static::absolutizePluginName($plugin);

		return '<script>
			(function() {
				var Plugin = BX.namespace("' . $plugin . '");
				
				if (typeof Plugin === "function") {
					initPlugin(Plugin);
				} else {
					BX.addCustomEvent("yamarketPluginReady", checkReady);
					
					function checkReady(pluginName, Plugin) {
						if (pluginName === "' . $plugin .'") {
							initPlugin(Plugin);
							BX.removeCustomEvent("yamarketPluginReady", checkReady);
						}
					}
				}
				
				function initPlugin(Plugin) {
					' . static::initPluginBody($plugin, $selector, $options) . '
				}
			})();
		</script>';
	}

	protected static function initPluginBody($plugin, $selector, $options)
	{
		return '
			var elementList = document.querySelectorAll("' . $selector . '");		
			var elementIndex;
			var element;	
			var options = ' . \CUtil::PhpToJSObject($options)  . ';
						
			for (elementIndex = 0; elementIndex < elementList.length; elementIndex++) {
				element = elementList[elementIndex];
				
				new Plugin(element, options);			
			}
		';
	}

	protected static function absolutizePluginName($plugin)
	{
		$result = $plugin;

		if (Market\Data\TextString::getPosition($plugin, static::$rootNamespace) === false)
		{
			$result = static::$rootNamespace . $plugin;
		}

		return $result;
	}
}