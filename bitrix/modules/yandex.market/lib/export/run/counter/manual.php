<?php

namespace Yandex\Market\Export\Run\Counter;

use Yandex\Market;

class Manual extends Base
{
	protected $excludeList = [];
	protected $distinctList = [];

	public function start()
	{
		// nothing
	}

	public function count($filter, $context)
	{
		if ($filter['DIRECTION'] === 'OFFER')
		{
			$result = $this->countByOffers($filter, $context);
		}
		else
		{
			$result = $this->countByElements($filter, $context);
		}

		return $result;
	}

	protected function countByElements($filter, $context)
	{
		$result = 0;
		$pageOffset = 0;
		$elementSelect = $this->getElementSelect($context);
		$elementFilterOriginal = $filter['ELEMENT'];

		do
		{
			$pageElementCount = 0;
			$parentList = [];
			$elementFilter = $elementFilterOriginal;

			if ($pageOffset > 0)
			{
				$elementFilter[] = [ '>ID' => $pageOffset ];
			}

			$queryElementList = \CIBlockElement::GetList(
				[ 'ID' => 'ASC' ],
				$elementFilter,
				false,
				[ 'nTopCount' => $context['PAGE_SIZE'], 'checkOutOfRange' => true ],
				$elementSelect
			);

			while ($element = $queryElementList->Fetch())
			{
				if ($context['USE_DISTINCT'] && isset($this->distinctList[$element['ID']]))
				{
					// nothing
				}
				else if ($context['CATALOG_TYPE_COMPATIBILITY'])
				{
					$parentList[$element['ID']] = true;

					$this->excludeList[$element['ID']] = true;
					++$result;
				}
				else if ($this->isElementHasOffers($element, $context))
				{
					$parentList[$element['ID']] = true;
				}
				else if (!isset($this->excludeList[$element['ID']]))
				{
					$this->excludeList[$element['ID']] = true;
					++$result;
				}

				$pageOffset = (int)$element['ID'];
				$pageElementCount++;
			}

			if (!empty($parentList) && $context['HAS_OFFER']) // has parents by catalog_type
			{
				$skuPropertyKey = 'PROPERTY_' . $context['OFFER_PROPERTY_ID'];
				$skuPropertyValueKey = $skuPropertyKey . '_VALUE';

				$offerSelect = [
					'IBLOCK_ID',
					'ID',
					$skuPropertyKey
				];

				$offerFilter = $filter['OFFERS'];
				$offerFilter['=' . $skuPropertyKey] = array_keys($parentList);

				$queryOffers = \CIBlockElement::GetList(
					[],
					$offerFilter,
					false,
					false,
					$offerSelect
				);

				while ($offer = $queryOffers->Fetch())
				{
					$offerElementId = (int)$offer[$skuPropertyValueKey];

					if (
						isset($parentList[$offerElementId])
						&& (!$context['USE_DISTINCT'] || !isset($this->distinctList[$offerElementId]))
					)
					{
						if ($context['CATALOG_TYPE_COMPATIBILITY'] && isset($this->excludeList[$offerElementId]))
						{
							unset($this->excludeList[$offerElementId]);
							--$result;
						}

						if (!isset($this->excludeList[$offer['ID']]))
						{
							$this->excludeList[$offer['ID']] = true;
							++$result;
						}

						if ($context['USE_DISTINCT'])
						{
							$this->distinctList[$offerElementId] = true;
						}
					}
				}
			}
		}
		while ($context['PAGE_SIZE'] <= $pageElementCount); // has next (iblock DISTINCT)

		return $result;
	}

	protected function countByOffers($filter, $context)
	{
		$result = 0;
		$pageOffset = 0;

		if (!$context['HAS_OFFER']) { return $result; }

		do
		{
			$pageElementCount = 0;
			$parentMap = [];

			$skuPropertyKey = 'PROPERTY_' . $context['OFFER_PROPERTY_ID'];
			$skuPropertyValueKey = $skuPropertyKey . '_VALUE';

			$offerFilter = $filter['OFFERS'];

			if ($pageOffset > 0)
			{
				$offerFilter[] = [ '>ID' => $pageOffset ];
			}

			$queryOffers = \CIBlockElement::GetList(
				[ 'ID' => 'ASC' ],
				$offerFilter,
				false,
				[ 'nTopCount' => $context['PAGE_SIZE'], 'checkOutOfRange' => true ],
				[ 'IBLOCK_ID', 'ID', $skuPropertyKey ]
			);

			while ($offer = $queryOffers->Fetch())
			{
				$offerId = (int)$offer['ID'];
				$parentId = (int)$offer[$skuPropertyValueKey];

				if ($parentId > 0 && !isset($this->excludeList[$offerId]))
				{
					if (!isset($parentMap[$parentId]))
					{
						$parentMap[$parentId] = [];
					}

					$parentMap[$parentId][] = $offerId;

					$this->excludeList[$offerId] = true;
					++$result;
				}

				$pageOffset = $offerId;
				$pageElementCount++;
			}

			if (!empty($parentMap)) // fetch exists parents
			{
				$foundParents = [];
				$elementFilter = $filter['ELEMENT'];
				$elementFilter['=ID'] = array_keys($parentMap);

				$queryElements = \CIBlockElement::GetList(
					[],
					$elementFilter,
					false,
					false,
					[ 'IBLOCK_ID', 'ID' ]
				);

				while ($element = $queryElements->Fetch())
				{
					$foundParents[$element['ID']] = true;
				}

				foreach ($parentMap as $parentId => $offerIds)
				{
					if (!isset($foundParents[$parentId]))
					{
						$result -= count($offerIds);
					}
				}
			}
		}
		while ($context['PAGE_SIZE'] <= $pageElementCount); // has next (iblock DISTINCT)

		return $result;
	}

	public function finish()
	{
		$this->excludeList = [];
		$this->distinctList = [];
	}

	protected function getElementSelect($context)
	{
		$result = [ 'IBLOCK_ID', 'ID' ];

		if (!$context['CATALOG_TYPE_COMPATIBILITY'] && !$context['OFFER_ONLY'])
		{
			$result[] = Market\Export\Entity\Catalog\Provider::useCatalogShortFields()
				? 'TYPE'
				: 'CATALOG_TYPE';
		}

		return $result;
	}

	protected function isElementHasOffers($element, $context)
	{
		$result = false;

		if (!$context['HAS_OFFER'])
		{
			$result = false;
		}
		else if ($context['OFFER_ONLY'])
		{
			$result = true;
		}
		else if (isset($element['TYPE']))
		{
			$result = (int)$element['TYPE'] === Market\Export\Run\Steps\Offer::ELEMENT_TYPE_SKU;
		}
		else if (isset($element['CATALOG_TYPE']))
		{
			$result = (int)$element['CATALOG_TYPE'] === Market\Export\Run\Steps\Offer::ELEMENT_TYPE_SKU;
		}
		else if (array_key_exists('CATALOG_TYPE', $element) || array_key_exists('TYPE', $element))
		{
			$result = true;
		}

		return $result;
	}
}