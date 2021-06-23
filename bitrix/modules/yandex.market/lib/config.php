<?php

namespace Yandex\Market;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

class Config
{
	protected static $serializedOptionPrefix =  '__YANDEX__CONFIG__:';

	public static function getModuleName()
	{
		return 'yandex.market';
	}

	public static function getLang($code, $replaces = null, $fallback = null)
	{
		$prefix = static::getLangPrefix();

		$result = Loc::getMessage($prefix . $code, $replaces) ?: $fallback;

		if ($result === null)
		{
			$result = $code;
		}

		return $result;
	}

	public static function getLangPrefix()
	{
		return 'YANDEX_MARKET_';
	}

	public static function getNamespace()
	{
		return '\\' . __NAMESPACE__;
	}

	public static function getModulePath()
	{
		return __DIR__;
	}

	public static function isExpertMode()
	{
		return (static::getOption('expert_mode', 'N') === 'Y');
	}

	public static function getOption($name, $default = "", $siteId = false)
	{
		$moduleName = static::getModuleName();
		$optionValue = Option::get($moduleName, $name, null, $siteId);

		if (Market\Data\TextString::getPosition($optionValue, static::$serializedOptionPrefix) === 0)
		{
			$truncatedValue = Market\Data\TextString::getSubstring(
				$optionValue,
				Market\Data\TextString::getLength(static::$serializedOptionPrefix)
			);
			$unserializedValue = unserialize($truncatedValue);
			$optionValue = ($unserializedValue !== false ? $unserializedValue : null);
		}

		if (!isset($optionValue))
		{
			$optionValue = $default;
		}

		return $optionValue;
	}

	public static function setOption($name, $value = "", $siteId = "")
	{
		$moduleName = static::getModuleName();

		if (!is_scalar($value))
		{
			$value = static::$serializedOptionPrefix . serialize($value);
		}

		Option::set($moduleName, $name, $value, $siteId);
	}

	public static function removeOption($name)
	{
		$moduleName = static::getModuleName();

		Option::delete($moduleName, [ 'name' => $name ]);
	}
}