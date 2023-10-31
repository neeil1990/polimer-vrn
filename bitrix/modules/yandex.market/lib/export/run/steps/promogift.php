<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

class PromoGift extends Offer
{
    public function getName()
    {
        return Market\Export\Run\Manager::STEP_PROMO_GIFT;
    }

    public function isVirtual()
    {
        return true;
    }

    public function isTypedTag()
    {
        return true;
    }

    public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
    {
        return $format->getPromoGiftParentName();
    }

    public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
    {
		return $format->getPromoGift($type);
	}

    protected function useHashCollision()
    {
        return false;
    }

	protected function getDataLogEntityType()
	{
		return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT;
	}

	protected function getDataLogEntityReference()
    {
        return [
            '=this.ENTITY_PARENT' => 'ref.SETUP_ID',
            '=this.ENTITY_ID_PART1' => 'ref.PROMO_ID',
            '=this.ENTITY_ID_PART2' => 'ref.ELEMENT_ID',
        ];
    }

    protected function getStorageDataClass()
	{
		return Market\Export\Run\Storage\PromoGiftTable::getClassName();
	}

	protected function getStoragePrimaryList()
    {
        return [
            'SETUP_ID',
            'PROMO_ID',
            'ELEMENT_ID'
        ];
    }

    protected function getStorageRuntime()
    {
        return [
            new Main\Entity\ReferenceField('EXPORT_OFFER', Market\Export\Run\Storage\OfferTable::getClassName(), [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                '=this.ELEMENT_ID' => 'ref.ELEMENT_ID'
            ])
        ];
    }

    protected function getExistDataStorageFilter(array $context)
    {
        return [
            '=SETUP_ID' => $context['SETUP_ID'],
            '=PROMO_ID' => $context['PROMO_ID'],
        ];
    }

    protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
    {
        return [
            'PROMO_ID' => $context['PROMO_ID'],
            'ELEMENT_TYPE' => $tagValues->getType(),
	        'PARENT_ID' => isset($element['PARENT_ID']) ? $element['PARENT_ID'] : '',
        ];
    }

    protected function getStorageReadyFilter($queryContext, $isNeedFull = false)
    {
        $filter = [
            '=SETUP_ID' => $queryContext['SETUP_ID']
        ];

        if (isset($queryContext['PROMO_ID']))
        {
            $filter['=PROMO_ID'] = $queryContext['PROMO_ID'];
        }

        if (!$isNeedFull)
        {
            switch ($this->getRunAction())
            {
                case 'change':
                case 'refresh':
                    $filter['>=TIMESTAMP_X'] = $this->getParameter('initTimeUTC');
                break;
            }
        }

        return $filter;
    }

