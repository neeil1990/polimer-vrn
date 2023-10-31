<?php

namespace Yandex\Market\Ui\UserField;

class SetupType extends ReferenceType
{
	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = parent::GetAdminListViewHTML($arUserField, $arHtmlControl);
		$setupId = !empty($arHtmlControl['VALUE']) ? (int)$arHtmlControl['VALUE'] : 0;

		if ($setupId > 0 && isset($arUserField['SETTINGS']['EDIT_URL']))
		{
			$editUrl = static::compileUrl($arUserField['SETTINGS']['EDIT_URL'], [
				'ID' => $setupId,
			]);

			$result = '<a href="' . htmlspecialcharsbx($editUrl) . '">' . $result .  '</a>';
		}

		return $result;
	}

	protected static function compileUrl($template, array $vars)
	{
		$result = $template;

		foreach ($vars as $key => $value)
		{
			$holder = '#' . $key . '#';
			$result = str_replace($holder, $value, $result);
		}

		return $result;
	}
}