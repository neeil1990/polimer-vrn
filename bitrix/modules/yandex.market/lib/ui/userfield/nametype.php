<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class NameType extends StringType
{
	public static function getAdminListViewHtml($userField, $additionalParameters)
	{
		$value = (string)$additionalParameters['VALUE'];
		$result = '&mdash;';

		if ($value !== '')
		{
			$url = (string)static::getUserFieldRowValue($userField, 'URL');
			$icon = (string)static::getUserFieldRowValue($userField, 'ICON');
			$hasUrl = ($url !== '');
			$hasIcon = ($icon !== '');

			$result = htmlspecialcharsbx($value);

			if ($hasIcon)
			{
				$result = static::applyListViewIcon($result, $icon);
			}

			if ($hasUrl)
			{
				$result = static::applyListViewUrl($result, $url, $hasIcon);
			}
		}

		return $result;
	}

	protected static function getUserFieldRowValue($userField, $key)
	{
		$settingName = $key . '_FIELD';

		if (isset($userField['SETTINGS'][$settingName]))
		{
			$fieldName = $userField['SETTINGS'][$settingName];
		}
		else
		{
			$fieldName = 'ROW_' . $key;
		}

		return isset($userField['ROW'][$fieldName]) ? $userField['ROW'][$fieldName] : null;
	}

	protected static function applyListViewIcon($content, $icon)
	{
		$template =
			'<span class="adm-submenu-item-link-icon adm-list-table-icon %s"></span>'
			. '<span class="adm-list-table-link">%s</span>';

		return sprintf($template, $icon, $content);
	}

	protected static function applyListViewUrl($content, $url, $hasIcon)
	{
		$attributes = $hasIcon ? 'class="adm-list-table-icon-link"' : '';
		$url = \CHTTP::URN2URI($url); // prevent ajax mode in ui.grid

		return sprintf(
			'<a href="%s" %s>%s</a>',
			htmlspecialcharsbx($url),
			$attributes,
			$content
		);
	}
}