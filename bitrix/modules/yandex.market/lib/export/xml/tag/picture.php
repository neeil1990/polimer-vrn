<?php

namespace Yandex\Market\Export\Xml\Tag;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

class Picture extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'picture',
			'value_type' => Market\Type\Manager::TYPE_FILE,
			'max_count' => 10
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = $this->getSourceFieldRecommendation(Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD);
		$propertySources = [
			$context['IBLOCK_ID'] => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY
		];

		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$propertySources[$context['OFFER_IBLOCK_ID']] = Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY;

			$result = array_merge(
				$result,
				$this->getSourceFieldRecommendation(Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD)
			);
		}

		$result = array_merge(
			$result,
			$this->getSourcePropertyRecommendation($propertySources)
		);

		return $result;
	}

	protected function getSourceFieldRecommendation($sourceType)
	{
		return [
			[
				'TYPE' => $sourceType,
				'FIELD' => 'DETAIL_PICTURE',
			],
			[
				'TYPE' => $sourceType,
				'FIELD' => 'PREVIEW_PICTURE',
			],
		];
	}

	protected function getSourcePropertyRecommendation($propertySources)
	{
		$result = [];
		$iblockIds = array_keys($propertySources);

		if (Main\Loader::includeModule('iblock'))
		{
			$query = Iblock\PropertyTable::getList([
				'filter' => [
					'=IBLOCK_ID' => $iblockIds,
					'=ACTIVE' => 'Y',
					'=PROPERTY_TYPE' => 'F',
					[
						'LOGIC' => 'OR',
						[ '=CODE' => 'MORE_PHOTO' ],
						[ '%FILE_TYPE' => [ 'jpg', 'jpeg', 'png' ] ]
					]
				],
				'select' => [ 'ID', 'IBLOCK_ID' ]
			]);

			while ($row = $query->fetch())
			{
				if (isset($propertySources[$row['IBLOCK_ID']]))
				{
					$result[] = [
						'TYPE' => $propertySources[$row['IBLOCK_ID']],
						'FIELD' => $row['ID'],
					];
				}
			}
		}

		return $result;
	}
}
