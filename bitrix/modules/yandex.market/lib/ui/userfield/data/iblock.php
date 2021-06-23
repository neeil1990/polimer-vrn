<?php

namespace Yandex\Market\Ui\UserField\Data;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock as IblockModule;

class Iblock
{
	public static function getEnum()
	{
		$result = [];

		if (Main\Loader::includeModule('iblock'))
		{
			$query = IblockModule\IblockTable::getList([
				'filter' => [ '=ACTIVE' => 'Y' ],
				'select' => [ 'ID', 'NAME' ],
			]);

			while ($row = $query->fetch())
			{
				$title = '[' . $row['ID'] . '] ' . $row['NAME'];

				$result[] = [
					'ID' => $row['ID'],
					'VALUE' => $title,
				];
			}
		}

		return $result;
	}
}