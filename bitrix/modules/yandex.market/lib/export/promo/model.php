<?php

namespace Yandex\Market\Export\Promo;

use Yandex\Market;
use Yandex\Market\Export\Glossary;
use Yandex\Market\Watcher;
use Bitrix\Main;

class Model extends Market\Reference\Storage\Model
	implements
		Market\Export\Run\Data\EntityExportable,
		Watcher\Agent\EntityWithActiveDates
{
    /** @var Discount\AbstractProvider|null */
    protected $discount;
    /** @var array|null */
    protected $discountPromoFields;
    /** @var bool|null */
    protected $isDiscountValid;

    public function __construct(array $fields = [])
    {
        parent::__construct($fields);

        $this->createDiscount();
    }

    protected function createDiscount()
    {
        $type = $this->getField('PROMO_TYPE');

        if (!Discount\Manager::isInternalType($type))
        {
            try
            {
                $externalId = $this->getField('EXTERNAL_ID');
                $externalSettings = (array)$this->getField('EXTERNAL_SETTINGS');

                $this->discount = Discount\Manager::getProviderInstance($type, $externalId);
                $this->discount->setSettings($externalSettings);

                $this->isDiscountValid = true;
            }
            catch (Main\SystemException $exception)
            {
                $this->isDiscountValid = false;
            }
        }
    }

    /**
	 * Название класса таблицы
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function onBeforeRemove()
    {
        $this->handleChanges(false);
        $this->handleActiveDate(false);
    }

    public function onAfterSave()
    {
        $this->updateListener();
    }

	public function getTagDescriptionList()
    {
        return [
            [
                'TAG' => 'promo',
                'VALUE' => null,
                'ATTRIBUTES' => [
                    'id' => [ 'TYPE' => 'PROMO', 'FIELD' => 'PRIMARY' ],
                    'type' => [ 'TYPE' => 'PROMO', 'FIELD' => 'PROMO_TYPE' ],
                ]
            ],
            [
                'TAG' => 'start-date',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'START_DATE' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'end-date',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'FINISH_DATE' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'description',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DESCRIPTION' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'url',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'URL' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'promo-code',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'PROMO_CODE' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'discount',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DISCOUNT_VALUE' ],
                'ATTRIBUTES' => [
                    'unit' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DISCOUNT_UNIT'  ],
                    'currency' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DISCOUNT_CURRENCY'  ],
                ]
            ],
            [
                'TAG' => 'required-quantity',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'GIFT_REQUIRED_QUANTITY' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'free-quantity',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'GIFT_FREE_QUANTITY' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'product',
                'VALUE' => [ 'TYPE' => 'PRODUCT', 'FIELD' => 'CONTENTS' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'promo-gift',
                'VALUE' => [ 'TYPE' => 'GIFT', 'FIELD' => 'CONTENTS' ],
                'ATTRIBUTES' => []
            ],
        ];
    }

	public function getName()
	{
		return $this->getField('NAME');
	}

    public function isActive()
    {
        $result = true;

        if ((string)$this->getField('ACTIVE') !== Table::BOOLEAN_Y)
        {
            $result = false;
        }
        else if ($this->discount !== null)
        {
            $result = $this->discount->isActive();
        }

        return $result;
    }

    public function isActiveDate()
    {
        /** @var Main\Type\DateTime $startDate */
        /** @var Main\Type\DateTime $finishDate */
        $startDate = $this->getPromoField('START_DATE');
        $finishDate = $this->getPromoField('FINISH_DATE');
        $now = time();
        $result = true;

        if ($startDate && $startDate->getTimestamp() > $now)
        {
            $result = false;
        }
        else if ($finishDate && $finishDate->getTimestamp() <= $now)
        {
            $result = false;
        }

        return $result;
    }

    public function getNextActiveDate()
    {
	    $result = null;
	    $now = new Main\Type\DateTime();
	    $dates = [
		    $this->getPromoField('START_DATE'),
		    $this->getPromoField('FINISH_DATE'),
	    ];

	    foreach ($dates as $date)
	    {
		    if (
				$date instanceof Main\Type\Date
				&& Market\Data\DateTime::compare($date, $now) !== -1
		    )
		    {
			    $result = $date;
			    break;
		    }
	    }

	    return $result;
    }

    public function isExportForAll()
    {
        return ((string)$this->getField('SETUP_EXPORT_ALL') === Table::BOOLEAN_Y);
    }

    public function getPromoType()
    {
        if ($this->discount !== null)
        {
            $result = $this->discount->getPromoType();
        }
        else
        {
            $result = $this->getField('PROMO_TYPE');
        }

        return $result;
    }

    public function getPromoField($key)
    {
        $result = null;

        if ($this->discount !== null)
        {
            $promoFields = $this->getPromoFields();

            if (isset($promoFields[$key]))
            {
                $result = $promoFields[$key];
            }
        }
        else
        {
            $result = $this->getField($key);
        }

        return $result;
    }

    public function getPromoFields()
    {
        if ($this->discount === null)
        {
            $result = $this->getFields();
        }
        else if ($this->discountPromoFields !== null)
        {
            $result = $this->discountPromoFields;
        }
        else
        {
            $result = $this->discount->getPromoFields() + $this->getFields();
            $result['PROMO_TYPE'] = $this->getPromoType();

            $this->discountPromoFields = $result;
        }

        return $result;
    }

    public function hasProductDiscountPrice()
    {
    	$promoType = $this->getPromoType();

        return (
        	$promoType === Table::PROMO_TYPE_FLASH_DISCOUNT
	        || $promoType === Table::PROMO_TYPE_BONUS_CARD
        );
    }

    public function applyDiscountRules($productId, $priceValue, $currency = null, $filterData = null)
    {
        if ($this->discount !== null)
        {
            $result = $this->discount->applyDiscountRules($productId, $priceValue, $currency, $filterData);
        }
        else
        {
            $rule = [
            	'DISCOUNT_VALUE' => $this->getField('DISCOUNT_VALUE'),
            	'DISCOUNT_UNIT' => $this->getField('DISCOUNT_UNIT'),
				'DISCOUNT_CURRENCY' => $this->getField('DISCOUNT_CURRENCY'),
			];
            $result = Rule\Simple::apply($rule, $priceValue, $currency);
        }

        return $result;
    }

    public function updateListener()
    {
        $this->handleChanges();
        $this->handleActiveDate();
    }

    public function isListenChanges()
    {
        return ($this->isActive() && $this->hasAutoUpdateSetup());
    }

    public function handleChanges($direction = null)
    {
        if ($direction === null) { $direction = $this->isListenChanges(); }

		$installer = new Watcher\Track\Installer(Glossary::SERVICE_SELF, Glossary::ENTITY_PROMO, $this->getId());

        if ($direction)
        {
			if ($this->isActiveDate())
			{
				$sources = $this->getTrackSourceList();
				$entities = array_merge(
					$this->getExternalBindEntities(),
					$this->getSelfBindEntities()
				);
			}
			else
			{
				$sources = [];
				$entities = $this->getSelfBindEntities();
			}

	        $installer->install($sources, $entities);
        }
        else
        {
	        $installer->uninstall();
        }
    }

    public function isListenActiveDate()
    {
        return ($this->isActive() && $this->getNextActiveDate() !== null && $this->hasAutoUpdateSetup());
    }

    public function handleActiveDate($direction = null)
    {
        $nextDate = $this->getNextActiveDate();

        if ($direction === null) { $direction = $this->isListenActiveDate(); }

        if ($direction && $nextDate)
        {
            Watcher\Track\EntityChange::schedule(Glossary::SERVICE_SELF, Glossary::ENTITY_PROMO, $this->getId(), $nextDate);
        }
        else
        {
	        Watcher\Track\EntityChange::release(Glossary::SERVICE_SELF, Glossary::ENTITY_PROMO, $this->getId());
        }
    }

    public function getTrackSourceList()
    {
	    $resultParts = [];

        if ($this->isSupportGift())
        {
            /** @var Market\Export\PromoGift\Model $promoGift */
            foreach ($this->getGiftCollection() as $promoGift)
            {
            	$giftContext = $promoGift->getContext();

            	if ($giftContext['EXPORT_GIFT'])
	            {
		            $resultParts[] = $promoGift->getTrackSourceList();
	            }
            }
        }

        if ($this->discount !== null)
        {
	        $resultParts[] = $this->discount->getTrackSourceList();
        }

        return !empty($resultParts) ? array_merge(...$resultParts) : [];
    }

	protected function getSelfBindEntities()
	{
		if ($this->getPromoField('START_DATE') === null && $this->getPromoField('FINISH_DATE') === null) { return []; }

		$result = [];

		foreach ($this->getSetupCollection() as $setup)
		{
			if (!$setup->isAutoUpdate() || !$setup->isFileReady()) { continue; }

			$result[] = new Market\Watcher\Track\BindEntity(Market\Export\Glossary::ENTITY_PROMO, $this->getId(), null, $setup->getId());
		}

		return $result;
	}

	protected function getExternalBindEntities()
	{
		if (!$this->isSupportGift()) { return []; }

		$partials = [];

		/** @var Market\Export\PromoGift\Model $promoGift */
		foreach ($this->getGiftCollection() as $promoGift)
		{
			/** @var Market\Export\Setup\Model $setup */
			foreach ($this->getSetupCollection() as $setup)
			{
				if (!$setup->isAutoUpdate() || !$setup->isFileReady()) { continue; }

				$partials[] = $promoGift->getSetupBindEntities($setup);
			}
		}

		return !empty($partials) ? array_merge(...$partials) : [];
	}

	public function getContext($isOnlySelf = false)
	{
		$result = [
			'PROMO_ID' => $this->getId(),
            'HAS_DISCOUNT_PRICE' => $this->hasProductDiscountPrice()
		];

		if (!$isOnlySelf)
		{
			$result = $this->mergeParentContext($result);
		}

		return $result;
	}

	public function hasAutoUpdateSetup()
    {
        $result = false;

        /** @var Market\Export\Setup\Model $setup */
        foreach ($this->getSetupCollection() as $setup)
        {
            if ($setup->isAutoUpdate() && $setup->isFileReady())
            {
                $result = true;
                break;
            }
        }

        return $result;
    }

	protected function mergeParentContext($selfContext)
	{
		/** @var \Yandex\Market\Export\Setup\Model $parent */
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

    /**
     * @return \Yandex\Market\Export\PromoProduct\Collection
     */
    public function getProductCollection()
    {
        return $this->getChildCollection('PROMO_PRODUCT');
    }

	/**
	 * @return bool
	 */
	public function isSupportGift()
    {
        return $this->getPromoType() === Table::PROMO_TYPE_GIFT_WITH_PURCHASE;
    }

    /**
     * @return \Yandex\Market\Export\PromoGift\Collection
     */
    public function getGiftCollection()
    {
        return $this->getChildCollection('PROMO_GIFT');
    }

	/**
	 * @return int|null
	 */
	public function getGiftLimit()
    {
        return 12;
    }

    /**
     * @return \Yandex\Market\Export\Setup\Collection
     */
    public function getSetupCollection()
    {
        return $this->getChildCollection('SETUP');
    }

    protected function getChildCollectionQueryParameters($fieldKey)
    {
        $result = [];

        switch ($fieldKey)
        {
            case 'SETUP':
                if (!$this->isExportForAll())
                {
                    $result['filter'] = [
                        '=PROMO_LINK.PROMO_ID' => $this->getId()
                    ];
                }
            break;

            default:
                $result = parent::getChildCollectionQueryParameters($fieldKey);
            break;
        }

        return $result;
    }

    protected function loadChildCollection($fieldKey)
    {
        $result = null;

        if ($this->discount !== null)
        {
            switch ($fieldKey)
            {
                case 'PROMO_GIFT':
                	$iblockList = ($this->discount !== null ? $this->discount->getGiftIblockList() : null);

                	$result = $this->buildPromoProductCollection($fieldKey, $iblockList);
				break;

                case 'PROMO_PRODUCT':
                    $result = $this->buildPromoProductCollection($fieldKey);
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
    	if (
    		$this->discount !== null
		    && ($fieldKey === 'PROMO_GIFT' || $fieldKey === 'PROMO_PRODUCT')
	    )
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
     * @param $iblockList int[]|null
     *
     * @return Market\Reference\Storage\Collection
     */
    protected function buildPromoProductCollection($fieldKey, $iblockList = null)
    {
		/** @var Market\Reference\Storage\Collection $result */
        /** @var Market\Export\PromoProduct\Model $promoProduct */

        $collectionClassName = $this->getChildCollectionReference($fieldKey);
        $modelClassName = $collectionClassName::getItemReference();

        $result = new $collectionClassName();
        $result->setParent($this);

        if ($iblockList === null) { $iblockList = $this->getSetupIblockList(); }

        foreach ($iblockList as $iblockId)
        {
            $promoProduct = $modelClassName::initialize([
                'PROMO_ID' => $this->getId(),
                'IBLOCK_ID' => $iblockId
            ]);

            $promoProduct->setCollection($result);
            $promoProduct->setDiscount($this->discount);

            $result->addItem($promoProduct);
        }

        return $result;
    }

	/**
	 * Список инфоблоков, используемых в профиле выгрузки
	 *
	 * @return int[]
	 */
	protected function getSetupIblockList()
	{
		/** @var Market\Export\IblockLink\Model $iblockLink */
		$parent = $this->getParent();
		$iblockList = [];

		if ($parent !== null && ($parent instanceof Market\Export\Setup\Model))
		{
			foreach ($parent->getIblockLinkCollection() as $iblockLink)
			{
				$iblockList[$iblockLink->getIblockId()] = true;
			}
		}
		else
		{
			foreach ($this->getSetupCollection() as $setup)
			{
				foreach ($setup->getIblockLinkCollection() as $iblockLink)
				{
					$iblockList[$iblockLink->getIblockId()] = true;
				}
			}
		}

		return array_keys($iblockList);
	}

    protected function getChildCollectionReference($fieldKey)
    {
        $result = null;

        switch ($fieldKey)
        {
            case 'PROMO_PRODUCT':
                $result = Market\Export\PromoProduct\Collection::getClassName();
            break;

            case 'PROMO_GIFT':
                $result = Market\Export\PromoGift\Collection::getClassName();
            break;

            case 'SETUP':
                $result = Market\Export\Setup\Collection::getClassName();
            break;
        }

        return $result;
    }
}