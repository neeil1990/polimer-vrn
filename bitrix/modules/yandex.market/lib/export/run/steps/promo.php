<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

class Promo extends Base
{
	protected $primaryPrefix;

    public function getName()
    {
        return Market\Export\Run\Manager::STEP_PROMO;
    }

    public function run($action, $offset = null)
    {
        $result = new Market\Result\Step();

        $setup = $this->getSetup();
        $context = $setup->getContext();
        $promoCollection = $setup->getPromoCollection();
        $promoCount = count($promoCollection);
        $promoIndex = 0;
        $readyCount = 0;
        $tagDescriptionList = null;
        $sourceValueList = [];
        $elementList = [];
        $flushLimit = $this->getFlushLimit();
        $promoChangedMap = null;

        $this->setRunAction($action);

        $result->setTotal($promoCount);
        $result->setTotalCount($promoCount);

        if ($action === 'change')
        {
        	$promoChangedMap = $this->getChangesMap($context);
        }

        /** @var Market\Export\Promo\Model $promo */
        foreach ($promoCollection as $promo)
        {
            $promoId = $promo->getInternalId();

            if ($offset !== null && $offset > $promoIndex)
            {
                // is out of offset
            }
            else if ($promoChangedMap !== null && !isset($promoChangedMap[$promoId]))
            {
                // is not changed
            }
            else if (!$promo->isActive() || !$promo->isActiveDate()) // is inactive
            {
                // nothing
            }
            else
            {
                if ($tagDescriptionList === null)
                {
                    $tagDescriptionList = $promo->getTagDescriptionList();
                }

                $promoFields = $this->getPromoFields($promo);
                $exportPromoProductList = $this->getExportPromoProductList($promo, $context);
                $exportPromoGiftList = null;

                if ($promo->isSupportGift())
                {
                    $exportPromoGiftList = $this->getExportPromoGiftList($promo, $context);
                }

                $elementList[$promoId] = $promoFields;

                $sourceValueList[$promoId] = [
                    'TYPE' => $promo->getPromoType(),
                    'PROMO' => $promoFields,
                    'PRODUCT' => [
                        'CONTENTS' => implode('', $exportPromoProductList)
                    ],
                    'GIFT' => [
                        'CONTENTS' => $exportPromoGiftList !== null ? implode('', $exportPromoGiftList) : null
                    ]
                ];
            }

            $promoIndex++;
            $readyCount++;

            $isTimeExpired = $this->getProcessor()->isTimeExpired();

            if (!empty($sourceValueList) && ($isTimeExpired || count($sourceValueList) >= $flushLimit))
            {
                $tagValuesList = $this->buildTagValuesList($tagDescriptionList, $sourceValueList, $context);

                $this->extendData($tagValuesList, $elementList, $context);
                $this->writeData($tagValuesList, $elementList, $context);

                $sourceValueList = [];
                $elementList = [];

                if ($isTimeExpired)
                {
                    $result->setOffset($readyCount);
                    break;
                }
            }
        }

        if (!empty($sourceValueList))
        {
            $tagValuesList = $this->buildTagValuesList($tagDescriptionList, $sourceValueList, $context);

			$this->extendData($tagValuesList, $elementList, $context);
            $this->writeData($tagValuesList, $elementList, $context);
        }

        $result->setProgress($readyCount);
        $result->setReadyCount($readyCount);

        return $result;
    }

    public function isTypedTag()
    {
        return true;
    }

    public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
    {
        return $format->getPromo($type);
    }

    public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
    {
        return $format->getPromoParentName();
    }

    protected function getDataLogEntityType()
    {
        return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO;
    }

    protected function getStorageDataClass()
    {
        return Market\Export\Run\Storage\PromoTable::getClassName();
    }

    protected function getStorageRuntime()
    {
        return [
            new Main\Entity\ReferenceField('EXPORT_PROMO_PRODUCT', Market\Export\Run\Storage\PromoProductTable::getClassName(), [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                '=this.ELEMENT_ID' => 'ref.PROMO_ID'
            ]),
            new Main\Entity\ReferenceField('EXPORT_PROMO_GIFT', Market\Export\Run\Storage\PromoGiftTable::getClassName(), [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                '=this.ELEMENT_ID' => 'ref.PROMO_ID'
            ]),
        ];
    }

    protected function getChangesMap($context)
    {
	    $changes = $this->getChanges();
	    $isOnlyPromoChanged = false;
	    $changedMap = [];

	    if (isset($changes[Market\Export\Run\Manager::ENTITY_TYPE_PROMO])) // has only changes in promo
	    {
		    $isOnlyPromoChanged = (count($changes) === 1);
		    $changedMap += array_flip($changes[Market\Export\Run\Manager::ENTITY_TYPE_PROMO]);
	    }

	    if (!$isOnlyPromoChanged)
	    {
	    	$changedIds = $this->getStorageChangedIds($changes, $context);
		    $changedMap += array_flip($changedIds);
	    }

	    return $changedMap;
    }

