<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class TradingOutletType extends EnumerationType
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function GetList($userField)
	{
		$serviceCode = static::getUserFieldServiceCode($userField);
		$optionValues = static::getUserFieldOptionValues($userField);
		$outlets = static::getVariants($serviceCode, $optionValues);

		$result = new \CDBResult();
		$result->InitFromArray($outlets);

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

	public static function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
	{
		$result = parent::GetEditFormHTMLMulty($arUserField, $arHtmlControl);

		if (Market\Data\TextString::getPosition($result, '<table') !== false)
		{
			$refreshButton = static::getRefreshButton($arUserField, [
				'data-parent-element' => 'table',
			]);

			$result = static::insertTableRefreshButton($result, $refreshButton);
		}

		return $result;
	}

	protected static function getRefreshButton($userField, array $attributes = [])
	{
		$optionValues = static::getUserFieldOptionValues($userField);
		$valuesSign = static::makeOptionValuesSign($optionValues);

		Market\Ui\Assets::loadPlugins([
			'Ui.Input.TradingOutletFetcher',
			'Ui.Input.TradingOutlet',
		]);

		static::loadLangMessages();

		return sprintf('<input %s />', Helper\Attributes::stringify($attributes + [
			'type' => 'button',
			'value' => static::getLang('USER_FIELD_TRADING_OUTLET_REFRESH'),
			'class' => 'js-plugin',
			'data-plugin' => 'Ui.Input.TradingOutlet',
			'data-service' => static::getUserFieldServiceCode($userField),
			'data-refresh-url' => static::getRefreshUrl(),
			'data-used-sign' => $valuesSign,
			'data-used-keys' => implode('|', static::getUsedOptionValues()),
		]));
	}

	protected static function loadLangMessages()
	{
		Market\Ui\Assets::loadMessages([
			'USER_FIELD_TRADING_OUTLET_REFRESH_FAIL',
		]);
	}

	protected static function getRefreshUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/tradingoutlet/enum.php';
	}

	protected static function insertTableRefreshButton($tableHtml, $refreshButton)
	{
		$lastCellPosition = Market\Data\TextString::getLastPosition($tableHtml, '</td>');
		$result = $tableHtml;

		if ($lastCellPosition !== false)
		{
			$result = Market\Data\TextString::getSubstring($tableHtml, 0, $lastCellPosition);
			$result	.= $refreshButton;
			$result	.= Market\Data\TextString::getSubstring($tableHtml, $lastCellPosition);
		}

		return $result;
	}

	public static function getVariants($serviceCode, $optionValues, $ignoreCache = false)
	{
		if (!static::validateServiceCode($serviceCode) || !static::validateOptionValues($optionValues)) { return []; }

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
			static::makeOptionValuesSign($optionValues)
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

	protected static function loadVariants($serviceCode, array $optionValues)
	{
		try
		{
			$service = Market\Trading\Service\Manager::createProvider($serviceCode);
			$options = $service->getOptions();

			$options->setValues($optionValues);

			$outletCollection = Market\Api\Model\OutletFacade::loadList($options);
			$result = static::makeOutletCollectionEnum($outletCollection);
		}
		catch (Main\SystemException $exception)
		{
			$result = [];
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