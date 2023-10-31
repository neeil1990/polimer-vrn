<?php

namespace Yandex\Market\Trading\Service;

use Yandex\Market;
use Bitrix\Main;

class Manager
{
	const SERVICE_TURBO = 'turbo';
	const SERVICE_MARKETPLACE = 'marketplace';
	const SERVICE_BERU = 'beru';

	const BEHAVIOR_DEFAULT = 'default';
	const BEHAVIOR_DBS = 'dbs';

	protected static $userServices;

	/**
	 * @param string $code
	 * @param string $behavior
	 *
	 * @return Market\Trading\Service\Reference\Provider
	 * @throws Market\Exceptions\NotImplemented
	 */
	public static function createProvider($code, $behavior = Manager::BEHAVIOR_DEFAULT)
	{
		$variant = static::makeVariant($code, $behavior);
		$userServices = static::getUserServices();

		if (isset($userServices[$variant]))
		{
			$className = $userServices[$variant];
		}
		else if (in_array($variant, static::getSystemVariants(), true))
		{
			$className = static::getSystemProviderClassName($variant);
		}
		else
		{
			throw new Market\Exceptions\NotImplemented('service provider not implemented for ' . $code);
		}

		return new $className($variant);
	}

	public static function getServices()
	{
		$serviceMap = [];

		foreach (static::getVariants() as $variant)
		{
			$variantService = static::getVariantServiceCode($variant);

			$serviceMap[$variantService] = true;
		}

		return array_keys($serviceMap);
	}

	public static function getBehaviors($serviceCode)
	{
		$behaviorMap = [];

		foreach (static::getVariants() as $variant)
		{
			$variantService = static::getVariantServiceCode($variant);

			if ($variantService === $serviceCode)
			{
				$behaviorCode = static::getVariantBehaviorCode($variant);
				$behaviorMap[$behaviorCode] = true;
			}
		}

		return array_keys($behaviorMap);
	}

	public static function isExists($code, $behavior = Manager::BEHAVIOR_DEFAULT)
	{
		$variant = static::makeVariant($code, $behavior);
		$variants = static::getVariants();

		return in_array($variant, $variants, true);
	}

	public static function getVariants()
	{
		$systemServices = static::getSystemVariants();
		$userServices = static::getUserServices();

		return array_merge(
			$systemServices,
			array_keys($userServices)
		);
	}

	protected static function getSystemVariants()
	{
		return [
			static::SERVICE_TURBO,
			static::SERVICE_BERU,
			static::SERVICE_MARKETPLACE . ':' . static::BEHAVIOR_DBS,
			static::SERVICE_MARKETPLACE,
		];
	}

	protected static function getSystemProviderClassName($code)
	{
		$serviceCode = static::getVariantServiceCode($code);
		$behaviorCode = static::getVariantBehaviorCode($code);
		$serviceNamespacePart = ucfirst($serviceCode);

		if ($behaviorCode !== static::BEHAVIOR_DEFAULT)
		{
			$serviceNamespacePart .= ucfirst($behaviorCode);
		}

		return __NAMESPACE__ . '\\' . $serviceNamespacePart . '\\' . 'Provider';
	}

	protected static function getUserServices()
	{
		if (static::$userServices === null)
		{
			static::$userServices = static::loadUserServices();
		}

		return static::$userServices;
	}

	protected static function loadUserServices()
	{
		$result = [];
		$moduleName = Market\Config::getModuleName();
		$eventName = 'onTradingServiceBuildList';

		$event = new Main\Event($moduleName, $eventName);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== Main\EventResult::SUCCESS) { continue; }

			$eventData = $eventResult->getParameters();

			if (!isset($eventData['SERVICE']))
			{
				throw new Main\ArgumentException('SERVICE must be defined for event result ' . $eventName);
			}

			$serviceKey = isset($eventData['BEHAVIOR'])
				? static::makeVariant($eventData['SERVICE'], $eventData['BEHAVIOR'])
				: static::makeVariant($eventData['SERVICE']);

			if (!isset($eventData['PROVIDER']))
			{
				throw new Main\ArgumentException('PROVIDER must be defined for service ' . $serviceKey);
			}

			if (!is_subclass_of($eventData['PROVIDER'], Reference\Provider::class))
			{
				throw new Main\ArgumentException($eventData['PROVIDER'] . ' must extends ' . Reference\Provider::class . ' for service ' . $serviceKey);
			}

			$result[$serviceKey] = $eventData['PROVIDER'];
		}

		return $result;
	}

	protected static function makeVariant($code, $behavior = Manager::BEHAVIOR_DEFAULT)
	{
		$result = $code;
		$behavior = (string)$behavior;

		if ($behavior !== '' && $behavior !== static::BEHAVIOR_DEFAULT)
		{
			$result .= ':' . $behavior;
		}

		return $result;
	}

	protected static function getVariantServiceCode($code)
	{
		$gluePosition = Market\Data\TextString::getPosition($code, ':');

		if ($gluePosition !== false)
		{
			$result = Market\Data\TextString::getSubstring($code, 0, $gluePosition);
		}
		else
		{
			$result = $code;
		}

		return $result;
	}

	protected static function getVariantBehaviorCode($code)
	{
		$gluePosition = Market\Data\TextString::getPosition($code, ':');

		if ($gluePosition !== false)
		{
			$result = Market\Data\TextString::getSubstring($code, $gluePosition + 1);
		}
		else
		{
			$result = static::BEHAVIOR_DEFAULT;
		}

		return $result;
	}
}