    protected function getStorageChangedIds($changes, $context)
    {
	    $dataClass = $this->getStorageDataClass();
    	$changesFilter = $this->getStorageChangesFilter($changes, $context);
	    $result = [];

    	if ($dataClass && $changesFilter !== null)
	    {
		    $filter = [
		    	'=SETUP_ID' => $context['SETUP_ID'],
		    ];

		    if (!empty($changesFilter))
		    {
		    	$filter[] = $changesFilter;
		    }

		    $query = $dataClass::getList([
		    	'filter' => $filter,
			    'select' => [ 'ELEMENT_ID' ],
			    'group' => [ 'ELEMENT_ID' ],
			    'runtime' => $this->getStorageRuntime(),
		    ]);

		    while ($row = $query->fetch())
		    {
		    	$result[] = $row['ELEMENT_ID'];
		    }
	    }

    	return $result;
    }

	protected function getStorageChangesFilter($changes, $context)
    {
        $isNeedCheckProduct = false;
        $result = [];

        if (!empty($changes))
        {
            $ignoredTypeList = $this->getIgnoredTypeChanges();

            foreach ($changes as $changeType => $entityIds)
            {
                if (isset($ignoredTypeList[$changeType])) { continue; }

                switch ($changeType)
                {
                    case Market\Export\Run\Manager::ENTITY_TYPE_PROMO:
                        $result[] = [
                            '=ELEMENT_ID' => $entityIds
                        ];
                    break;

                    default:
                        $isNeedCheckProduct = true;
                    break;
                }
            }
        }

        if ($isNeedCheckProduct)
        {
            $result[] = [
                '>=EXPORT_PROMO_PRODUCT.TIMESTAMP_X' => $this->getParameter('initTimeUTC')
            ];

            $result[] = [
                '>=EXPORT_PROMO_GIFT.TIMESTAMP_X' => $this->getParameter('initTimeUTC')
            ];
        }

        if (empty($result))
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

    protected function getQueryExcludeFilterPrimary($queryContext)
    {
        return 0; // equal for all
    }

    /**
     * Получаем содерижимое выгруженных товаров для promo
     *
     * @param Market\Export\Promo\Model $promo
     * @param $context
     *
     * @return array
     */
    protected function getExportPromoProductList(Market\Export\Promo\Model $promo, $context)
    {
        $result = [];

        $queryExportProductList = Market\Export\Run\Storage\PromoProductTable::getList([
            'filter' => [
                '=SETUP_ID' => $context['SETUP_ID'],
                '=PROMO_ID' => $promo->getId(),
                '=STATUS' => static::STORAGE_STATUS_SUCCESS
            ],
            'select' => [
                'ELEMENT_ID',
                'CONTENTS'
            ]
        ]);

        while ($exportProduct = $queryExportProductList->fetch())
        {
            $result[$exportProduct['ELEMENT_ID']] = $exportProduct['CONTENTS'];
        }

        return $result;
    }

    /**
     * Получаем содержимое выгруженных подарков для promo
     *
     * @param Market\Export\Promo\Model $promo
     * @param $context
     *
     * @return array
     *
     * @throws Main\ArgumentException
     * @throws Main\SystemException
     */
    protected function getExportPromoGiftList(Market\Export\Promo\Model $promo, $context)
    {
        $result = [];

        $queryExportProductList = Market\Export\Run\Storage\PromoGiftTable::getList([
            'filter' => [
                '=SETUP_ID' => $context['SETUP_ID'],
                '=PROMO_ID' => $promo->getId(),
                '=STATUS' => static::STORAGE_STATUS_SUCCESS,
                [
                    'LOGIC' => 'OR',
                    [ '=ELEMENT_TYPE' => Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER ],
                    [
                        '=ELEMENT_TYPE' => Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_GIFT,
                        '=GIFT_EXPORT.STATUS' => static::STORAGE_STATUS_SUCCESS
                    ]
                ]
            ],
            'select' => [
                'ELEMENT_ID',
                'CONTENTS'
            ],
            'runtime' => [
                new Main\Entity\ReferenceField('GIFT_EXPORT', Market\Export\Run\Storage\GiftTable::getClassName(), [
                    '=this.SETUP_ID' => 'ref.SETUP_ID',
                    '=this.ELEMENT_ID' => 'ref.ELEMENT_ID'
                ])
            ]
        ]);

        while ($exportProduct = $queryExportProductList->fetch())
        {
            $result[$exportProduct['ELEMENT_ID']] = $exportProduct['CONTENTS'];
        }

        return $result;
    }

    /**
     * Количество записей promo, после которых необходимо выполнить запись в файл
     *
     * @return int
     */
    protected function getFlushLimit()
    {
        return (int)($this->getParameter('promoPageSize') ?: Market\Config::getOption('export_run_promo_page_size') ?: 20);
    }

    protected function isAllowDeleteParent()
    {
        return true;
    }

    protected function isAllowPublicDelete()
    {
        return true;
    }

    protected function getPromoFields(Market\Export\Promo\Model $promo)
    {
    	$result = $promo->getPromoFields();
    	$result['PRIMARY'] = $this->getPrimaryPrefix() . $result['ID'];

    	return $result;
    }

	protected function getPrimaryPrefix()
	{
		if ($this->primaryPrefix === null)
		{
			$this->primaryPrefix = (string)Market\Config::getOption('export_promo_id_prefix', '');
		}

		return $this->primaryPrefix;
	}
}