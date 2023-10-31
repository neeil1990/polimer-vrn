<?php
namespace Yandex\Market\Data\Iblock;

use Bitrix\Main;

class Element
{
	private static $elementIblock = [];

	public static function iblockId($id)
	{
		$id = (int)$id;

		if ($id <= 0) { return null; }

		if (!isset(self::$elementIblock[$id]))
		{
			if (!Main\Loader::includeModule('iblock')) { return null; }

			self::$elementIblock[$id] = \CIBlockElement::GetIBlockByID($id);
		}

		return self::$elementIblock[$id] ?: null;
	}
}