<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Catalog;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class IblockType extends EnumerationType
{
	protected static $values;

	public static function GetList($arUserField)
	{
		$values = static::getValues();

		$result = new \CDBResult();
		$result->InitFromArray($values);

		return $result;
	}

	protected static function getValues()
	{
		if (static::$values === null)
		{
			static::$values = static::loadValues();
		}

		return static::$values;
	}

	protected static function loadValues()
	{
		$result = Data\Iblock::getEnum();
		$catalogTypes = Data\Catalog::getIblockTypes();

		if (!empty($catalogTypes))
		{
			$notProductCatalogs = array_diff($catalogTypes, [ Data\Catalog::TYPE_PRODUCT ]);

			$result = Helper\Enum::unsetByMap($result, $notProductCatalogs);
			$result = Data\Catalog::groupEnum($result, $catalogTypes, 'MULTIPLE');
		}

		return $result;
	}

	/**
	 * @param $arUserField
	 * @param $arHtmlControl
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
	{
		$result = '';
		$isFirstOption = true;
		$activeGroup = null;
		$baseId = Helper\Attributes::convertNameToId($arHtmlControl['NAME']);
		$queryOptions = call_user_func(
			[ $arUserField['USER_TYPE']['CLASS_NAME'], 'getList' ],
			$arUserField
		);

		while ($option = $queryOptions->Fetch())
		{
			if (isset($option['GROUP']) && $option['GROUP'] !== $activeGroup)
			{
				$result .= '<div class="adm-iblock-section-' . ($isFirstOption ? 'catalog' : 'other') . '">' . $option['GROUP'] . '</div>';

				$activeGroup = $option['GROUP'];
			}

			$optionHtmlId = $baseId . '_' . $option['ID'];
			$isChecked = !empty($arHtmlControl['VALUE']) && in_array($option['ID'], $arHtmlControl['VALUE']);

			$result .=
				'<div>'
				. '<input class="adm-designed-checkbox" type="checkbox" name="' . $arHtmlControl['NAME'] . '" value="' . $option['ID'] . '" ' . ($isChecked ? 'checked' : '') . ' id="' . $optionHtmlId . '">'
				. '<label class="adm-designed-checkbox-label" for="' . $optionHtmlId . '"></label>'
				. '<label for="' . $optionHtmlId . '"> ' . $option['VALUE'] . '</label>'
				. '</div>';

			$isFirstOption = false;
		}

		return $result;
	}
}