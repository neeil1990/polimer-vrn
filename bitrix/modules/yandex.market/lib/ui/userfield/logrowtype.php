<?php

namespace Yandex\Market\Ui\UserField;

class LogRowType extends StringType
{
	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = '';
		$value = null;

		if (!empty($arHtmlControl['VALUE']))
		{
			$value = $arHtmlControl['VALUE'];
		}
		else if (!empty($arUserField['VALUE']))
		{
			$value = $arUserField['VALUE'];
		}

		if (!empty($value['MESSAGE']))
		{
			$result = $value['MESSAGE'];
		}

		return $result;
	}

	public static function GetAdminListViewHtmlMulty($arUserField, $arHtmlControl)
	{
		$result = '';

		if (!empty($arUserField['VALUE']))
		{
			$existValues = [];

			foreach ($arUserField['VALUE'] as $value)
			{
				$valueHtml = static::GetAdminListViewHTML($arUserField, [
					'VALUE' => $value
				]);

				if (isset($existValues[$valueHtml]))
				{
					$existValues[$valueHtml]++;
				}
				else
				{
					$existValues[$valueHtml] = 1;
				}
			}

			foreach ($existValues as $valueHtml => $repeatCount)
			{
				$result .=
					($result === '' ? '' : '<br />')
					. $valueHtml
					. ($repeatCount > 1 ? ' (' . $repeatCount .  ')' : '');
			}
		}

		return $result;
	}
}