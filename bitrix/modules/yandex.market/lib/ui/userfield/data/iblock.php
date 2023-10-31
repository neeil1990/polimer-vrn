<?php

namespace Yandex\Market\Ui\UserField\Data;

use Bitrix\Main;
use Bitrix\Iblock as IblockModule;

class Iblock
{
	public static function getEnum($siteId = null)
	{
		if (!Main\Loader::includeModule('iblock')) { return []; }

		$result = [];
		$parameters = [
			'filter' => [ '=ACTIVE' => 'Y' ],
			'select' => [ 'ID', 'NAME' ],
		];

		if ($siteId !== null)
		{
			$parameters['filter']['=YM_IBLOCK_SITE.SITE_ID'] = $siteId;
			$parameters['runtime'] = [
				new Main\Entity\ReferenceField(
					'YM_IBLOCK_SITE',
					IblockModule\IblockSiteTable::class,
					[ '=this.ID' => 'ref.IBLOCK_ID' ]
				),
			];
		}

		$query = IblockModule\IblockTable::getList($parameters);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
			];
		}

		return $result;
	}
}