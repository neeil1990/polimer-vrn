<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

class Gift extends Offer
{
    public function getName()
    {
        return Market\Export\Run\Manager::STEP_GIFT;
    }

    public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
    {
        return $format->getGiftParentName();
    }

    public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
    {
        return $format->getGift();
    }

    protected function useHashCollision()
    {
        return false;
    }

    protected function getDataLogEntityType()
    {
        return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_GIFT;
    }

    protected function getStorageDataClass()
    {
        return Market\Export\Run\Storage\GiftTable::getClassName();
    }

    protected function getStorageRuntime()
    {
        return [
            new Main\Entity\ReferenceField('EXPORT_PROMO_GIFT', Market\Export\Run\Storage\PromoGiftTable::getClassName(), [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                '=this.ELEMENT_ID' => 'ref.ELEMENT_ID'
            ])
        ];
    }

    protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
    {
        return [];
    }

    protected function getStorageReadyFilter($queryContext, $isNeedFull = false)
    {
        $filter = [
            '=SETUP_ID' => $queryContext['SETUP_ID']
        ];

        if (!$isNeedFull)
        {
            switch ($this->getRunAction())
            {
                case 'change':
                case 'refresh':
                    $filter['>=TIMESTAMP_X'] = $this->getParameter('initTime');
                break;
            }
        }

        return $filter;
    }

    protected function getQueryExcludeFilterPrimary($queryContext)
    {
        return 0; // equal for all
    }

    protected function getQueryChangesFilter($changes, $context)
    {
        return []; // changes processed in getIblockConfigList
    }

	protected function getIgnoredTypeChanges()
	{
		return [
			Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY => true,
		];
	}

