<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;

class HttpConfiguration
{
	protected static $originalOptions;

	public static function stamp()
	{
		static::saveOriginal();
	}

	public static function setGlobalTimeout($socketTimeout, $streamTimeout = null)
	{
		static::setOptions(array_merge(static::getOptions(), [
			'socketTimeout' => $socketTimeout,
			'streamTimeout' => $streamTimeout ?: $socketTimeout,
		]));
	}

	public static function restore()
	{
		static::restoreOriginal();
	}

	protected static function saveOriginal()
	{
		if (static::$originalOptions !== null) { return; }

		static::$originalOptions = static::getOptions();
	}

	protected static function restoreOriginal()
	{
		if (static::$originalOptions === null) { return; }

		static::setOptions(static::$originalOptions);
		static::$originalOptions = null;
	}

	protected static function getOptions()
	{
		$result = Main\Config\Configuration::getInstance()->get('http_client_options');

		return is_array($result) ? $result : [];
	}

	protected static function setOptions(array $options)
	{
		Main\Config\Configuration::getInstance()->add('http_client_options', $options);
	}
}