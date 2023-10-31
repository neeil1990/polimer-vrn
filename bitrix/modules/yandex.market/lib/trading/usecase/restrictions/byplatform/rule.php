<?php

namespace Yandex\Market\Trading\UseCase\Restrictions\ByPlatform;

use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Rule
{
	use Market\Reference\Concerns\HasLang;

	const ENTITY_TYPE_DELIVERY = 'DELIVERY';
	const ENTITY_TYPE_PAY_SYSTEM = 'PAY_SYSTEM';

	protected static $platformEnum = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getClassTitle()
	{
		return static::getLang('TRADING_USE_CASE_RESTRICTION_BY_PLATFORM');
	}

	public static function getClassDescription()
	{
		return '';
	}

	public static function check($params, $config)
	{
		$configPlatforms = static::sanitizeConfigPlatform($config['PLATFORM']);
		$intersect = array_intersect($params, $configPlatforms);
		$result = !empty($intersect);

		if ($config['INVERT'] === 'Y')
		{
			$result = !$result;
		}

		return $result;
	}

	protected static function sanitizeConfigPlatform($platforms)
	{
		$platforms = (array)$platforms;

		Main\Type\Collection::normalizeArrayValuesByInt($platforms, false);

		return $platforms;
	}

	public static function extractParams(Sale\Order $order)
	{
		if (method_exists($order, 'getTradeBindingCollection'))
		{
			$tradingCollection = $order->getTradeBindingCollection();
			$result = static::extractParamsFromTradingCollection($tradingCollection);
		}
		else
		{
			$xmlId = $order->getField('XML_ID');
			$result = static::extractParamsFromXmlId($xmlId);
		}

		return $result;
	}

	protected static function extractParamsFromTradingCollection(Sale\TradeBindingCollection $collection)
	{
		$result = [];

		/** @var Sale\TradeBindingEntity $trading */
		foreach ($collection as $trading)
		{
			$platformId = (int)$trading->getField('TRADING_PLATFORM_ID');

			if ($platformId <= 0) { continue; }

			$result[] = $platformId;
		}

		return $result;
	}

	protected static function extractParamsFromXmlId($xmlId)
	{
		$parsed = Market\Trading\Entity\Sale\Platform::parseOrderXmlId($xmlId);
		$result = [];

		if ($parsed !== null)
		{
			$result[] = $parsed['PLATFORM_ID'];
		}

		return $result;
	}

	public static function isAvailable($entityType)
	{
		$enum = static::getPlatformEnum($entityType);

		return !empty($enum);
	}

	public static function getParamsStructure($entityType, $entityId = 0)
	{
		return [
			'PLATFORM' => [
				'TYPE' => 'ENUM',
				'MULTIPLE' => 'Y',
				'LABEL' => static::getLang('TRADING_USE_CASE_RESTRICTION_BY_PLATFORM_PARAM_PLATFORM'),
				'OPTIONS' => static::getPlatformEnum($entityType),
				'MULTIELEMENT' => 'Y',
			],
			'INVERT' => [
				'TYPE' => 'Y/N',
				'LABEL' => static::getLang('TRADING_USE_CASE_RESTRICTION_BY_PLATFORM_PARAM_INVERT'),
			],
		];
	}

	protected static function getPlatformEnum($entityType)
	{
		if (!isset(static::$platformEnum[$entityType]))
		{
			static::$platformEnum[$entityType] = static::loadPlatformEnum($entityType);
		}

		return static::$platformEnum[$entityType];
	}

	protected static function loadPlatformEnum($entityType)
	{
		$setupList = Market\Trading\Setup\Model::loadList();
		$result = [];

		foreach ($setupList as $setup)
		{
			$platform = $setup->getPlatform();
			$service = $setup->getService();
			$feature = $service->getFeature();

			if (!static::isFeatureSupported($feature, $entityType)) { continue; }
			if (!$platform->isInstalled()) { continue; }

			$result[$platform->getId()] = $service->getInfo()->getTitle();
		}

		return $result;
	}

	protected static function isFeatureSupported(TradingService\Reference\Feature $feature, $entityType)
	{
		switch ($entityType)
		{
			case static::ENTITY_TYPE_DELIVERY:
			case static::ENTITY_TYPE_PAY_SYSTEM:
				$result = true;
			break;

			default:
				$message = sprintf(
					'entity type must be one of %s, %s',
					static::ENTITY_TYPE_DELIVERY,
					static::ENTITY_TYPE_PAY_SYSTEM
				);

				throw new Main\ArgumentException($message, 'entityType');
		}

		return $result;
	}
}