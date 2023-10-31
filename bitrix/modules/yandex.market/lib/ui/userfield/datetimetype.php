<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class DateTimeType
{
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends()
	{
		return Main\UserField\Types\DateTimeType::class;
	}

	public static function getCompatibleExtends()
	{
		return \CUserTypeDateTime::class;
	}

	public static function getUserTypeDescription()
	{
		return static::callParent('getUserTypeDescription');
	}

	public static function checkFields($arUserField, $value)
	{
		return static::callParent('checkFields', [$arUserField, $value]);
	}

	public static function onBeforeSave($arUserField, $value)
	{
		return static::callParent('onBeforeSave', [$arUserField, $value]);
	}

	public static function getFilterHTML($arUserField, $arHtmlControl)
	{
		return static::callParent('getFilterHTML', [$arUserField, $arHtmlControl]);
	}

	public static function getFilterData($arUserField, $arHtmlControl)
	{
		return static::callParent('getFilterData', [$arUserField, $arHtmlControl]);
	}

	public static function getAdminListViewHTML($arUserField, $arHtmlControl)
	{
		return static::callParent('getAdminListViewHTML', [$arUserField, $arHtmlControl]);
	}

	public static function getEditFormHtml($userField, $additionalParameters)
	{
		if (empty($userField['ENTITY_VALUE_ID']))
		{
			$userField['ENTITY_VALUE_ID'] = 1;
		}

		return static::callParent('getEditFormHtml', [$userField, $additionalParameters]);
	}
}