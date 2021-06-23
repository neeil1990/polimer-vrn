<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class OrderPropertyType extends EnumerationType
{
	protected static $variants = [];

	public static function GetList($arUserField)
	{
		$personType = static::extractUserFieldPersonType($arUserField);
		$variants = static::getVariants($personType);
		$variants = static::filterVariants($arUserField, $variants);
		$variants = static::markDefaultVariant($arUserField, $variants);
		$variants = static::applyVariantsDefaultGroup($arUserField, $variants);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$htmlId = Market\Ui\UserField\Helper\Attributes::convertNameToId($arHtmlControl['NAME']) . '_REFRESH';

		if (!isset($arUserField['SETTINGS']['ATTRIBUTES']))
		{
			$arUserField['SETTINGS']['ATTRIBUTES'] = [];
		}

		$arUserField['SETTINGS']['ATTRIBUTES']['id'] = $htmlId;

		$result = parent::GetEditFormHTML($arUserField, $arHtmlControl);
		$result .= static::getRefreshScript($arUserField, $htmlId);
		$result .= static::getAddButton($arUserField, $htmlId);

		static::loadLangMessages();

		return $result;
	}

	protected static function filterVariants($userField, $variants)
	{
		return static::filterDisabledTypes($userField, $variants);
	}

	protected static function filterDisabledTypes($userField, $variants)
	{
		$disabledTypes = static::getDisabledTypes($userField);
		$disabledMap = array_flip($disabledTypes);

		foreach ($variants as $variantKey => $variant)
		{
			if (isset($variant['TYPE'], $disabledMap[$variant['TYPE']]))
			{
				unset($variants[$variantKey]);
			}
		}

		return $variants;
	}

	protected static function getDisabledTypes($userField)
	{
		return isset($userField['SETTINGS']['DISABLED_TYPES'])
			? (array)$userField['SETTINGS']['DISABLED_TYPES']
			: [ 'LOCATION' ];
	}

	protected static function markDefaultVariant($userField, $variants)
	{
		if (isset($userField['SETTINGS']['TYPE']))
		{
			$type = $userField['SETTINGS']['TYPE'];
			$defaultKey = null;
			$methods = [
				'resolveDefaultVariantByType',
				'resolveDefaultVariantByCodeMatch',
				'resolveDefaultVariantByCodeSubstring',
				'resolveDefaultVariantBySimilarType',
			];

			foreach ($methods as $method)
			{
				$methodResult = static::$method($type, $variants);

				if ($methodResult !== null)
				{
					$defaultKey = $methodResult;
					break;
				}
			}

			if ($defaultKey !== null)
			{
				$variants[$defaultKey]['DEF'] = 'Y';
			}
		}

		return $variants;
	}

	protected static function resolveDefaultVariantByType($type, $variants)
	{
		$result = null;

		foreach ($variants as $variantKey => $variant)
		{
			if (isset($variant['TYPE']) && $variant['TYPE'] === $type)
			{
				$result = $variantKey;
				break;
			}
		}

		return $result;
	}

	protected static function resolveDefaultVariantByCodeMatch($type, $variants)
	{
		$result = null;

		foreach ($variants as $variantKey => $variant)
		{
			if (isset($variant['CODE']) && strcasecmp($variant['CODE'], $type) === 0)
			{
				$result = $variantKey;
				break;
			}
		}

		return $result;
	}

	protected static function resolveDefaultVariantByCodeSubstring($type, $variants)
	{
		$result = null;

		foreach ($variants as $variantKey => $variant)
		{
			if (isset($variant['CODE']) && Market\Data\TextString::getPosition($variant['CODE'], $type) !== false)
			{
				$result = $variantKey;
				break;
			}
		}

		return $result;
	}

	protected static function resolveDefaultVariantBySimilarType($type, $variants)
	{
		$similarTypes = static::getVariantSimilarTypes($type);
		$result = null;

		foreach ($similarTypes as $similarType)
		{
			$matchKey = static::resolveDefaultVariantByType($similarType, $variants);

			if ($matchKey !== null)
			{
				$result = $matchKey;
				break;
			}
		}

		return $result;
	}

	protected static function getVariantSimilarTypes($type)
	{
		switch ($type)
		{
			case 'LAST_NAME':
			case 'FIRST_NAME':
			case 'MIDDLE_NAME':
				$result = [ 'NAME' ];
			break;

			default:
				$result = [];
			break;
		}

		return $result;
	}

	protected static function applyVariantsDefaultGroup($userField, $variants)
	{
		if (empty($userField['SETTINGS']['DEFAULT_GROUP'])) { return $variants; }

		foreach ($variants as &$variant)
		{
			if (!empty($variant['GROUP'])) { continue; }

			$variant['GROUP'] = $userField['SETTINGS']['DEFAULT_GROUP'];
		}
		unset($variant);

		return $variants;
	}

	protected static function getRefreshScript($userField, $htmlId)
	{
		$personTypeValue = static::extractUserFieldPersonType($userField);
		$personTypeField = static::getUserFieldPersonTypeField($userField);

		Market\Ui\Assets::loadPlugin('Ui.Input.OrderPropertyRefresh');

		return Market\Ui\Assets::initPlugin('Ui.Input.OrderPropertyRefresh', '#' . $htmlId, [
			'type' => isset($userField['SETTINGS']['TYPE']) ? $userField['SETTINGS']['TYPE'] : null,
			'refreshUrl' => static::getRefreshUrl(),
			'personTypeId' => $personTypeValue,
			'personTypeElement' => 'select[name="' . $personTypeField . '"]',
		]);
	}

	protected static function loadLangMessages()
	{
		Market\Ui\Assets::loadMessages([
			'USER_FIELD_ORDER_PROPERTY_REFRESH_FAIL',
			'USER_FIELD_ORDER_PROPERTY_ADD_FAIL',
		]);
	}

	protected static function getRefreshUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/orderproperty/enum.php';
	}

	protected static function getAddButton($userField, $htmlId)
	{
		if (empty($userField['SETTINGS']['ADD_URL'])) { return ''; }

		$buttonId = $htmlId . '_ADD';
		$addUrl = $userField['SETTINGS']['ADD_URL'];

		return
			' '
			. static::getAddButtonHtml($buttonId)
			. static::getAddButtonScript($userField, $buttonId, $addUrl);
	}

	protected static function getAddButtonHtml($htmlId)
	{
		return sprintf(
			'<input type="button" value="%s" id="%s" />',
			htmlspecialcharsbx(Market\Config::getLang('USER_FIELD_ORDER_PROPERTY_ADD_BUTTON')),
			$htmlId
		);
	}

	protected static function getAddButtonScript($userField, $htmlId, $addUrl)
	{
		$personTypeValue = static::extractUserFieldPersonType($userField);
		$personTypeField = static::getUserFieldPersonTypeField($userField);
		$serviceCode = static::extractUserFieldServiceCode($userField);

		Market\Ui\Assets::loadPlugin('Ui.Input.OrderPropertyAdd');

		return Market\Ui\Assets::initPlugin('Ui.Input.OrderPropertyAdd', '#' . $htmlId, [
			'url' => $addUrl,
			'serviceCode' => $serviceCode,
			'personTypeId' => $personTypeValue,
			'personTypeElement' => 'select[name="' . $personTypeField . '"]',
		]);
	}

	protected static function extractUserFieldPersonType($userField)
	{
		$fieldPersonType = static::getUserFieldPersonTypeField($userField);
		$result = null;

		if ($fieldPersonType !== null && isset($userField['ROW'][$fieldPersonType]))
		{
			$result = $userField['ROW'][$fieldPersonType];
		}
		else if (isset($userField['SETTINGS']['PERSON_TYPE_DEFAULT']))
		{
			$result = $userField['SETTINGS']['PERSON_TYPE_DEFAULT'];
		}

		return $result;
	}

	protected static function getUserFieldPersonTypeField($userField)
	{
		$result = null;

		if (isset($userField['SETTINGS']['PERSON_TYPE_FIELD']))
		{
			$result = $userField['SETTINGS']['PERSON_TYPE_FIELD'];
		}

		return $result;
	}

	protected static function extractUserFieldServiceCode($userField)
	{
		return isset($userField['SETTINGS']['SERVICE_CODE'])
			? $userField['SETTINGS']['SERVICE_CODE']
			: null;
	}

	public static function getVariants($personType)
	{
		if (!isset(static::$variants[$personType]))
		{
			static::$variants[$personType] = static::loadVariants($personType);
		}

		return static::$variants[$personType];
	}

	protected static function loadVariants($personType)
	{
		$environment = Market\Trading\Entity\Manager::createEnvironment();

		try
		{
			$property = $environment->getProperty();
			$result = $property->getEnum($personType);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}
}