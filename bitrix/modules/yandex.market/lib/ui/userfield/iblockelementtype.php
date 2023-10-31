<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;

class IblockElementType extends StringType
{
	protected static $elementDataCache = [];

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = '';

		if ((string)$arHtmlControl['VALUE'] !== '')
		{
			$elementData = static::getIblockElementData($arHtmlControl['VALUE']);
			$result = '[' . $arHtmlControl['VALUE'] . ']';

			if ($elementData)
			{
				$elementUrl = \CIBlock::GetAdminElementEditLink($elementData['IBLOCK_ID'], $arHtmlControl['VALUE']);
				$result = '<a href="' . $elementUrl .'">' . $result . ' ' . $elementData['NAME'] . '</a>';
			}
		}

		return $result;
	}

	protected static function getIblockElementData($id)
	{
		$result = false;
		$id = (int)$id;

		if ($id <= 0)
		{
			// nothing
		}
		else if (isset(static::$elementDataCache[$id]))
		{
			$result = static::$elementDataCache[$id];
		}
		else if (Main\Loader::includeModule('iblock'))
		{
			$query = \CIBlockElement::GetList(
				[],
				[ '=ID' => $id ],
				false,
				false,
				[ 'IBLOCK_ID', 'NAME' ]
			);

			if ($item = $query->Fetch())
			{
				$result = $item;
			}

			static::$elementDataCache[$id] = $result;
		}

		return $result;
	}
}