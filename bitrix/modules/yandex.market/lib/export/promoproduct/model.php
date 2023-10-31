<?php

namespace Yandex\Market\Export\PromoProduct;

use Yandex\Market;

class Model extends Market\Reference\Storage\Model
{
    /** @var array */
	protected $iblockContext;
	/** @var Market\Export\Promo\Discount\AbstractProvider|null*/
	protected $discount;

	/**
	 * Название класса таблицы
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getTagDescriptionList($offerPrimarySource = null)
	{
		return array_merge(
			$this->getCommonDescriptionList($offerPrimarySource),
			$this->getDiscountPriceDescriptionList()
		);
	}

	protected function getCommonDescriptionList($offerPrimarySource = null)
	{
		if (empty($offerPrimarySource))
		{
			$offerPrimarySource = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'ID'
			];
		}

		return [
			[
				'TAG' => 'product',
				'VALUE' => null,
				'ATTRIBUTES' => [
					'offer-id' => $offerPrimarySource,
				],
				'SETTINGS' => null
			]
		];
	}

	protected function getDiscountPriceDescriptionList()
	{
		/** @var Market\Export\Promo\Model $promo */
		$promo = $this->getParent();
		$result = [];

		if ($this->discount === null || $promo === null || !$promo->hasProductDiscountPrice()) { return $result; }

		$context = $this->getIblockContext();
		$select = $this->discount->getProductPriceSelect($context);

		if (isset($select['PRICE']))
		{
			$attributes = [];

			if (isset($select['CURRENCY']))
			{
				$attributes['currency'] = $select['CURRENCY'];
			}

			$result[] = [
				'TAG' => 'discount-price',
				'VALUE' => $select['PRICE'],
				'ATTRIBUTES' => $attributes,
				'SETTINGS' => null,
			];
		}

