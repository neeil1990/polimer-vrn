<?php

namespace Yandex\Market;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Environment
{
	const PHP_MIN_VERSION = '5.4.0';

	protected static $dateDefaultTimezoneOriginal;
	protected static $phpIniOriginal = [];
	protected static $globalVariablesOriginal = [];

	/**
	 * Сохранить состояние окружения
	 */
	public static function stamp()
	{
		static::stampTimezone();
	}

	protected static function stampTimezone()
	{
		Market\Config::setOption('environment_timezone', date_default_timezone_get());
	}

	public static function getTimezone()
	{
		return (string)Market\Config::getOption('environment_timezone');
	}

	/**
	 * Восстановление переменных окружения
	 */
	public static function restore()
	{
		static::restoreDefaultTimezone();
		static::restoreMissingIniTimezone();
		static::restoreValidGlobalUser();
	}

	/**
	 * Пустой объект пользователя
	 */
	public static function makeUserPlaceholder()
	{
		$globalUser = isset($GLOBALS['USER']) ? $GLOBALS['USER'] : null;

		if ($globalUser instanceof \CUser) { return; }

		$GLOBALS['USER'] = new Utils\DummyUser();

		if (!isset(static::$globalVariablesOriginal['USER']))
		{
			static::$globalVariablesOriginal['USER'] = $globalUser;
		}
	}

	/**
	 * Сброс переменных окружения
	 */
	public static function reset()
	{
		static::resetDefaultTimezone();
		static::resetOriginalIni();
		static::resetGlobalVariables();
	}

	protected static function restoreDefaultTimezone()
	{
		$storedTimezone = static::getTimezone();

		if ($storedTimezone === '' || $storedTimezone === date_default_timezone_get()) { return; }

		// default

		static::$dateDefaultTimezoneOriginal = date_default_timezone_get();
		date_default_timezone_set($storedTimezone);
	}

	protected static function resetDefaultTimezone()
	{
		if (static::$dateDefaultTimezoneOriginal === null) { return; }

		date_default_timezone_set(static::$dateDefaultTimezoneOriginal);
		static::$dateDefaultTimezoneOriginal = null;
	}

	protected static function restoreMissingIniTimezone()
	{
		$storedTimezone = static::getTimezone();
		$iniTimezone = ini_get('date.timezone');

		if ($iniTimezone || $storedTimezone === '') { return; }

		ini_set('date.timezone', $storedTimezone);
		static::$phpIniOriginal['date.timezone'] = $iniTimezone;
	}

	protected static function restoreValidGlobalUser()
	{
		if (!empty($GLOBALS['USER']) && !($GLOBALS['USER'] instanceof \CUser))
		{
			static::$globalVariablesOriginal['USER'] = $GLOBALS['USER'];
			unset($GLOBALS['USER']);
		}
	}

	protected static function resetOriginalIni()
	{
		foreach (static::$phpIniOriginal as $key => $value)
		{
			ini_set($key, $value);
		}

		static::$phpIniOriginal = [];
	}

	protected static function resetGlobalVariables()
	{
		foreach (static::$globalVariablesOriginal as $key => $value)
		{
			$GLOBALS[$key] = $value;
		}

		static::$globalVariablesOriginal = [];
	}

	/**
	 * Результат проверки окружения
	 *
	 * @return Result\Base
	 */
	public static function check()
	{
		$result = new Market\Result\Base();

		static::checkPhpVersion($result);
		static::checkSiteEncoding($result);

		return $result;
	}

	/**
	 * Проверяем версию Php
	 *
	 * @param Result\Base $result
	 */
	protected static function checkPhpVersion(Market\Result\Base $result)
	{
		if (CheckVersion(PHP_VERSION, static::PHP_MIN_VERSION) === false)
		{
			$errorMessage = Market\Config::getLang('ENVIRONMENT_PHP_MIN_VERSION', [
				'#MIN#' => static::PHP_MIN_VERSION,
				'#CURRENT#' => PHP_VERSION
			]);

			$result->addError(new Market\Error\Base($errorMessage, 'ENV_PHP_MIN_VERSION'));
		}
	}

	/**
	 * Проверяем соответствие кодировки сайта и php-окружения
	 *
	 * @param Result\Base $result
	 */
	protected static function checkSiteEncoding(Market\Result\Base $result)
	{
		$isUtfMode = Main\Application::isUtfMode();
		$mbstringFuncOverload = (int)ini_get('mbstring.func_overload');
		$mbstringEncoding = static::unifyEncoding(ini_get('mbstring.internal_encoding'));
		$defaultEncoding = static::unifyEncoding(ini_get('default_charset'));
		$resultEncoding = $mbstringEncoding ?: $defaultEncoding;

		if ($isUtfMode)
		{
			if ($mbstringFuncOverload !== 2 && !static::isMbstringOverloadDeprecated())
			{
				$errorMessage = Market\Config::getLang('ENCODING_FUNC_OVERLOAD', [
					'#REQUIRED#' => 2,
					'#CURRENT#' => $mbstringFuncOverload
				]);

				$result->addError(new Market\Error\Base($errorMessage, 'ENV_ENCODING_FUNC_OVERLOAD'));
			}

			if ($resultEncoding !== 'utf8')
			{
				$errorMessage = Market\Config::getLang('ENCODING_NOT_VALID', [
					'#REQUIRED#' => 'utf-8',
					'#CURRENT#' => $resultEncoding
				]);

				$result->addError(new Market\Error\Base($errorMessage, 'ENV_ENV_ENCODING_NOT_VALID'));
			}
		}
		else if ($mbstringFuncOverload === 2)
		{
			if ($resultEncoding === 'utf8')
			{
				$langEncoding = static::unifyEncoding(LANG_CHARSET);

				$errorMessage = Market\Config::getLang('ENCODING_NOT_VALID', [
					'#REQUIRED#' => $langEncoding === 'cp1251' ? 'cp1251' : 'latin1',
					'#CURRENT#' => $resultEncoding
				]);

				$result->addError(new Market\Error\Base($errorMessage, 'ENV_ENV_ENCODING_NOT_VALID'));
			}
		}
		else if ($mbstringFuncOverload !== 0)
		{
			$errorMessage = Market\Config::getLang('ENCODING_FUNC_OVERLOAD', [
				'#REQUIRED#' => 0,
				'#CURRENT#' => $mbstringFuncOverload
			]);

			$result->addError(new Market\Error\Base($errorMessage, 'ENV_ENCODING_FUNC_OVERLOAD'));
		}
	}

	protected static function unifyEncoding($encoding)
	{
		$encodingLower = Market\Data\TextString::toLower($encoding);

		return str_replace(['-', 'windows'], ['', 'cp'], $encodingLower);
	}

	protected static function isMbstringOverloadDeprecated()
	{
		$mainVersion = Main\ModuleManager::getVersion('main');

		return $mainVersion !== false && CheckVersion($mainVersion, '20.100.0');
	}
}