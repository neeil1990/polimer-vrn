<?php

namespace Yandex\Market\Export\Entity\Iblock\Offer\Property;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Iblock\Element\Property\Source
{
	public function getQueryFilter($filter, $select)
	{
		return [
			'OFFERS' => $this->buildQueryFilter($filter)
	    ];
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];
		$elementsByIblock = [];

		foreach ($elementList as $elementId => $element)
		{
			if (isset($element['PARENT_ID'])) // is offer
			{
				if (!isset($elementsByIblock[$element['IBLOCK_ID']]))
				{
					$elementsByIblock[$element['IBLOCK_ID']] = [];
				}

				$elementsByIblock[$element['IBLOCK_ID']][] = $element['ID'];
			}
		}

		if (!empty($elementsByIblock) && Main\Loader::includeModule('iblock'))
		{
			foreach ($elementsByIblock as $iblockId => $elementIds)
			{
				$propertyValuesList = $this->getPropertyValues($iblockId, $elementIds, $select, $queryContext);

				foreach ($propertyValuesList as $elementId => $propertyValues)
				{
					$result[$elementId] = $propertyValues;
				}
			}
		}

		return $result;
	}

	protected function getContextIblockId(array $context)
	{
		return isset($context['OFFER_IBLOCK_ID']) ? $context['OFFER_IBLOCK_ID'] : null;
	}

	protected function getLangPrefix()
	{
		return 'IBLOCK_OFFER_PROPERTY_';
	}
}