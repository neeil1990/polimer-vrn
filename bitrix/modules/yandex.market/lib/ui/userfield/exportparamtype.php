<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui;
use Yandex\Market\Export\Entity as ExportEntity;

class ExportParamType extends EnumerationType
{
	public static function GetList($arUserField)
	{
		$options = static::getUserFieldOptions($arUserField);
		$variants = static::allVariants($options);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	protected static function getUserFieldOptions($userField)
	{
		if (isset($userField['SETTINGS']['IBLOCK_ID']))
		{
			$result = [
				'IBLOCK_ID' => $userField['SETTINGS']['IBLOCK_ID'],
			];
		}
		else if (isset($userField['ROW']['PARENT_ROW']))
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

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		Ui\Plugin\ExportParam::load();

		$arHtmlControl['VALIGN'] = 'middle';

		$attributes = Helper\Attributes::extractFromSettings($arUserField['SETTINGS']);
		$attributes += static::makeSelectViewAttributes($arUserField);
		$attributes['name'] = $arUserField['FIELD_NAME'];
		$settings = static::makeSelectViewSettings($arUserField);
		$value = Helper\Value::asSingle($arUserField, $arHtmlControl);
		$options = static::getUserFieldOptions($arUserField);
		$enum = (string)$value !== '' ? static::makeEnumFromValues([$value], $options) : [];

		return View\Select::getControl($enum, $value, $attributes, $settings);
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		Ui\Plugin\ExportParam::load();

		$attributes = Helper\Attributes::extractFromSettings($userField['SETTINGS']);
		$attributes += static::makeSelectViewAttributes($userField);
		$settings = static::makeSelectViewSettings($userField);
		$values = Helper\Value::asMultiple($userField, $htmlControl);
		$options = static::getUserFieldOptions($userField);
		$enum = static::makeEnumFromValues($values, $options);

		return View\Collection::render(
			$userField['FIELD_NAME'],
			Helper\Value::asMultiple($userField, $htmlControl),
			static function($name, $value) use ($enum, $attributes, $settings) {
				$attributes['name'] = $name;

				return View\Select::getControl($enum, $value, $attributes, $settings);
			},
			Fieldset\Helper::makeChildAttributes($userField)
		);
	}

	protected static function makeSelectViewAttributes($userField)
	{
		$result = parent::makeSelectViewAttributes($userField);
		$result += [
			'class' => 'js-plugin',
			'data-plugin' => 'Ui.Input.ExportParam',
			'data-url' => static::getRefreshUrl(),
		];

		if (isset($userField['SETTINGS']['IBLOCK_ID']))
		{
			$result['data-iblock-id'] = is_array($userField['SETTINGS']['IBLOCK_ID'])
				? implode(',', $userField['SETTINGS']['IBLOCK_ID'])
				: $userField['SETTINGS']['IBLOCK_ID'];
		}

		return $result;
	}

	protected static function getRefreshUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/exportparam/enum.php';
	}

	protected static function makeEnumFromValues($values, $options)
	{
		$result = [];

		foreach ($values as $value)
		{
			$option = static::valueToEnum($value, $options);

			if ($option === null) { continue; }

			$result[] = $option;
		}

		return $result;
	}
	
	protected static function valueToEnum($value, $options)
	{
		try
		{
			if (!is_string($value)) { return null; }

			list($sourceType, $fieldKey) = explode(':', $value);

			if ((string)$sourceType === '' || (string)$fieldKey === '') { return null; }

			$source = ExportEntity\Manager::getSource($sourceType);

			foreach (static::makeContexts($options) as $context)
			{
				$field = $source->getField($fieldKey, $context);

				if ($field !== null)
				{
					$result = [
						'ID' => $value,
						'VALUE' => $field['VALUE'] ?: $field['ID'],
					];
				}
			}
		}
		catch (Main\SystemException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
			$result = null;
		}

		return $result;
	}

	protected static function allVariants($options)
	{
		$result = [];

		foreach (ExportEntity\Manager::getSourceTypeList() as $sourceType)
		{
			foreach (static::makeContexts($options) as $context)
			{
				$source = ExportEntity\Manager::getSource($sourceType);
				$fields = $source->getFields($context);

				$result += static::fieldsToVariants($source, $fields);
			}
		}

		return array_values($result);
	}

	public static function suggestVariants($query, $options)
	{
		$result = [];

		foreach (static::makeContexts($options) as $context)
		{
			foreach (ExportEntity\Manager::getSourceTypeList() as $sourceType)
			{
				$source = ExportEntity\Manager::getSource($sourceType);
				$fields = $source->suggestFields($query, $context);

				$result += static::fieldsToVariants($source, $fields);
			}
		}

		return array_values($result);
	}

	protected static function fieldsToVariants(ExportEntity\Reference\Source $source, $fields)
	{
		if (!is_array($fields)) { return []; }

		$group = $source->getTitle();
		$result = [];

		foreach ($fields as $field)
		{
			$key = $source->getType() . ':' . $field['ID'];
			$value = $field['VALUE'] ?: $field['ID'];

			$result[$key] = [
				'ID' => $key,
				'VALUE' => $value,
				'GROUP' => $group,
			];
		}

		return $result;
	}

	protected static function makeContexts($options)
	{
		$iblockIds =
			static::getUsedIblocksFromSettings($options)
			?: static::getUsedIblocksFromTradingOptions($options)
			?: static::getUsedIblockFromCatalog();
		$common = static::getCommonContext();
		$result = [];

		if (empty($iblockIds))
		{
			$result[] = $common;
		}
		else
		{
			foreach ($iblockIds as $iblockId)
			{
				$result[] = ExportEntity\Iblock\Provider::getContext($iblockId) + $common;
			}
		}

		return $result;
	}

	protected static function getCommonContext()
	{
		return  [
			'USER_GROUPS' => Market\Data\UserGroup::getDefaults(),
			'HAS_CATALOG' => Main\ModuleManager::isModuleInstalled('catalog'),
			'HAS_SALE' => Main\ModuleManager::isModuleInstalled('sale'),
		];
	}

	protected static function getUsedIblocksFromSettings($options)
	{
		return isset($options['IBLOCK_ID']) ? (array)$options['IBLOCK_ID'] : [];
	}

	protected static function getUsedIblocksFromTradingOptions($options)
	{
		if (empty($options['PRODUCT_SKU_FIELD']) || !is_array($options['PRODUCT_SKU_FIELD'])) { return []; }

		$found = [];

		foreach ($options['PRODUCT_SKU_FIELD'] as $map)
		{
			if (!isset($map['IBLOCK'], $map['FIELD'])) { continue; }

			$iblock = (int)$map['IBLOCK'];
			$field = (string)$map['FIELD'];

			if ($iblock <= 0 || $field === '') { continue; }

			$found[] = $iblock;
		}

		if (empty($found)) { return []; }

		$catalogs = static::getUsedIblockFromCatalog($found);

		if (empty($catalogs)) { return $found; }

		return $catalogs;
	}

	protected static function getUsedIblockFromCatalog($iblockIds = null)
	{
		$iblockTypes = Data\Catalog::getIblockTypes($iblockIds);
		$iblockTypes = array_filter($iblockTypes, static function($type) { return $type === Data\Catalog::TYPE_PRODUCT; });

		return array_keys($iblockTypes);
	}
}