    protected function getStorageChangesFilter($changes, $context)
    {
        $isNeedFull = false;
        $result = [];

        if (!empty($changes))
        {
            $offerChanges = $changes;
            $selfChanges = [];
            $selfTypeList = [
                Market\Export\Run\Manager::ENTITY_TYPE_PROMO => true,
                Market\Export\Run\Manager::ENTITY_TYPE_GIFT => true
            ];

            foreach ($offerChanges as $changeType => $entityIds)
            {
                if (isset($selfTypeList[$changeType]))
                {
                    $selfChanges[$changeType] = $entityIds;
                    unset($offerChanges[$changeType]);
                }
            }

            // offer changes

            if (!empty($offerChanges))
            {
                $result[] = [
                    '>=EXPORT_PROMO_GIFT.TIMESTAMP_X' => $this->getParameter('initTime')
                ];
            }

            // self changes

            foreach ($selfChanges as $changeType => $entityIds)
            {
                switch ($changeType)
                {
                    case Market\Export\Run\Manager::ENTITY_TYPE_PROMO:
                        $result[] = [
                            '=EXPORT_PROMO_GIFT.PROMO_ID' => $entityIds
                        ];
                    break;

                    case Market\Export\Run\Manager::ENTITY_TYPE_GIFT:
                        $result[] = [
                            '=ELEMENT_ID' => $entityIds
                        ];
                    break;
                }
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

    protected function getIblockConfigList($isNeedAll = null)
    {
        $result = [];
        $setup = $this->getSetup();
        $context = $setup->getContext();
        $promoGiftList = $this->getPromoGiftList($context);

        if (!empty($promoGiftList))
        {
            $iblockLinkCollection = $setup->getIblockLinkCollection();

            foreach ($promoGiftList as $iblockId => $elementList)
            {
                $iblockLink = $iblockLinkCollection->getByIblockId($iblockId);
                $isOfferIblockId = false;
                $filterIndex = 0;

                if ($iblockLink === null)
                {
                    $iblockLink = $iblockLinkCollection->getByOfferIblockId($iblockId);

                    if ($iblockLink !== null)
                    {
                        $isOfferIblockId = true;
                        $iblockId = $iblockLink->getIblockId();
                    }
                    else
					{
						$catalogIblockId = Market\Export\Entity\Iblock\Provider::getCatalogIblockId($iblockId);

						if ($catalogIblockId !== null)
						{
							$iblockId = $catalogIblockId;
							$isOfferIblockId = true;
						}
					}
                }

                $iblockContext = $this->getIblockContext($iblockId, $iblockLink) + $context;

                $iblockConfig = [
                    'ID' => null,
                    'EXPORT_ALL' => false,
                    'TAG_DESCRIPTION_LIST' => $this->getTagDescriptionList($iblockContext, $iblockLink),
                    'FILTER_LIST' => [],
                    'CONTEXT' => $iblockContext,
                ];

                foreach (array_chunk($elementList, 500) as $elementChunk)
                {
                    $iblockConfig['FILTER_LIST'][] = [
                        'ID' => 'n' . $filterIndex,
                        'FILTER' => $this->getSourceFilter($elementChunk, $isOfferIblockId),
                        'CONTEXT' => [ 'IGNORE_EXCLUDE' => true ] // hasn't self context
                    ];

                    $filterIndex++;
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

	protected function getPromoGiftList($context)
    {
    	$elementIds = $this->getPromoGiftElementIds($context);

    	return $this->splitElementIdsByIblock($elementIds);
    }

    protected function getPromoGiftElementIds($context)
    {
    	$result = [];

        $filter = [
            '=SETUP_ID' => $context['SETUP_ID'],
            '=ELEMENT_TYPE' => Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_GIFT,
            '=STATUS' => static::STORAGE_STATUS_SUCCESS
        ];

        switch ($this->getRunAction())
        {
            case 'change':
            case 'refresh':
                $filter['>=TIMESTAMP_X'] = $this->getParameter('initTime');
            break;
        }

        $query = Market\Export\Run\Storage\PromoGiftTable::getList([
            'filter' => $filter,
            'select' => [ 'ELEMENT_ID' ],
        ]);

        while ($item = $query->fetch())
        {
	        $result[] = (int)$item['ELEMENT_ID'];
        }

        return $result;
    }

    protected function splitElementIdsByIblock($elementIds)
    {
    	if (empty($elementIds)) { return []; }

	    $result = [];

    	$query = Iblock\ElementTable::getList([
    		'filter' => [
    			'=ID' => $elementIds,
		    ],
		    'select' => [
		    	'IBLOCK_ID',
		    	'ID',
		    ]
	    ]);

    	while ($row = $query->fetch())
	    {
		    $iblockId = (int)$row['IBLOCK_ID'];

		    if (!isset($result[$iblockId]))
		    {
			    $result[$iblockId] = [];
		    }

		    $result[$iblockId][] = (int)$row['ID'];
	    }

	    return $result;
    }

    protected function getIblockContext($iblockId, Market\Export\IblockLink\Model $iblockLink = null)
    {
        $result = null;

        if ($iblockLink === null)
        {
            $result = Market\Export\Entity\Iblock\Provider::getContext($iblockId);
        }
        else
        {
            $result = $iblockLink->getContext();
        }

        return $result;
    }

    protected function getTagDescriptionList($context, Market\Export\IblockLink\Model $iblockLink = null)
    {
    	$selfTag = $this->getTag();
        $requiredTags = [
            'name' => true,
            'picture' => true,
	        $selfTag->getName() => true,
        ];
        $foundTags = [];
        $result = [];

        if ($iblockLink !== null)
        {
        	// primary

	        $primarySource = $this->getOfferPrimarySource($iblockLink, $context);

	        if ($primarySource !== null)
	        {
		        $result[] = [
			        'TAG' => $selfTag->getName(),
			        'VALUE' => null,
			        'ATTRIBUTES' => [
			        	$this->getTagPrimaryName($selfTag) => $primarySource,
			        ],
			        'SETTINGS' => null,
		        ];
	        }

	        // inner tags

	        $tagMap = [
		        'name' => [
			        'TAG' => 'name',
			        'VALUE' => true,
		        ],
		        'model' => [
			        'TAG' => 'name',
			        'VALUE' => true,
		        ],
		        'picture' => [
			        'TAG' => 'picture',
			        'VALUE' => true,
		        ],
	        ];

            foreach ($iblockLink->getTagDescriptionList() as $tag)
            {
                if (isset($tagMap[$tag['TAG']]))
                {
                	$mapDescription = $tagMap[$tag['TAG']];
                	$isAllPartsFound = true;
                	$giftTagDescription = [
                		'TAG' => $mapDescription['TAG'],
		                'VALUE' => null,
		                'ATTRIBUTES' => [],
		                'SETTINGS' => $tag['SETTINGS'],
	                ];

                	if (isset($mapDescription['VALUE']))
	                {
	                	if (!empty($tag['VALUE']))
		                {
		                    $giftTagDescription['VALUE'] = $tag['VALUE'];
	                    }
	                	else
		                {
		                	$isAllPartsFound = false;
		                }
	                }

                	if (isset($mapDescription['ATTRIBUTES']))
                	{
                		foreach ($mapDescription['ATTRIBUTES'] as $attributeName => $dummy)
		                {
		                	if (!empty($tag['ATTRIBUTES'][$attributeName]))
			                {
				                $giftTagDescription['ATTRIBUTES'][$attributeName] = $tag['ATTRIBUTES'][$attributeName];
			                }
		                	else
			                {
			                	$isAllPartsFound = false;
			                }
		                }
	                }

                	if ($isAllPartsFound)
	                {
	                    $foundTags[$giftTagDescription['TAG']] = true;
	                    $result[] = $giftTagDescription;
	                }
                }
            }
        }

        foreach ($requiredTags as $giftTagName => $dummy)
        {
            if (!isset($foundTags[$giftTagName]))
            {
                $defaultTagSources = $this->getDefaultTagDescription($giftTagName, $context);

                if (!empty($defaultTagSources))
                {
                    foreach ($defaultTagSources as $defaultTagSource)
                    {
                        $result[] = $defaultTagSource;
                    }
                }
                else
                {
                    throw new Main\SystemException('not found default description for gift tag ' . $giftTagName);
                }
            }
        }

        return $result;
    }

    protected function getDefaultTagDescription($tagName, $context)
    {
        $result = [];
	    $selfTag = $this->getTag();

        $fieldSource = Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD;
        $fallbackSource = null;

        if ($context['HAS_OFFER'])
		{
			$fallbackSource = $fieldSource;
			$fieldSource = Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD;
		}

        switch ($tagName)
        {
            case $selfTag->getName():
                $result[] = [
                    'TAG' => $tagName,
                    'VALUE' => null,
                    'ATTRIBUTES' => [
	                    $this->getTagPrimaryName($selfTag) => [
                            'TYPE' => $fieldSource,
                            'FIELD' => 'ID'
                        ]
                    ],
                    'SETTINGS' => null
                ];
            break;

            case 'name':
                $result[] = [
                    'TAG' => $tagName,
                    'VALUE' => [
                        'TYPE' => $fieldSource,
                        'FIELD' => 'NAME'
                    ],
                    'ATTRIBUTES' => [],
                    'SETTINGS' => null
                ];
            break;

            case 'picture':
                $result[] = [
                    'TAG' => $tagName,
                    'VALUE' => [
                        'TYPE' => $fieldSource,
                        'FIELD' => 'DETAIL_PICTURE'
                    ],
                    'ATTRIBUTES' => [],
                    'SETTINGS' => null
                ];

                $result[] = [
                    'TAG' => $tagName,
                    'VALUE' => [
                        'TYPE' => $fieldSource,
                        'FIELD' => 'PREVIEW_PICTURE'
                    ],
                    'ATTRIBUTES' => [],
                    'SETTINGS' => null
                ];

                if ($fallbackSource !== null)
				{
					$result[] = [
						'TAG' => $tagName,
						'VALUE' => [
							'TYPE' => $fallbackSource,
							'FIELD' => 'DETAIL_PICTURE'
						],
						'ATTRIBUTES' => [],
						'SETTINGS' => null
					];

					$result[] = [
						'TAG' => $tagName,
						'VALUE' => [
							'TYPE' => $fallbackSource,
							'FIELD' => 'PREVIEW_PICTURE'
						],
						'ATTRIBUTES' => [],
						'SETTINGS' => null
					];
				}
            break;
        }

        return $result;
    }

    protected function getSourceFilter($elementList, $isOfferIblockId)
    {
        $fieldSource = (
            $isOfferIblockId
                ? Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD
                : Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD
        );

        return [
            $fieldSource => [
                [
                    'FIELD' => 'ID',
                    'COMPARE' => '=',
                    'VALUE' => $elementList
                ]
            ]
        ];
    }

    protected function isAllowDeleteParent()
    {
        return true;
    }

    protected function isAllowPublicDelete()
    {
        return true;
    }
}