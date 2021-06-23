<?php

namespace Yandex\Market\Ui\UserField;

class PromoType extends ReferenceType
{
	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = parent::GetAdminListViewHTML($arUserField, $arHtmlControl);
		$promoId = !empty($arHtmlControl['VALUE']) ? (int)$arHtmlControl['VALUE'] : 0;

		if ($promoId > 0)
		{
			$result = '<a href="/bitrix/admin/yamarket_promo_edit.php?lang=' . LANGUAGE_ID . '&id=' . $promoId . '">' . $result .  '</a>';
		}

		return $result;
	}
}