    protected function getStorageChangesFilter($changes, $context)
    {
        $isNeedFull = false;
        $result = [];

        if (!empty($changes))
        {
            $isNeedCheckProduct = false;

            // self changes

            foreach ($changes as $changeType => $entityIds)
            {
                switch ($changeType)
                {
                    case Market\Export\Run\Manager::ENTITY_TYPE_PROMO:
                        $result[] = [
                            '=PROMO_ID' => $entityIds
                        ];
                    break;

                    case Market\Export\Run\Manager::ENTITY_TYPE_GIFT:
                    case Market\Export\Run\Manager::ENTITY_TYPE_OFFER:

                        if (!isset($context['OFFER_IBLOCK_ID']))
                        {
                            $result[] = [
                                '=ELEMENT_ID' => $entityIds
                            ];
                        }
                        else // convert product change to offer
                        {
                            $elementIdsMap = array_flip($entityIds);

                            $queryOffers = \CIBlockElement::GetList(
                                array(),
                                array(
                                    'IBLOCK_ID' => $context['OFFER_IBLOCK_ID'],
                                    '=PROPERTY_' . $context['OFFER_PROPERTY_ID'] => $entityIds
                                ),
                                false,
                                false,
                                array(
                                    'IBLOCK_ID',
                                    'ID',
                                    'PROPERTY_' . $context['OFFER_PROPERTY_ID']
                                )
                            );

                            while ($offer = $queryOffers->Fetch())
                            {
                                $offerId = (int)$offer['ID'];
                                $offerElementId = (int)$offer['PROPERTY_' . $context['OFFER_PROPERTY_ID'] . '_VALUE'];

                                if (isset($elementIdsMap[$offerElementId]))
                                {
                                    unset($elementIdsMap[$offerElementId]);
                                }

                                $elementIdsMap[$offerId] = true;
                            }

                            if (!empty($elementIdsMap))
                            {
                                $result[] = [
                                    '=ELEMENT_ID' => array_keys($elementIdsMap)
                                ];
                            }
                        }

                    break;

                    default:
                        $isNeedCheckProduct = true;
                    break;
                }
            }

            // offer changes

            if ($isNeedCheckProduct)
            {
                $result[] = [
                    '>=EXPORT_OFFER.TIMESTAMP_X' => $this->getParameter('initTimeUTC')
                ];
            }
        }

        if ($isNeedFull)
        {
            $result = [];
        }
        else if (empty($result))
        {
            $result = null;
        }
        else if (count($result) > 1)
        {
            $result['LOGIC'] = 'OR';
        }

        return $result;
    }

    protected function getIgnoredTypeChanges()
    {
        return [
			Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY => true,
			Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION => true,
		];
    }

    protected function getIblockConfigList($isNeedAll = null)
    {
        $setup = $this->getSetup();
        $result = [];
        $promoChangedMap = null;
	    $iblockLinkCollection = $setup->getIblockLinkCollection();

        if ($this->getRunAction() === 'change')
        {
            $changes = $this->getChanges();

            if (isset($changes[Market\Export\Run\Manager::ENTITY_TYPE_PROMO]) && count($changes) === 1) // has only changes in promo
            {
                $promoChangedMap = array_flip($changes[Market\Export\Run\Manager::ENTITY_TYPE_PROMO]);
            }
        }

        /** @var Market\Export\Promo\Model $promo */
        foreach ($setup->getPromoCollection() as $promo)
        {
            $promoId = $promo->getInternalId();

            if (
                ($promoChangedMap !== null && !isset($promoChangedMap[$promoId])) // has changes, but in other promo
                || !$promo->isActive()
                || !$promo->isActiveDate()
                || !$promo->isSupportGift()
            )
            {
                continue;
            }

            /** @var Market\Export\PromoGift\Model $promoGift */
            foreach ($promo->getGiftCollection() as $promoGift)
            {
	            $promoProductIblockId = $promoGift->getIblockId();
	            $iblockLink = $iblockLinkCollection->getByIblockId($promoProductIblockId);
	            $context = $promoGift->getContext();
	            $offerPrimarySource = $iblockLink !== null ? $this->getOfferPrimarySource($iblockLink, $context) : null;

                $iblockConfig = [
                    'ID' => $promoGift->getId(),
                    'EXPORT_ALL' => false,
                    'TAG_DESCRIPTION_LIST' => $promoGift->getTagDescriptionList($offerPrimarySource),
                    'FILTER_LIST' => [],
                    'CONTEXT' => $context,
                    'LIMIT' => $promo->getGiftLimit()
                ];
				$isFirstFilter = true;

                /** @var \Yandex\Market\Export\Filter\Model $filterModel */
                foreach ($promoGift->getFilterCollection() as $filterModel)
                {
                    $iblockConfig['FILTER_LIST'][] = [
                        'ID' => $filterModel->getInternalId(),
                        'FILTER' => $filterModel->getSourceFilter(),
                        'CONTEXT' => [ 'IGNORE_EXCLUDE' => $isFirstFilter ] // hasn't self context
                    ];

					$isFirstFilter = false;
                }

                $result[] = $iblockConfig;
            }
        }

        return $result;
    }

