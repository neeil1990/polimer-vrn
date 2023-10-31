<?php

namespace Yandex\Market\Trading\Entity;

use Yandex\Market;
use Bitrix\Main;

class Manager
{
	use Market\Reference\Concerns\HasLang;

	const ENVIRONMENT_SALE_CRM = 'saleCrm';
	const ENVIRONMENT_SALE = 'sale';

	protected static $userEnvironments;
	protected static $activeEnvironment;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	/**
	 * @return Reference\Environment
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function createEnvironment()
	{
		$activeCode = static::getActiveEnvironment();
		$result = static::getEnvironmentInstance($activeCode);

		$result->load();

		return $result;
	}

	/**
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function getActiveEnvironment()
	{
		if (static::$activeEnvironment === null)
		{
			static::$activeEnvironment = static::resolveActiveEnvironment();
		}

		return static::$activeEnvironment;
	}

	/**
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected static function resolveActiveEnvironment()
	{
		$result = null;
		$option = (string)Market\Config::getOption('trading_entity_environment', '');

		if ($option !== '')
		{
			$environment = static::getEnvironmentInstance($option);

			if (!$environment->isSupported())
			{
				throw new Market\Exceptions\NotImplemented('environment ' . $option . ' is not supported');
			}

			$result = $option;
		}
		else
		{
			foreach (static::getEnvironmentVariants() as $code)
			{
				$environment = static::getEnvironmentInstance($code);

				if ($environment->isSupported())
				{
					$result = $code;
					break;
				}
			}

			if ($result === null)
			{
				$message = static::getLang('TRADING_ENTITY_ENVIRONMENT_NOT_FOUND');
				throw new Market\Exceptions\NotImplemented($message);
			}
		}

		return $result;
	}

	/**
	 * @return string[]
	 */
	public static function getEnvironmentVariants()
	{
		$userEnvironments = static::getUserEnvironments();

		return array_merge(
			array_keys($userEnvironments),
			static::getSystemEnvironments()
		);
	}

	/**
	 * @param string $type
	 * @return Reference\Environment
	 * @throws Main\ArgumentException
	 */
	protected static function getEnvironmentInstance($type)
	{
		$userEnvironments = static::getUserEnvironments();

		if (isset($userEnvironments[$type]))
		{
			$environmentClassName = $userEnvironments[$type];
		}
		else if (in_array($type, static::getSystemEnvironments(), true))
		{
			$environmentClassName = static::getSystemEnvironmentClassName($type);
		}
		else
		{
			throw new Main\ArgumentException('environment ' . $type . ' not found');
		}

		return new $environmentClassName($type);
	}

	/**
	 * @return string[]
	 */
	protected static function getSystemEnvironments()
	{
		return [
			static::ENVIRONMENT_SALE_CRM,
			static::ENVIRONMENT_SALE,
		];
	}

	protected static function getSystemEnvironmentClassName($type)
	{
		return __NAMESPACE__ . '\\' . ucfirst($type) . '\\' . 'Environment';
	}

	protected static function getUserEnvironments()
	{
		if (static::$userEnvironments === null)
		{
			static::$userEnvironments = static::loadUserEnvironments();
		}

		return static::$userEnvironments;
	}

	protected static function loadUserEnvironments()
	{
		$result = [];
		$moduleName = Market\Config::getModuleName();
		$eventName = 'onTradingEntityEnvironmentBuildList';

		$event = new Main\Event($moduleName, $eventName);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== Main\EventResult::SUCCESS) { continue; }

			$eventData = $eventResult->getParameters();

			if (!isset($eventData['CODE']))
			{
				throw new Main\ArgumentException('CODE must be defined for event result ' . $eventName);
			}

			if (!isset($eventData['ENVIRONMENT']))
			{
				throw new Main\ArgumentException('ENVIRONMENT must be defined for ' . $eventData['CODE']);
			}

			if (!is_subclass_of($eventData['ENVIRONMENT'], Reference\Environment::class))
			{
				throw new Main\ArgumentException($eventData['ENVIRONMENT'] . ' must extends ' . Reference\Environment::class . ' for ' . $eventData['CODE']);
			}

			$result[$eventData['CODE']] = $eventData['ENVIRONMENT'];
		}

		return $result;
	}
}