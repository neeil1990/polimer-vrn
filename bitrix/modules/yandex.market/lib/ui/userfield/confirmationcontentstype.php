<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class ConfirmationContentsType extends StringType
{
	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if ((string)$arHtmlControl['VALUE'] === '')
		{
			$result = '&nbsp;';
		}
		else if (isset($arUserField['ROW']['DOMAIN'], $arUserField['ROW']['BEHAVIOR']))
		{
			try
			{
				$behavior = Market\Confirmation\Behavior\Manager::getBehavior($arUserField['ROW']['BEHAVIOR']);
				$result = $behavior->formatDisplay($arUserField['ROW']['DOMAIN'], $arHtmlControl['VALUE']);
			}
			catch (Main\SystemException $exception)
			{
				$result = $arHtmlControl['VALUE'];
			}
		}
		else
		{
			$result = $arHtmlControl['VALUE'];
		}

		return $result;
	}
}