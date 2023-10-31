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
	 * @param $userField
	 * @param $htmlControl
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		// options

		$activeGroup = null;
		$groups = [];
		$baseId = Helper\Attributes::convertNameToId($htmlControl['NAME']);
		$queryOptions = call_user_func(
			[ $userField['USER_TYPE']['CLASS_NAME'], 'getList' ],
			$userField
		);

		while ($option = $queryOptions->Fetch())
		{
			if (isset($option['GROUP']) && $option['GROUP'] !== $activeGroup)
			{
				$activeGroup = $option['GROUP'];
				$groups[$activeGroup] = [
					'TITLE' => $option['GROUP'],
					'CONTENT' => '',
					'CHECKED' => false,
				];
			}
			else if ($activeGroup === null)
			{
				$activeGroup = 0;
				$groups[$activeGroup] = [
					'CONTENT' => '',
					'CHECKED' => false,
				];
			}

			$optionHtmlId = $baseId . '_' . $option['ID'];
			$isChecked = !empty($htmlControl['VALUE']) && in_array($option['ID'], $htmlControl['VALUE']);

			$groups[$activeGroup]['HTML'] .=
				'<div>'
				. '<input class="adm-designed-checkbox" type="checkbox" name="' . $htmlControl['NAME'] . '" value="' . $option['ID'] . '" ' . ($isChecked ? 'checked' : '') . ' id="' . $optionHtmlId . '">'
				. '<label class="adm-designed-checkbox-label" for="' . $optionHtmlId . '"></label>'
				. '<label for="' . $optionHtmlId . '"> ' . $option['VALUE'] . '</label>'
				. '</div>';

			if ($isChecked)
			{
				$groups[$activeGroup]['CHECKED'] = true;
			}
		}

		// groups

		$hasChecked = count(array_filter(array_column($groups, 'CHECKED'))) > 0;
		$isFirstGroup = true;
		$result = '';

		foreach ($groups as $group)
		{
			if (!isset($group['TITLE']))
			{
				$result .= $group['HTML'];
				continue;
			}

			/** @noinspection HtmlUnknownAttribute */
			$result .= sprintf(
				'<details class="adm-iblock-section" %s>
					<summary class="adm-iblock-section-title">%s</summary>
					%s	
				</details>',
				$group['CHECKED'] || ($isFirstGroup && !$hasChecked) ? 'open' : '',
				$group['TITLE'],
				$group['HTML']
			);

			$isFirstGroup = false;
		}

		return $result;
	}
}