		return $result;
	}

	public function getIblockId()
    {
        return (int)$this->getField('IBLOCK_ID');
    }

    public function setDiscount(Market\Export\Promo\Discount\AbstractProvider $discount = null)
    {
        $this->discount = $discount;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function getUsedSources()
    {
        $result = $this->getSourceSelect();

        foreach ($this->getFilterCollection() as $filterModel)
        {
            $filterUserSources = $filterModel->getUsedSources();

            foreach ($filterUserSources as $sourceType)
            {
                if (!isset($result[$sourceType]))
                {
                    $result[$sourceType] = true;
                }
            }
        }

        return array_keys($result);
    }

    public function getSourceSelect()
    {
        return []; // nothing, all data from iblockLink
    }

    public function getTrackSourceList()
    {
        $sourceList = $this->getUsedSources();
        $context = $this->getContext();
        $result = [];

        foreach ($sourceList as $sourceType)
        {
            $eventHandler = Market\Export\Entity\Manager::getEvent($sourceType);

            $result[] = [
                'SOURCE_TYPE' => $sourceType,
                'SOURCE_PARAMS' => $eventHandler->getSourceParams($context)
            ];
        }

        return $result;
    }

	public function getContext($isOnlySelf = false)
	{
		$result = $this->getIblockContext();
		$result['HAS_SETUP_IBLOCK'] = false;

		if ($iblockLink = $this->getIblockLink())
        {
        	$result['SITE_ID'] = $iblockLink->getSiteId();
            $result['HAS_SETUP_IBLOCK'] = true;
        }

		if ($this->discount !== null)
		{
			$result += $this->discount->getProductContext();
		}

		if (!$isOnlySelf)
		{
			$result = $this->mergeParentContext($result);
		}

		return $result;
	}

	protected function mergeParentContext($selfContext)
	{
		/** @var \Yandex\Market\Export\Promo\Model $parent */
		$collection = $this->getCollection();
		$parent = $collection ? $collection->getParent() : null;
		$parentContext = $parent ? $parent->getContext() : null;
		$result = $selfContext;

		if ($parentContext !== null)
		{
			$result += $parentContext;
		}

		return $result;
	}

	protected function getIblockContext()
	{
		if ($this->iblockContext === null)
		{
			$iblockId = $this->getIblockId();

			$this->iblockContext = Market\Export\Entity\Iblock\Provider::getContext($iblockId);
		}

		return $this->iblockContext;
	}

    /**
     * @return \Yandex\Market\Export\Filter\Collection
     */
    public function getFilterCollection()
    {
        return $this->getChildCollection('FILTER');
    }

    protected function loadChildCollection($fieldKey)
    {
        $result = null;

        if ($this->discount !== null)
        {
            switch ($fieldKey)
            {
                case 'FILTER':
                    $result = $this->buildFilterCollection($fieldKey);
                break;
            }
        }

        if ($result === null)
        {
            $result = parent::loadChildCollection($fieldKey);
        }

        return $result;
    }

	protected function supportsBatchCollectionLoading($fieldKey)
	{
		if ($this->discount !== null && $fieldKey === 'FILTER')
		{
			$result = false;
		}
		else
		{
			$result = parent::supportsBatchCollectionLoading($fieldKey);
		}

		return $result;
	}

    /**
     * Создаем коллекцию по инфоблокам профиля выгрузки (необходимо для скидок Битрикс)
     *
     * @param $fieldKey
     *
     * @return Market\Reference\Storage\Collection
     */
    protected function buildFilterCollection($fieldKey)
    {
        /** @var Market\Reference\Storage\Collection $result */
        /** @var Market\Export\Filter\Model $filterModel */
        $collectionClassName = $this->getChildCollectionReference($fieldKey);
        $modelClassName = $collectionClassName::getItemReference();

        $result = new $collectionClassName();
        $result->setParent($this);

        if ($this->discount !== null)
        {
            $context = $this->getDiscountFilterContext();
            $filterList = $this->getDiscountProductFilterList($context);

            foreach ($filterList as $filter)
            {
                $filterModel = $modelClassName::initialize([
                    'ENTITY_TYPE' => Market\Export\Filter\Table::ENTITY_TYPE_PROMO_PRODUCT,
                    'ENTITY_ID' => $this->getId()
                ]);

                $filterModel->setCollection($result);
                $filterModel->setPlainFilter((array)$filter['FILTER']);

                if (isset($filter['DATA']))
                {
                    $filterModel->setPlainData($filter['DATA']);
                }

                $result->addItem($filterModel);
            }
        }

        return $result;
    }

    protected function getDiscountFilterContext()
    {
        $result = $this->getIblockContext();
        $result['TAGS'] = [];

        $iblockLink = $this->getIblockLink();

        if ($iblockLink !== null)
        {
            $tags = [
                'oldprice' => [ 'oldprice', 'price' ]
            ];

            foreach ($tags as $tagName => $targetTagList)
            {
                $tagValue = null;

                foreach ($targetTagList as $targetTagName)
                {
                    $targetTagDescription = $iblockLink->getTagDescription($targetTagName);

                    if (isset($targetTagDescription['VALUE']['TYPE'], $targetTagDescription['VALUE']['FIELD']))
                    {
                        $tagValue = $targetTagDescription['VALUE'];
                        break;
                    }
                }

                if ($tagValue !== null)
                {
                    $result['TAGS'][$tagName] = $tagValue;
                }
            }
        }

        return $result;
    }

    /**
     * Настройка инфоблока для профиля выгрузки (доступна только в момент выгрузки)
     *
     * @return Market\Export\IblockLink\Model|null
     */
    protected function getIblockLink()
    {
        /** @var Market\Export\Promo\Model $promo */
        /** @var Market\Export\Setup\Model $setup */
        $promo = $this->getParent();
        $setup = $promo ? $promo->getParent() : null;
        $result = null;

        if ($setup)
        {
            $iblockId = $this->getIblockId();
            $iblockLinkCollection = $setup->getIblockLinkCollection();

            $result = $iblockLinkCollection->getByIblockId($iblockId);
        }

        return $result;
    }

    protected function getDiscountProductFilterList($context)
    {
        $result = [];

        if ($this->discount !== null)
        {
            $result = $this->discount->getProductFilterList($context);
        }

        return $result;
    }

    protected function getChildCollectionReference($fieldKey)
    {
        $result = null;

        switch ($fieldKey)
        {
            case 'FILTER':
                $result = Market\Export\Filter\Collection::getClassName();
            break;
        }

        return $result;
    }
}