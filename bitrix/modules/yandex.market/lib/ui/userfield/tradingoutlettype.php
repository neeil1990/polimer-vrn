<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class TradingOutletType extends EnumerationType
{
	public static function GetList($userField)
	{
		$serviceCode = static::getUserFieldServiceCode($userField);
		$optionValues = static::getUserFieldOptionValues($userField);
		$outlets = static::getVariants($serviceCode, $optionValues);

		$result = new \CDBResult();
		$result->InitFromArray($outlets['enum']);

		return $result;
	}

	protected static function getUserFieldServiceCode($userField)
	{
		return isset($userField['SETTINGS']['SERVICE'])
			? (string)$userField['SETTINGS']['SERVICE']
			: null;
	}

	protected static function getUserFieldOptionValues($userField)
	{
		if (isset($userField['ROW']['PARENT_ROW']))
		{
			$result = $userField['ROW']['PARENT_ROW'];
		}
		else if (isset($userField['ROW']))
		{
			$result = $userField['ROW'];
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl)
	{
		static::loadEditAssets();

		$attributes = Helper\Attributes::extractFromSettings($userField['SETTINGS']);
		$attributes += static::makeSelectViewAttributes($userField);
		$attributes['name'] = $userField['FIELD_NAME'];
		$settings = static::makeSelectViewSettings($userField);
		$value = Helper\Value::asSingle($userField, $htmlControl);
		$enum = (string)$value !== '' ? static::makeEnumFromValues([$value]) : [];

		return View\Select::getControl($enum, $value, $attributes, $settings);
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		static::loadEditAssets();

		$attributes = Helper\Attributes::extractFromSettings($userField['SETTINGS']);
		$attributes += static::makeSelectViewAttributes($userField);
		$attributes['name'] = $userField['FIELD_NAME'] . '[]';
		$settings = static::makeSelectViewSettings($userField);
		$values = Helper\Value::asMultiple($userField, $htmlControl);
		$enum = static::makeEnumFromValues($values);

		return View\Select::getControl($enum, $values, $attributes, $settings);
	}

	protected static function makeEnumFromValues(array $values)
	{
		$result = [];

		foreach ($values as $value)
		{
			$result[] = [
				'ID' => $value,
				'VALUE' => $value,
			];
		}

		return $result;
	}

	protected static function loadEditAssets()
	{
		Market\Ui\Plugin\TradingOutlet::load();
	}

	protected static function makeSelectViewAttributes($userField)
	{
		$result = parent::makeSelectViewAttributes($userField);
		$result = array_diff_key($result, [
			'data-multiple' => true,
		]);
		$result += [
			'class' => 'js-plugin',
			'data-plugin' => 'Ui.Input.TradingOutlet',
			'data-url' => static::getRefreshUrl(),
			'data-service' => static::getUserFieldServiceCode($userField),
			'data-used-keys' => implode('|', static::getUsedOptionValues()),
		];

		if ($userField['MULTIPLE'] !== 'N')
		{
			$result += [
				'multiple' => true,
				'size' => 1,
			];
		}

		return $result;
	}

	protected static function getRefreshUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/tradingoutlet/enum.php';
	}

	public static function getVariants($serviceCode, $optionValues, $ignoreCache = false)
	{
		if (!static::validateServiceCode($serviceCode) || !static::validateOptionValues($optionValues)) { return static::makeVariantsSkeleton(); }

		$sign = static::makeVariantsSign($serviceCode, $optionValues);
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = 60 * 15;
		$cacheKey = Market\Config::getLangPrefix() . 'CACHE_OUTLET_' . $sign;

		if ($cache->read($cacheTtl, $cacheKey) && !$ignoreCache)
		{
			$result = $cache->get($cacheKey);
		}
		else
		{
			$result = static::loadVariants($serviceCode, $optionValues);

			$cache->set($cacheKey, $result);
		}

		return $result;
	}

	protected static function validateServiceCode($serviceCode)
	{
		return (string)$serviceCode !== '';
	}

	protected static function validateOptionValues($optionValues)
	{
		$result = true;

		if (!is_array($optionValues))
		{
			$result = false;
		}
		else
		{
			foreach (static::getUsedOptionValues() as $key)
			{
				if (
					!isset($optionValues[$key])
					|| Market\Utils\Value::isEmpty($optionValues[$key])
				)
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	protected static function makeVariantsSign($serviceCode, array $optionValues)
	{
		return implode('|', [
			$serviceCode,
			static::makeOptionValuesSign($optionValues),
			isset($optionValues['page']) ? (int)$optionValues['page'] : 1,
		]);
	}

	protected static function makeOptionValuesSign(array $optionValues)
	{
		$parts = [];

		foreach (static::getUsedOptionValues() as $key)
		{
			if (!isset($optionValues[$key])) { continue; }

			$parts[] = $key . '=' . $optionValues[$key];
		}

		return implode('|', $parts);
	}

	protected static function getUsedOptionValues()
	{
		return [
			'OAUTH_CLIENT_ID',
			'OAUTH_TOKEN',
			'CAMPAIGN_ID',
		];
	}

	protected static function makeVariantsSkeleton()
	{
		return [
			'enum' => [],
			'hasNext' => false,
		];
	}

	protected static function loadVariants($serviceCode, array $optionValues)
	{
		$result = static::makeVariantsSkeleton();

		try
		{
			$service = Market\Trading\Service\Manager::createProvider($serviceCode);
			$options = $service->getOptions();

			$options->setValues($optionValues);

			$outletCollection = Market\Api\Model\OutletFacade::loadList($options, [
				'page' => isset($optionValues['page']) ? (int)$optionValues['page'] : 1,
			]);
			$paging = $outletCollection->getPaging();

			$result['enum'] = static::makeOutletCollectionEnum($outletCollection);
			$result['hasNext'] = $paging !== null && $paging->hasNext();
		}
		catch (Main\SystemException $exception)
		{
			// nothing
		}

		return $result;
	}

	protected static function makeOutletCollectionEnum(Market\Api\Model\OutletCollection $outletCollection)
	{
		$result = [];

		/** @var Market\Api\Model\Outlet $outlet */
		foreach ($outletCollection as $outlet)
		{
			$code = (string)$outlet->getShopOutletCode();
			$title = $outlet->getName() ?: $code;

			if ($code === '') { continue; }

			$result[] = [
				'ID' => htmlspecialcharsbx($code),
				'VALUE' => htmlspecialcharsbx($title),
			];
		}

		return $result;
	}
}