<?php

namespace Yandex\Market\Export\Xml\Attribute;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

class ConditionType extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'type',
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];
		$iblockList = [];

		if (!empty($context['IBLOCK_ID']))
		{
			$iblockList[Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY] = $context['IBLOCK_ID'];
		}

		if (!empty($context['OFFER_IBLOCK_ID']))
		{
			$iblockList[Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY] = $context['OFFER_IBLOCK_ID'];
		}

		if (!empty($iblockList) && Main\Loader::includeModule('iblock'))
		{
			foreach ($iblockList as $sourceType => $iblockId)
			{
				$query = Iblock\PropertyTable::getList([
					'filter' => [
						'=IBLOCK_ID' => (int)$iblockId,
						'=USER_TYPE' => Market\Ui\UserField\ConditionType\Property::USER_TYPE
					],
					'select' => [
						'ID'
					]
				]);

				while ($property = $query->fetch())
				{
					$result[] = [
						'TYPE' => $sourceType,
						'FIELD' => $property['ID']
					];
				}
			}
		}

		return $result;
	}
}