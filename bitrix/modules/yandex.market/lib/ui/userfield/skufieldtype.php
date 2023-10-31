<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class SkuFieldType
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function SanitizeFields($userField, $value)
	{
		$result = null;

		if (static::isSingleValue($value) && (int)$value['IBLOCK'] > 0 && (string)$value['FIELD'] !== '')
		{
			$iblockId = (int)$value['IBLOCK'];
			$field = (string)$value['FIELD'];

			if ($iblockId > 0 && $field !== '')
			{
				$result = [
					'IBLOCK' => $iblockId,
					'FIELD' => $field,
				];
			}
		}

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl)
	{
		Market\Ui\Assets::loadPlugins([
			'Plugin.Base',
			'Field.Reference.Base',
			'Field.SkuField.Source',
			'Field.SkuField.Row',
		]);

		$htmlId = Helper\Attributes::convertNameToId($htmlControl['NAME']);
		$value = static::getUserFieldValueAsSingle($userField, $htmlControl);
		$iblockOptions = static::getIblockEnum();
		$fieldOptions = [];
		$loadedFieldOptions = [];

		if (!empty($value['IBLOCK']))
		{
			$fieldOptions = static::getFieldEnum($value['IBLOCK']);
			$loadedFieldOptions[$value['IBLOCK']] = $fieldOptions;
		}
		else if (!empty($iblockOptions))
		{
			$firstIblockOption = reset($iblockOptions);
			$fieldOptions = static::getFieldEnum($firstIblockOption['ID']);
			$loadedFieldOptions[$firstIblockOption['ID']] = $fieldOptions;
		}

		$result = '<table border="0">';
		$result .= static::getRowInputHtml($iblockOptions, $fieldOptions, $htmlControl['NAME'], $value);
		$result .= '</table>';
		$result .= static::initializeFieldEnumSource($htmlId, $loadedFieldOptions);

		return $result;
	}

	protected static function getUserFieldValueAsSingle($userField, $htmlControl)
	{
		if ($userField['ENTITY_VALUE_ID'] < 1 && !empty($userField['SETTINGS']['DEFAULT_VALUE']))
		{
			$result = static::valueAsSingle($userField['SETTINGS']['DEFAULT_VALUE']);
		}
		else if (isset($userField['VALUE']))
		{
			$result = static::valueAsSingle($userField['VALUE']);
		}
		else
		{
			$result = static::valueAsSingle($htmlControl['VALUE']);
		}

		return $result;
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		Market\Ui\Assets::loadPlugins([
			'Plugin.Base',
			'Field.Reference.Base',
			'Field.Reference.Collection',
			'Field.SkuField.Source',
			'Field.SkuField.Row',
			'Field.SkuField.Table',
		]);

		$values = static::getUserFieldValueAsMultiple($userField, $htmlControl);
		$valueIndex = 0;
		$iblockOptions = static::getIblockEnum();
		$loadedFieldOptions = [];
		$firstIblockOption = reset($iblockOptions);
		$firstIblockId = $firstIblockOption !== false ? (int)$firstIblockOption['ID'] : null;
		$htmlId = Market\Ui\UserField\Helper\Attributes::convertNameToId($htmlControl['NAME']) . '_TABLE';
		$inputName = preg_replace('/\[]$/', '', $htmlControl['NAME']);

		$result = '<div class="js-plugin" data-plugin="Field.SkuField.Table" data-base-name="' . $inputName . '" id="' . htmlspecialcharsbx($htmlId) . '">';
		$result .= '<table border="0">';

		if (!empty($values))
		{
			foreach ($values as $value)
			{
				$iblockId = !empty($value['IBLOCK']) ? (int)$value['IBLOCK'] : $firstIblockId;

				if (isset($loadedFieldOptions[$iblockId]))
				{
					$fieldOptions = $loadedFieldOptions[$iblockId];
				}
				else if ($iblockId > 0)
				{
					$fieldOptions = static::getFieldEnum($iblockId);
					$loadedFieldOptions[$iblockId] = $fieldOptions;
				}
				else
				{
					$fieldOptions = [];
				}

				$result .= static::getRowInputHtml($iblockOptions, $fieldOptions, $inputName . '[' . $valueIndex . ']', $value, true);

				++$valueIndex;
			}
		}
		else
		{
			if ($firstIblockId > 0)
			{
				$fieldOptions = static::getFieldEnum($firstIblockId);
				$loadedFieldOptions[$firstIblockId] = $fieldOptions;
			}
			else
			{
				$fieldOptions = [];
			}

			$result .= static::getRowInputHtml($iblockOptions, $fieldOptions, $inputName . '[' . $valueIndex . ']', null, true);
		}

		$result .= '</table>';
		$result .= '<input class="adm-btn js-sku-field__add" type="button" value="' . htmlspecialcharsbx(static::getLang('USER_FIELD_SKU_FIELD_ADD')) . '" />';
		$result .= '</div>';

		$result .= static::initializeFieldEnumSource($htmlId, $loadedFieldOptions);

		return $result;
	}

	protected static function getUserFieldValueAsMultiple($userField, $htmlControl)
	{
		if ($userField['ENTITY_VALUE_ID'] < 1 && !empty($userField['SETTINGS']['DEFAULT_VALUE']))
		{
			$result = static::valueAsMultiple($userField['SETTINGS']['DEFAULT_VALUE']);
		}
		else if (!empty($userField['VALUE']))
		{
			$result = static::valueAsMultiple($userField['VALUE']);
		}
		else if (!empty($htmlControl['VALUE']))
		{
			$result = static::valueAsMultiple($htmlControl['VALUE']);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected static function getRowInputHtml($iblockOptions, $fieldOptions, $name, $value, $isMultiple = false)
	{
		$valueIblock = isset($value['IBLOCK']) ? $value['IBLOCK'] : null;
		$valueField = isset($value['FIELD']) ? $value['FIELD'] : null;
		$result = '';

		if ($isMultiple)
		{
			$result .= '<tr class="js-sku-field-row">';
		}
		else
		{
			$htmlId = Helper\Attributes::convertNameToId($name);
			$result .= '<tr class="js-plugin" data-plugin="Field.SkuField.Row" id="' . $htmlId . '">';
		}

		$result .= '<td>';
		$result .= View\Select::getControl($iblockOptions, $valueIblock, [
			'name' => $name . '[IBLOCK]',
			'class' => 'js-sku-field-row__input',
			'data-name' => 'IBLOCK',
			'style' => 'max-width: 220px;',
		]);
		$result .= '</td>';

		$result .= '<td>';
		$result .= View\Select::getControl($fieldOptions, $valueField, [
			'name' => $name . '[FIELD]',
			'class' => 'js-sku-field-row__input',
			'data-name' => 'FIELD',
			'style' => 'width: 220px;',
		], [
			'ALLOW_NO_VALUE' => 'Y',
		]);
		$result .= '</td>';

		if ($isMultiple)
		{
			$result .= '<td>';
			$result .= '<button class="adm-btn js-sku-field-row__delete" type="button" title="' . htmlspecialcharsbx(static::getLang('USER_FIELD_SKU_FIELD_DELETE')) . '">-</button>';
			$result .= '</td>';
		}

		$result .= '</tr>';

		return $result;
	}

	protected static function initializeFieldEnumSource($htmlId, $loadedFieldEnum)
	{
		$url = static::getFieldRefreshUrl();

		return '<script>
			(function() {
				var SkuField = BX.namespace(\'YandexMarket.Field.SkuField\');
				var element = $("#' . $htmlId . '");
				var source = SkuField.Source.getInstance(element);
				var url = "' . $url . '";
				var fieldsEnum = ' . Market\Utils::jsonEncode($loadedFieldEnum, JSON_UNESCAPED_UNICODE) . ';
				var iblockId;
				
				source.setUrl(url);
				
				for (iblockId in fieldsEnum) {
					if (fieldsEnum.hasOwnProperty(iblockId)) {						
						source.addEnum(iblockId, fieldsEnum[iblockId]);
					}
				}
			})();
		</script>';
	}

	protected static function getIblockEnum()
	{
		$enum = Data\Iblock::getEnum();
		$catalogTypes = Data\Catalog::getIblockTypes();

		if (!empty($catalogTypes))
		{
			$enum = Data\Catalog::groupEnum($enum, $catalogTypes);
		}

		return $enum;
	}

	public static function getFieldRefreshUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/skufield/enum.php';
	}

	public static function getFieldEnum($iblockId)
	{
		$environment = Market\Trading\Entity\Manager::createEnvironment();
		$product = $environment->getProduct();

		return $product->getFieldEnum($iblockId);
	}

	protected static function valueAsSingle($value)
	{
		if (static::isSingleValue($value))
		{
			$result = $value;
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected static function isSingleValue($value)
	{
		return (
			is_array($value)
			&& (array_key_exists('IBLOCK', $value) || array_key_exists('FIELD', $value))
		);
	}

	protected static function valueAsMultiple($value)
	{
		return static::isMultipleValue($value) ? $value : [ $value ];
	}

	protected static function isMultipleValue($value)
	{
		return (is_array($value) && !static::isSingleValue($value));
	}

	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function ymExportValue(array $userField, $value, array $row = null)
	{
		$isOriginSingle = static::isSingleValue($value);
		$valueMultiple = static::valueAsMultiple($value);

		if (empty($valueMultiple)) { return $isOriginSingle ? null : []; }

		$result = [];
		$iblockEnum = static::getIblockEnum();
		$iblockMap = array_column($iblockEnum, 'VALUE', 'ID');

		foreach (static::valueAsMultiple($value) as $one)
		{
			if (!isset($one['IBLOCK'], $one['FIELD']))
			{
				$result[] = $one;
				continue;
			}

			if (!isset($iblockMap[$one['IBLOCK']]))
			{
				$result[] = [ $one['IBLOCK'], $one['FIELD'] ];
				continue;
			}

			$iblockId = $one['IBLOCK'];
			$iblockName = $iblockMap[$one['IBLOCK']];
			$fieldEnum = static::getFieldEnum($iblockId);
			$fieldMap = array_column($fieldEnum, 'VALUE', 'ID');
			$fieldName = isset($fieldMap[$one['FIELD']]) ? $fieldMap[$one['FIELD']] : $one['FIELD'];

			$result[] = [
				$iblockName,
				$fieldName,
				Data\Catalog::getCatalogTypeTitle($iblockId),
			];
		}

		return $isOriginSingle ? reset($result) : $result;
	}
}