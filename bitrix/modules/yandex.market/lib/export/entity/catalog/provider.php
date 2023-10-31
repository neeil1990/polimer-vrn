<?php

namespace Yandex\Market\Export\Entity\Catalog;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

class Provider
{
	protected static $useCatalogShortFieldsCache;
	protected static $useSkuAvailableCalculationCache;

	public static function supportCatalogShortFields()
	{
		return (
			Main\Loader::includeModule('catalog')
			&& method_exists('CProductQueryBuilder', 'isValidField')
			&& \CProductQueryBuilder::isValidField('AVAILABLE')
		);
	}

	public static function useCatalogShortFields()
	{
		if (static::$useCatalogShortFieldsCache !== null)
		{
			$result = static::$useCatalogShortFieldsCache;
		}
		else
		{
			$result = false;

			if (static::supportCatalogShortFields())
			{
				$option = (string)Market\Config::getOption('export_entity_catalog_use_short', 'Y');
				$result = ($option === 'Y');
			}

			static::$useCatalogShortFieldsCache = $result;
		}

		return $result;
	}

	public static function supportSkuAvailableCalculation()
	{
		$catalogVersion = Main\ModuleManager::getVersion('catalog');

		return (
			$catalogVersion !== false
			&& CheckVersion($catalogVersion, '16.0.3')
			&& (string)Main\Config\Option::get('catalog', 'show_catalog_tab_with_offers') !== 'Y'
		);
	}

	public static function useSkuAvailableCalculation()
	{
		if (static::$useSkuAvailableCalculationCache !== null)
		{
			$result = static::$useSkuAvailableCalculationCache;
		}
		else
		{
			$result = false;
			$option = (string)Market\Config::getOption('export_entity_catalog_sku_available_auto');

			if ($option !== '')
			{
				$result = ($option === 'Y');
			}
			else if (static::supportSkuAvailableCalculation())
			{
				$result = true;
			}

			static::$useSkuAvailableCalculationCache = $result;
		}

		return $result;
	}

	protected static function hasCatalogTypeCompatibility()
	{
		return !static::supportSkuAvailableCalculation();
	}

	public static function isCatalogTypeCompatibility(array $context)
	{
		return (
			$context['HAS_OFFER']
			&& empty($context['OFFER_ONLY'])
			&& static::useCatalogTypeCompatibility()
		);
	}

	public static function useCatalogTypeCompatibility()
	{
		$selfOption = (string)Market\Config::getOption('export_offer_catalog_type_compatibility');

		if ($selfOption !== '')
		{
			$result = ($selfOption === 'Y');
		}
		else
		{
			$result = static::hasCatalogTypeCompatibility();
		}

		return $result;
	}
}