    protected function applyQueryFilterModifications($queryFilter, $queryContext)
	{
		return $queryFilter;
	}

	protected function processExportElementList(&$elementList, &$parentList, $context)
    {
        if (!$context['EXPORT_GIFT'])
        {
            $this->filterOnlyUsedElementList($elementList, $context);
        }

        $this->sortExportElementList($elementList);
    }

    /**
     * Фильтруем список элементов, по наличию в выгрузке профиля
     *
     * @param $elementList array
     * @param $context array
     */
    protected function filterOnlyUsedElementList(&$elementList, $context)
    {
        $idList = array_keys($elementList);
        $usedIdMap = $this->getExistOfferList($idList, $context);

        foreach ($elementList as $elementId => $element)
        {
            if (!isset($usedIdMap[$elementId]))
            {
                unset($elementList[$elementId]);
            }
        }
    }

    protected function extractElementListValues($sourceSelect, $elementList, $parentList, $queryContext)
    {
        $result = parent::extractElementListValues($sourceSelect, $elementList, $parentList, $queryContext);

        if ($queryContext['EXPORT_GIFT'])
        {
	        $idList = array_keys($result);
	        $existIdMap = $this->getExistOfferList($idList, $queryContext);

	        foreach ($result as $elementId => &$sourceValues)
	        {
	            $sourceValues['TYPE'] = (
	                isset($existIdMap[$elementId])
	                    ? Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER
	                    : Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_GIFT
	            );
	        }
	        unset($sourceValues);
        }
        else
        {
	        foreach ($result as $elementId => &$sourceValues)
	        {
		        $sourceValues['TYPE'] = Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER;
	        }
	        unset($sourceValues);
        }

        return $result;
    }

	protected function sortExportElementList(&$elementList)
	{
        $parentElementCount = [];
        $parentElementIndex = [];
        $isNeedSort = false;

        foreach ($elementList as $elementId => $element)
        {
            $elementParentIndex = 0;

            if (!isset($element['PARENT_ID']))
            {
                // nothing
            }
            else if (!isset($parentElementCount[$element['PARENT_ID']]))
            {
                $parentElementCount[$element['PARENT_ID']] = 0;
                $elementParentIndex = 0;
            }
            else
            {
                $isNeedSort = true;
                $elementParentIndex = ++$parentElementCount[$element['PARENT_ID']];
            }

            $parentElementIndex[$elementId] = $elementParentIndex;
        }

        if ($isNeedSort)
        {
            uksort($elementList, function($aId, $bId) use ($parentElementIndex) {
                $aParentIndex = $parentElementIndex[$aId];
                $bParentIndex = $parentElementIndex[$bId];

                if ($aParentIndex === $bParentIndex) { return 0; }

                return ($aParentIndex < $bParentIndex ? -1 : 1);
            });
        }
	}

	protected function getExistOfferList($elementIdList, $context)
	{
		$result = [];

		if (!empty($elementIdList))
		{
			$queryExistOfferList = Market\Export\Run\Storage\OfferTable::getList([
				'filter' => [
					'=SETUP_ID' => $context['SETUP_ID'],
					'=ELEMENT_ID' => $elementIdList,
					'=STATUS' => static::STORAGE_STATUS_SUCCESS
				],
				'select' => [
					'ELEMENT_ID'
				]
			]);

			while ($existOffer = $queryExistOfferList->fetch())
			{
				$result[$existOffer['ELEMENT_ID']] = true;
			}
		}

		return $result;
	}

    protected function writeDataLog($tagResultList, $context)
    {
        $newTagResultList = [];

        foreach ($tagResultList as $elementId => $tagResult)
        {
            $newTagResultList[$context['PROMO_ID'] . ':' . $elementId] = $tagResult;
        }

        parent::writeDataLog($newTagResultList, $context);
    }
}