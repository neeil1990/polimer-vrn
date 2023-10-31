<?php
namespace Yandex\Market\Export\Entity\Iblock\Property\Feature\Set;

use Bitrix\Iblock;
use Yandex\Market\Reference\Concerns;

class Filter extends Skeleton
{
	use Concerns\HasMessage;

	public function key()
	{
		return 'iblock.FILTER';
	}

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function properties()
	{
		$result = [];

		$sourcesMap = $this->sourcesMap();
		$iblockMap = array_flip($sourcesMap);

		$queryProperties = Iblock\SectionPropertyTable::getList([
			'select' => [ 'PROPERTY_ID', 'PROPERTY_IBLOCK_ID' => 'PROPERTY.IBLOCK_ID' ],
			'filter' => [
				'=IBLOCK_ID' => array_values($sourcesMap),
				'=SMART_FILTER' => 'Y'
			],
			'order' => [
				'PROPERTY.SORT' => 'ASC',
				'PROPERTY.ID' => 'ASC',
			],
		]);

		while ($property = $queryProperties->fetch())
		{
			$iblockId = (int)$property['PROPERTY_IBLOCK_ID'];
			$propertyId = (int)$property['PROPERTY_ID'];
			$sourceType = $iblockMap[$iblockId];

			if (!isset($result[$sourceType]))
			{
				$result[$sourceType] = [];
			}

			$result[$sourceType][$propertyId] = $propertyId;
		}

		return $result;
	}
}