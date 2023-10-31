<?php

namespace Yandex\Market\Export\Promo\Discount;

use Bitrix\Main;
use Bitrix\Catalog;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class CatalogProvider extends SaleProvider
{
    const CATALOG_DISCOUNT_TYPE_DISCOUNT = 'Discount';
    const CATALOG_DISCOUNT_UNIT_PERCENT = 'P';
    const CATALOG_DISCOUNT_UNIT_SUM = 'S';
    const CATALOG_DISCOUNT_UNIT_FIX = 'F';

    /** @var string|false */
    protected $promoCode;

    public static function isEnvironmentSupport()
    {
        return Main\ModuleManager::isModuleInstalled('catalog') && !Market\Utils::isOnlySaleDiscount();
    }

    public static function getTitle()
    {
        return Market\Config::getLang('EXPORT_PROMO_PROVIDER_CATALOG_DISCOUNT_TITLE');
    }

    public static function getDescription()
    {
        return Market\Config::getLang('EXPORT_PROMO_PROVIDER_CATALOG_DISCOUNT_DESCRIPTION');
    }

    public static function getExternalEnum()
    {
        $result = [];

        if (Main\Loader::includeModule('catalog'))
        {
            $query = Catalog\DiscountTable::getList([
                'filter' => [
                    '=TYPE' => Catalog\DiscountTable::TYPE_DISCOUNT,
                    '=RENEWAL' => 'N'
                ],
                'select' => [ 'ID', 'NAME' ]
            ]);

            while ($discount = $query->fetch())
            {
                $result[] = [
                    'ID' => $discount['ID'],
                    'VALUE' => '[' . $discount['ID'] . '] ' . $discount['NAME']
                ];
            }
        }

        return $result;
    }

    public function detectPromoType()
    {
        if ($this->getPromoCode() !== null)
        {
            $result = Market\Export\Promo\Table::PROMO_TYPE_PROMO_CODE;
        }
        else
        {
            $result = Market\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT;
        }

        return $result;
    }

    protected function getPromoDiscountRule()
    {
        $discountUnit = Market\Export\Promo\Table::DISCOUNT_UNIT_CURRENCY;
        $discountValue = (float)$this->getField('VALUE');
        $discountPrice = null;

        switch ($this->getField('VALUE_TYPE'))
        {
            case Catalog\DiscountTable::VALUE_TYPE_PERCENT:
                $discountUnit = Market\Export\Promo\Table::DISCOUNT_UNIT_PERCENT;
            break;

            case Catalog\DiscountTable::VALUE_TYPE_SALE:
                $discountPrice = $discountValue;
                $discountValue = 0;
            break;
        }

        return [
            'DISCOUNT_LIMIT' => (float)$this->getField('MAX_DISCOUNT'),
            'DISCOUNT_VALUE' => $discountValue,
            'DISCOUNT_UNIT' => $discountUnit,
            'DISCOUNT_PRICE' => $discountPrice,
            'DISCOUNT_CURRENCY' => $this->getField('CURRENCY'),
        ];
    }

    public function getProductFilterList($context)
    {
        $conditionList = $this->getField('CONDITIONS_LIST');
        $filterList = QueryBuilder::convertActionToFilter($conditionList, $context);
        $result = [];

        if (!empty($filterList))
        {
            $discountRule = $this->getPromoDiscountRule();

            foreach ($filterList as $filter)
            {
                $result[] = [
                    'FILTER' => $filter,
                    'DATA' => [
                        'RULE' => $discountRule
                    ]
                ];
            }
        }

        return $result;
    }

    protected function applySimpleDiscountRule($rule, $price, $currency)
    {
        if ($rule['DISCOUNT_PRICE'] !== null)
        {
			$result = Market\Export\Promo\Rule\SetPrice::apply($rule, $price, $currency);
        }
        else
        {
            $result = parent::applySimpleDiscountRule($rule, $price, $currency);
        }

        return $result;
    }

    public function getGiftFilterList($context)
    {
        return [];
    }

    protected function getPromoCode()
    {
        if ($this->promoCode === null)
        {
            $this->promoCode = $this->loadPromoCode();
        }

        return ($this->promoCode !== false ? $this->promoCode : null);
    }

    /**
     * @return string|false
     */
    protected function loadPromoCode()
    {
        $result = false;

        if (Main\Loader::includeModule('catalog'))
        {
            $queryCoupon = Catalog\DiscountCouponTable::getList([
                'filter' => [
                    '=DISCOUNT_ID' => $this->id,
                    '=ACTIVE' => 'Y',
                    '=TYPE' => Catalog\DiscountCouponTable::TYPE_NO_LIMIT
                ],
                'select' => [
                    'COUPON'
                ],
                'order' => [
                    'ID' => 'ASC'
                ],
                'limit' => 1
            ]);

            if ($coupon = $queryCoupon->fetch())
            {
                $result = $coupon['COUPON'];
            }
        }

        return $result;
    }

    protected function loadFields()
    {
        $result = [];

        if (Main\Loader::includeModule('catalog'))
        {
            $query = Catalog\DiscountTable::getList([
                'filter' => [
                    '=ID' => $this->id
                ]
            ]);

            if ($discount = $query->fetch())
            {
                $result = $discount;
            }
        }

        return $result;
    }
}