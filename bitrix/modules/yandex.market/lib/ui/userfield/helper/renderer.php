<?php

namespace Yandex\Market\Ui\UserField\Helper;

use Bitrix\Main;
use Yandex\Market;

class Renderer
{
	const VALUE_HOLDER = 'HOLDER';

	public static function getViewHtml($userField, $value = null, $row = null)
	{
		global $USER_FIELD_MANAGER;

		$userField = Field::extendValue($userField, $value, $row);

		if ($value === null && isset($userField['VALUE']))
		{
			$value = $userField['VALUE'];
		}

		$controlValue = static::sanitizeControlValue(
			$value,
			$userField['MULTIPLE'] !== 'N'
		);
		$controlValue = Field::unifyValue($controlValue);

		return $USER_FIELD_MANAGER->getListView($userField, $controlValue);
	}

	public static function getEditRow($userField, $value = null, $row = null)
	{
		global $USER_FIELD_MANAGER;

		$userField = Field::extendValue($userField, $value, $row);

		$html = $USER_FIELD_MANAGER->GetEditFormHTML(false, null, $userField);

		return static::parseEditHtml($html);
	}

	public static function getEditHtml($userField, $value = null, $row = null)
	{
		$result = static::getEditRow($userField, $value, $row);

		return $result['CONTROL'];
	}

	protected static function sanitizeControlValue($value, $isMultiple)
	{
		if ($isMultiple)
		{
			$result = (array)$value;

			foreach ($result as &$itemValue)
			{
				if (is_array($itemValue))
				{
					$itemValue = static::VALUE_HOLDER;
				}
			}
			unset($itemValue);
		}
		else if (is_array($value))
		{
			$result = static::VALUE_HOLDER;
		}
		else
		{
			$result = $value;
		}

		return $result;
	}

	public static function extractEditHtmlControl($html)
	{
		$parsed = static::parseEditHtml($html);

		return $parsed['CONTROL'];
	}

	public static function parseEditHtml($html)
	{
		$result = [
			'ROW_CLASS' => '',
			'VALIGN' => '',
			'CONTROL' => $html,
		];

		if (preg_match('/^<tr(.*?)>(?:<td(.*?)>.*?<\/td>)?<td.*?>(.*)<\/td><\/tr>$/s', $html, $match))
		{
			$rowAttributes = trim($match[1]);
			$rowClassName = '';
			$titleAttributes = trim($match[2]);
			$titleVerticalAlign = null;

			if (preg_match('/class="(.*?)"/', $rowAttributes, $rowMatches))
			{
				$rowClassName = $rowMatches[1];
			}

			if (preg_match('/valign="(.*?)"/', $titleAttributes, $titleMatches))
			{
				$titleVerticalAlign = $titleMatches[1];
			}
			else if (Market\Data\TextString::getPosition($titleAttributes, 'adm-detail-valign-top') !== false)
			{
				$titleVerticalAlign = 'top';
			}

			$result['ROW_CLASS'] = $rowClassName;
			$result['VALIGN'] = $titleVerticalAlign;
			$result['CONTROL'] = $match[3];
		}

		return $result;
	}
}