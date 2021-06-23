<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class PromoProduct extends Offer
{
    public function getName()
    {
        return Market\Export\Run\Manager::STEP_PROMO_PRODUCT;
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
		return $format->getPromoProductParentName();
	}

	public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
    {
		return $format->getPromoProduct($type);
	}

	protected function useHashCollision()
	{
		return false;
	}

	protected function getDataLogEntityType()
	{
		return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT;
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
		return Market\Export\Run\Storage\PromoProductTable::getClassName();
	}

	protected function getStoragePrimaryList()
    {
        return [
            'SETUP_ID',
            'PROMO_ID',
            'ELEMENT_TYPE',
            'ELEMENT_ID'
        ];
    }

    protected function getStorageRuntime()
    {
        return [
            new Main\Entity\ReferenceField('EXPORT_OFFER', Market\Export\Run\Storage\OfferTable::getClassName(), [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                [
                	'LOGIC' => 'OR',
					[ '=this.ELEMENT_TYPE' => [ '?', Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_OFFER ] ],
					[ '=this.ELEMENT_TYPE' => [ '?', Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_OFFER_WITH_DISCOUNT ] ],
				],
                '=this.ELEMENT_ID' => 'ref.ELEMENT_ID'
            ]),
            new Main\Entity\ReferenceField('EXPORT_CATEGORY', Market\Export\Run\Storage\CategoryTable::getClassName(), [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                '=this.ELEMENT_TYPE' => [ '?', Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_CATEGORY ],
                '=this.ELEMENT_ID' => 'ref.ELEMENT_ID'
            ])
        ];
    }

    protected function getIblockConfigList($isNeedAll = null)
    {
        $result = [];
        $setup = $this->getSetup();
        $iblockLinkCollection = $setup->getIblockLinkCollection();
        $promoCollection = $setup->getPromoCollection();
        $promoChangedMap = null;
        $isChangeOnlyCurrency = false;

        if ($this->getRunAction() === 'change')
        {
            $changes = $this->getChanges();

            if (count($changes) === 1)
			{
				reset($changes);
				$changeType = key($changes);

				if ($changeType === Market\Export\Run\Manager::ENTITY_TYPE_PROMO) // has only changes in promo
				{
					$promoChangedMap = array_flip($changes[$changeType]);
				}
				else if ($changeType === Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY)
				{
					$isChangeOnlyCurrency = true;
				}
			}
        }

        /** @var Market\Export\Promo\Model $promo */
        foreach ($promoCollection as $promo)
        {
            $promoId = $promo->getInternalId();

            if (
                ($promoChangedMap !== null && !isset($promoChangedMap[$promoId])) // has changes, but in other promo
				|| ($isChangeOnlyCurrency && !$promo->hasProductDiscountPrice()) // changed currency, but promo hasn't apply to product price
                || !$promo->isActive()
                || !$promo->isActiveDate()
            )
            {
                continue;
            }

            /** @var Market\Export\PromoProduct\Model $promoProduct */
            foreach ($promo->getProductCollection() as $promoProduct)
            {
                $promoProductIblockId = $promoProduct->getIblockId();
                $iblockLink = $iblockLinkCollection->getByIblockId($promoProductIblockId);

                if ($iblockLink === null) { continue; } // no promo iblock in setup

                $promoProductContext = $promoProduct->getContext();
                $promoProductContext['IBLOCK_LINK'] = $iblockLink;
                $promoProductContext['PROMO'] = $promo;

	            $offerPrimarySource = $this->getOfferPrimarySource($iblockLink, $promoProductContext);
                $tagDescriptionList = $promoProduct->getTagDescriptionList($offerPrimarySource);

                if ($promoProductContext['HAS_DISCOUNT_PRICE'])
                {
	                $tagDescriptionList = $this->fillDiscountPriceTagDescriptionList($tagDescriptionList, $iblockLink);
	                $tagDescriptionList = $this->fillOriginalPriceTagDescriptionList($tagDescriptionList, $iblockLink);
                }

                $iblockConfig = [
                    'ID' => $promoProduct->getId(),
                    'EXPORT_ALL' => false,
                    'TAG_DESCRIPTION_LIST' => $tagDescriptionList,
                    'FILTER_LIST' => [],
                    'CONTEXT' => $promoProductContext
                ];
				$isFirstFilter = true;

                /** @var \Yandex\Market\Export\Filter\Model $filterModel */
                foreach ($promoProduct->getFilterCollection() as $filterModel)
                {
                    $iblockConfig['FILTER_LIST'][] = [
                        'ID' => $filterModel->getInternalId(),
                        'FILTER' => $filterModel->getSourceFilter(),
                        'CONTEXT' => [
                            'FILTER_DATA' => $filterModel->getPlainData(),
							'IGNORE_EXCLUDE' => $isFirstFilter
                        ]
                    ];

					$isFirstFilter = false;
                }

                $result[] = $iblockConfig;
            }
        }

        return $result;
    }

    protected function makeQueryFilters($sourceFilterList, $sourceSelectList, $queryContext, $changesFilter = null)
    {
	    $categoryFilterIds = null;

	    if (!$queryContext['HAS_DISCOUNT_PRICE'] && $this->getRunAction() !== null) // can be category filter
	    {
		    $categoryFilterIds = $this->getCategoryFilterIdList($sourceFilterList, $queryContext['IBLOCK_LINK']);
	    }

	    if ($categoryFilterIds !== null)
	    {
	    	$result = [
	    		[
	    			'SPECIAL' => 'category',
				    'VALUE' => $categoryFilterIds,
			    ]
		    ];
	    }
	    else
	    {
	    	$result = parent::makeQueryFilters($sourceFilterList, $sourceSelectList, $queryContext, $changesFilter);
	    }

	    return $result;
    }

    protected function exportIblockFilter($queryFilter, $sourceSelect, $querySelect, $tagDescriptionList, $context, $queryOffset = null, $limit = null, $successCount = 0)
    {
	    $result = [
		    'OFFSET' => null,
		    'SUCCESS_COUNT' => 0
	    ];

    	if (!isset($queryFilter['SPECIAL']))
	    {
		    $context['ELEMENT_TYPE'] = (
			    $context['HAS_DISCOUNT_PRICE']
				    ? Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_OFFER_WITH_DISCOUNT
				    : Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_OFFER
		    );

	    	$result = parent::exportIblockFilter($queryFilter, $sourceSelect, $querySelect, $tagDescriptionList, $context, $queryOffset, $limit, $successCount);
	    }
    	else if ($queryFilter['SPECIAL'] === 'category')
	    {
			$context['ELEMENT_TYPE'] = Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_CATEGORY;

			$usedCategoryIdList = $this->getUsedCategoryIdList($queryFilter['VALUE'], $context);

			if (!empty($usedCategoryIdList))
			{
				$tagValuesList = $this->buildCategoryTagValuesList($usedCategoryIdList, $context);
				$writeLimit = ($limit !== null ? $limit - $successCount : null);

				$this->extendData($tagValuesList, [], $context);
				$writeResultList = $this->writeData($tagValuesList, [], $context, [], $writeLimit);

				foreach ($writeResultList as $writeResult)
				{
					if ($writeResult['STATUS'] === static::STORAGE_STATUS_SUCCESS)
					{
						$result['SUCCESS_COUNT']++;
					}
				}
			}
		}

		return $result;
	}

    protected function processExportElementList(&$elementList, &$parentList, $context)
    {
        $this->filterOnlyUsedElementList($elementList, $context);
    }

    protected function getQueryExcludeFilterPrimary($queryContext)
    {
        return (int)$queryContext['PROMO_ID'];
    }

    protected function getExistDataStorageFilter(array $context)
    {
        return [
            '=SETUP_ID' => $context['SETUP_ID'],
            '=PROMO_ID' => $context['PROMO_ID'],
            '=ELEMENT_TYPE' => $context['ELEMENT_TYPE'],
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

        if (isset($queryContext['ELEMENT_TYPE']))
        {
            $filter['=ELEMENT_TYPE'] = $queryContext['ELEMENT_TYPE'];
        }

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
				Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY => true,
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
                    '>=EXPORT_OFFER.TIMESTAMP_X' => $this->getParameter('initTime')
                ];

				$result[] = [
					'>=EXPORT_CATEGORY.TIMESTAMP_X' => $this->getParameter('initTime'),
					'=EXPORT_CATEGORY.STATUS' => static::STORAGE_STATUS_DELETE
				];
            }

            // self changes

            foreach ($selfChanges as $changeType => $entityIds)
            {
                switch ($changeType)
                {
                    case Market\Export\Run\Manager::ENTITY_TYPE_PROMO:
                        $result[] = [
                            '=PROMO_ID' => $entityIds
                        ];
                    break;

					case Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY:
						$result[] = [
							'=ELEMENT_TYPE' => Market\Export\PromoProduct\Table::PROMO_PRODUCT_TYPE_OFFER_WITH_DISCOUNT
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

    protected function writeDataLog($tagResultList, $context)
    {
        $newTagResultList = [];

        foreach ($tagResultList as $elementId => $tagResult)
        {
            $newTagResultList[$context['PROMO_ID'] . ':' . $elementId] = $tagResult;
        }

        parent::writeDataLog($newTagResultList, $context);
    }

    protected function buildTagValues($elementId, $tagDescriptionList, $sourceValues, $context)
    {
        $result = parent::buildTagValues($elementId, $tagDescriptionList, $sourceValues, $context);
        $result->setType($context['ELEMENT_TYPE']);

        if ($context['HAS_DISCOUNT_PRICE'])
        {
            $this->applyPromoDiscountRules($elementId, $result, $context['PROMO'], $context['FILTER_DATA']);
        }

        return $result;
    }

    protected function buildTagList($tagValuesList, array $context = [])
	{
		if ($context['HAS_DISCOUNT_PRICE'])
		{
			$result = $this->filterInvalidTagValues($tagValuesList, $context);
			$result += parent::buildTagList($tagValuesList, $context);
		}
		else
		{
			$result = parent::buildTagList($tagValuesList, $context);
		}

		return $result;
	}

	/**
	 * Фильтруем невалидные значения для тегов
	 *
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array                    $context
	 *
	 * @return Market\Result\XmlNode[]
	 */
	protected function filterInvalidTagValues(&$tagValuesList, $context)
	{
		$result = [];
		$isSilent = !empty($context['PROMO_PRICE_SILENT']);

		foreach ($tagValuesList as $elementId => $tagValue)
		{
			$originalPrice = (float)$tagValue->getTagValue('original-price');
			$originalPriceCurrency = (string)$tagValue->getTagAttribute('original-price', 'currency');

			if ($originalPrice > 0 && $originalPriceCurrency !== '')
			{
				$discountPriceList = $tagValue->getTagValue('discount-price', true);
				$discountCurrencyList = $tagValue->getTagAttribute('discount-price', 'currency', true);
				$isFoundDiscount = false;
				$hasInvalidPrice = false;
				$hasNotEmptyPrice = false;

				foreach ($discountPriceList as $discountIndex => $discountPrice)
				{
					$discountCurrency = isset($discountCurrencyList[$discountIndex]) ? (string)$discountCurrencyList[$discountIndex] : '';
					$discountPrice = (float)$discountPrice;

					if ($discountCurrency === '' || $discountPrice <= 0)
					{
						$hasInvalidPrice = true;
					}
					else if ($discountCurrency === $originalPriceCurrency && $discountPrice < $originalPrice)
					{
						$isFoundDiscount = true;
						$hasNotEmptyPrice = true;

						if ($hasInvalidPrice)
						{
							array_splice($discountPriceList, 0, $discountIndex);
							array_splice($discountCurrencyList, 0, $discountIndex);

							$tagValue->setTagValue('discount-price', $discountPriceList, true);
							$tagValue->setTagAttribute('discount-price', 'currency', $discountCurrencyList, true);
						}

						break;
					}
					else
					{
						$hasNotEmptyPrice = true;
					}
				}

				if ($hasNotEmptyPrice && !$isFoundDiscount)
				{
					$tagResult = new Market\Result\XmlNode();

					if ($isSilent)
					{
						$tagResult->invalidate();
					}
					else
					{
						$tagResult->addError(new Market\Error\XmlNode(
							Market\Config::getLang('EXPORT_RUN_STEP_PROMO_PRODUCT_PROMO_PRICE_NOT_LESS_ORIGINAL')
						));
					}

					$result[$elementId] = $tagResult;

					unset($tagValuesList[$elementId]);
				}
			}
		}

		return $result;
	}

    protected function getIgnoredTypeChanges()
    {
        return [
            Market\Export\Run\Manager::ENTITY_TYPE_GIFT => true
        ];
    }

	/**
	 * Заполняем источник тега discount-price
	 *
	 * @param $tagDescriptionList array
	 * @param $iblockLink Market\Export\IblockLink\Model
	 *
	 * @return array
	 */
	protected function fillDiscountPriceTagDescriptionList(array $tagDescriptionList, Market\Export\IblockLink\Model $iblockLink)
    {
    	$discountPriceName = 'discount-price';
    	$definedTags = array_column($tagDescriptionList, 'TAG', 'TAG');

    	if (isset($definedTags[$discountPriceName]))
	    {
		    $currencyTagDescription = $iblockLink->getTagDescription('currencyId');

		    if ($currencyTagDescription !== null)
		    {
			    foreach ($tagDescriptionList as &$tagDescription)
			    {
			        if (
			        	$tagDescription['TAG'] === $discountPriceName
				        && (
				            $currencyTagDescription['VALUE']['TYPE'] === Market\Export\Entity\Manager::TYPE_CURRENCY
					        || !isset($tagDescription['attributes']['currency'])
				        )
			        )
			        {
				        $tagDescription['attributes']['currency'] = $currencyTagDescription['VALUE'];
			        }
			    }
			    unset($tagDescription);
		    }
	    }
    	else
	    {
	    	$discountPriceDescriptionList = $this->getDiscountPriceTagDescriptionList($iblockLink);

	    	if (!empty($discountPriceDescriptionList))
		    {
		    	array_push($tagDescriptionList, ...$discountPriceDescriptionList);
		    }
	    }

    	return $tagDescriptionList;
    }

    /**
     * Получаем источник тега discount-price из описания инфоблока
     *
     * @param $iblockLink Market\Export\IblockLink\Model
     *
     * @return array
     */
    protected function getDiscountPriceTagDescriptionList(Market\Export\IblockLink\Model $iblockLink)
    {
        $result = [];
        $currencyTagDescription = $iblockLink->getTagDescription('currencyId');

        if ($currencyTagDescription !== null)
        {
            $priceTagDescription = $iblockLink->getTagDescription('price');
            $oldPriceTagDescription = $iblockLink->getTagDescription('oldprice');

            if ($oldPriceTagDescription !== null)
            {
                $result[] = [
                    'TAG' => 'discount-price',
                    'VALUE' => $oldPriceTagDescription['VALUE'],
                    'ATTRIBUTES' => [
                        'currency' => $currencyTagDescription['VALUE']
                    ],
                    'SETTINGS' => null
                ];
            }

            if ($priceTagDescription !== null)
            {
                $priceSource = $priceTagDescription['VALUE'];

                if (isset($priceSource['FIELD']) && Market\Data\TextString::getPosition($priceSource['FIELD'], 'DISCOUNT_VALUE') !== false)
                {
                    $priceSource['FIELD'] = str_replace('DISCOUNT_VALUE', 'VALUE', $priceSource['FIELD']);
                }

                $result[] = [
                    'TAG' => 'discount-price',
                    'VALUE' => $priceSource,
                    'ATTRIBUTES' => [
                        'currency' => $currencyTagDescription['VALUE']
                    ],
                    'SETTINGS' => null
                ];
            }
        }

        return $result;
    }

	/**
	 * Заполняем источник тега original-price из описания инфоблока для сравнения с ценой, выгруженной в основной секции offers
	 *
	 * @param $tagDescriptionList array
	 * @param $iblockLink Market\Export\IblockLink\Model
	 *
	 * @return array
	 */
	protected function fillOriginalPriceTagDescriptionList(array $tagDescriptionList, Market\Export\IblockLink\Model $iblockLink)
    {
	    $currencyTagDescription = $iblockLink->getTagDescription('currencyId');
	    $priceTagDescription = $iblockLink->getTagDescription('price');

	    if ($currencyTagDescription !== null && $priceTagDescription !== null)
	    {
		    $tagDescriptionList[] = [
			    'TAG' => 'original-price',
			    'VALUE' => $priceTagDescription['VALUE'],
			    'ATTRIBUTES' => [
				    'currency' => $currencyTagDescription['VALUE']
			    ],
			    'SETTINGS' => null
		    ];
	    }

	    return $tagDescriptionList;
    }

    /**
     * Выбираем из фильтра Ид категорий, если есть фильтр только по категории
     *
     * @param $sourceFilter array
     * @param Market\Export\IblockLink\Model $iblockLink
     *
     * @return array|null
     */
    protected function getCategoryFilterIdList($sourceFilter, Market\Export\IblockLink\Model $iblockLink)
    {
        $categoryTagDescription = $iblockLink->getTagDescription('categoryId');
        $result = null;

        if (
            !empty($sourceFilter)
            && !empty($categoryTagDescription['VALUE']['TYPE'])
            && !empty($categoryTagDescription['VALUE']['FIELD'])
        )
        {
            $categorySource = $categoryTagDescription['VALUE'];

            // extract ids from filter

            foreach ($sourceFilter as $sourceType => $sourceConditionList)
            {
                if ($sourceType !== $categorySource['TYPE'])
                {
                    $result = null;
                    break;
                }
                else
                {
                    foreach ($sourceConditionList as $sourceCondition)
                    {
                        $isMatch = true;
                        $conditionValueList = null;

						if ($sourceCondition['COMPARE'] !== '=' && $sourceCondition['COMPARE'] !== '')
						{
							$isMatch = false;
						}
						else if (
							$sourceCondition['FIELD'] !== $categorySource['FIELD']
							&& ($sourceCondition['FIELD'] !== 'SECTION_ID' || $categorySource['FIELD'] !== 'IBLOCK_SECTION_ID')
						)
                        {
                            $isMatch = false;
                        }
                        else
                        {
                            $conditionValueList = (array)$sourceCondition['VALUE'];

                            Main\Type\Collection::normalizeArrayValuesByInt($conditionValueList, false);

                            if (empty($conditionValueList))
                            {
                                $isMatch = false;
                            }
                        }

                        if (!$isMatch)
                        {
                            $result = null;
                            break 2;
                        }
                        else
                        {
                            if ($result === null)
                            {
                                $result = $conditionValueList;
                            }
                            else
                            {
                                $result = array_merge($result, $conditionValueList);
                            }
                        }
                    }
                }
            }

            // apply conflict

            if (!empty($result))
            {
                $conflictList = $this->getProcessor()->getConflicts();

                if (isset($conflictList[$categorySource['TYPE']][$categorySource['FIELD']]))
                {
                    $conflictAction = $conflictList[$categorySource['TYPE']][$categorySource['FIELD']];

                    foreach ($result as &$categoryId)
                    {
                        $categoryId = $this->applyValueConflict($categoryId, $conflictAction);
                    }
                    unset($categoryId);
                }
            }
        }

        return $result;
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
        $usedIdList = $this->getUsedOfferIdList($idList, $context);
        $usedIdMap = array_flip($usedIdList);

        foreach ($elementList as $elementId => $element)
        {
            if (!isset($usedIdMap[$elementId]))
            {
                unset($elementList[$elementId]);
            }
        }
    }

    /**
     * Выбираем выгруженные предложения
     *
     * @param $offerIdList int[]
     * @param $context array
     *
     * @return int[]
     */
    protected function getUsedOfferIdList($offerIdList, $context)
    {
        $result = [];

        if (!empty($offerIdList))
        {
            $queryExportOfferList = Market\Export\Run\Storage\OfferTable::getList([
                'filter' => [
                    '=SETUP_ID' => $context['SETUP_ID'],
                    '=ELEMENT_ID' => $offerIdList,
                    '=STATUS' => static::STORAGE_STATUS_SUCCESS
                ],
                'select' => [
                    'ELEMENT_ID'
                ]
            ]);

            while ($exportOffer = $queryExportOfferList->fetch())
            {
                $result[] = (int)$exportOffer['ELEMENT_ID'];
            }
        }

        return $result;
    }

    /**
     * Выбираем выгруженные категории
     *
     * @param $categoryIdList int[]
     * @param $context array
     *
     * @return int[]
     */
    protected function getUsedCategoryIdList($categoryIdList, $context)
    {
        $result = [];

        if (!empty($categoryIdList))
        {
            $queryExportCategoryList = Market\Export\Run\Storage\CategoryTable::getList([
                'filter' => [
                    '=SETUP_ID' => $context['SETUP_ID'],
                    '=ELEMENT_ID' => $categoryIdList,
                    '=STATUS' => static::STORAGE_STATUS_SUCCESS
                ],
                'select' => [
                    'ELEMENT_ID'
                ]
            ]);

            while ($exportCategory = $queryExportCategoryList->fetch())
            {
                $result[] = (int)$exportCategory['ELEMENT_ID'];
            }
        }

        return $result;
    }

    /**
     * @param $categoryIdList int[]
     * @param $context array
     *
     * @return Market\Result\XmlValue[]
     */
    protected function buildCategoryTagValuesList($categoryIdList, $context)
    {
        $result = [];

        foreach ($categoryIdList as $categoryId)
        {
            $result[$categoryId] = $this->buildCategoryTagValues($categoryId, $context);
        }

        return $result;
    }

    /**
     * @param $categoryId int
     * @param $context array
     *
     * @return Market\Result\XmlValue
     */
    protected function buildCategoryTagValues($categoryId, $context)
    {
        $result = new Market\Result\XmlValue();

        $result->setType($context['ELEMENT_TYPE']);

        $result->addTag('product', null, [
            'category-id' => $categoryId
        ]);

        return $result;
    }

    /**
     * Применяем скидку к расчетной цене товара
     *
     * @param $productId int
     * @param $tagValue Market\Result\XmlValue
     * @param $promo Market\Export\Promo\Model
     * @param $filterData array|null
     */
    protected function applyPromoDiscountRules($productId, $tagValue, Market\Export\Promo\Model $promo, $filterData)
    {
        $priceList = $tagValue->getTagValue('discount-price', true);
        $currencyList = $tagValue->getTagAttribute('discount-price', 'currency', true);
        $discountPriceList = [];
        $isChanged = false;

        foreach ($priceList as $priceIndex => $priceValue)
        {
            if (!empty($currencyList[$priceIndex]))
            {
                $isChanged = true;
                $discountPriceList[$priceIndex] = $promo->applyDiscountRules($productId, $priceValue, $currencyList[$priceIndex], $filterData);
            }
            else
            {
                $discountPriceList[$priceIndex] = $priceValue;
            }
        }

        if ($isChanged)
        {
            $tagValue->setTagValue('discount-price', $discountPriceList, true);
        }
    }
}