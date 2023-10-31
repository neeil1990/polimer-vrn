<?php

namespace Yandex\Market\Export\Promo\Discount;

use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class SaleProvider extends AbstractProvider
{
    const SALE_ACTION_BASKET_DISCOUNT = 'ActSaleBsktGrp';
    const SALE_ACTION_GIFT = 'GiftCondGroup';

    const SALE_DISCOUNT_TYPE_DISCOUNT = 'Discount';
    const SALE_DISCOUNT_UNIT_PERCENT = 'Perc';
    const SALE_DISCOUNT_UNIT_SUM = 'CurEach';

    const SALE_CONDITION_BASKET_COUNT_GROUP = 'CondBsktCntGroup';
    const SALE_CONDITION_BASKET_ROW_GROUP = 'CondBsktRowGroup';
    const SALE_CONDITION_BASKET_QUANTITY = 'CondBsktFldQuantity';

    const SALE_CONDITION_TIME_WEEKDAY = 'CondSaleCmnDayOfWeek';

    protected $publicAccess;
    protected $filterCoupons = [];

    /**
     * Поддерживается ли выполнение в текущем окружении
     *
     * @return bool
     */
    public static function isEnvironmentSupport()
    {
        return Main\ModuleManager::isModuleInstalled('sale');
    }

    public static function getTitle()
    {
    	$isOnlySale = Market\Utils::isOnlySaleDiscount();

        return Market\Config::getLang('EXPORT_PROMO_PROVIDER_SALE_DISCOUNT_TITLE' . ($isOnlySale ? '_ALONE' : ''));
    }

    public static function getDescription()
    {
        return Market\Config::getLang('EXPORT_PROMO_PROVIDER_SALE_DISCOUNT_DESCRIPTION');
    }

    /**
     * Список скидок для выбора в качестве источника
     *
     * @return array
     */
    public static function getExternalEnum()
    {
        $result = [];

        if (Main\Loader::includeModule('sale'))
        {
            $query = Sale\Internals\DiscountTable::getList([
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

    /**
     * Определяем тип скидки
     *
     * @return string|null
     */
    public function detectPromoType()
    {
        $actionList = $this->getActionList();
        $firstAction = reset($actionList);
        $result = null;

        if ($firstAction !== false)
        {
            $result = $this->getActionType($firstAction);
        }

        return $result;
    }

    /**
     * Данные для promo
     *
     * @return array
     */
    public function getPromoFields()
    {
        $result =
	        $this->getPromoFieldsByType()
	        + $this->getPromoFieldsDefaults();

        $result = $this->applyPromoFieldsWeekdaysCondition($result);

        return $result;
    }

    protected function getPromoFieldsDefaults()
    {
    	return [
		    'START_DATE' => $this->getField('ACTIVE_FROM'),
		    'FINISH_DATE' => $this->getField('ACTIVE_TO'),
		    'DISCOUNT_UNIT' => null,
		    'DISCOUNT_CURRENCY' => null,
		    'DISCOUNT_VALUE' => null,
		    'PROMO_CODE' => null,
		    'GIFT_REQUIRED_QUANTITY' => null,
		    'GIFT_FREE_QUANTITY' => null,
	    ];
    }

    protected function getPromoFieldsByType()
    {
	    switch ($this->getPromoType())
	    {
		    case Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
			    $conditionQuantity = $this->getRequiredQuantityFromConditions();

			    $result = [
				    'GIFT_REQUIRED_QUANTITY' => ($conditionQuantity !== null ? $conditionQuantity : 1),
			        'GIFT_FREE_QUANTITY' => 1,
			    ];
		    break;

		    case Market\Export\Promo\Table::PROMO_TYPE_PROMO_CODE:
			    $result = (array)$this->getPromoDiscountRule();
			    $result['PROMO_CODE'] = $this->getPromoCode();
		    break;

		    default:
			    $result = (array)$this->getPromoDiscountRule();
		    break;
	    }

	    return $result;
    }

    protected function applyPromoFieldsWeekdaysCondition($fields)
    {
	    $weekdays = $this->getWeekdaysLimitFromConditions();
	    $result = $fields;

	    if (!empty($weekdays))
	    {
		    $weekdaysCondition = new WeekdayCondition($weekdays);
		    list($weekdayStart, $weekdayFinish) = $weekdaysCondition->getDateRange($result['START_DATE'], $result['FINISH_DATE']);

		    if ($weekdayStart !== null && $weekdayFinish !== null)
		    {
			    $result['START_DATE'] = $weekdayStart;
			    $result['FINISH_DATE'] = $weekdayFinish;
		    }
	    }

	    return $result;
    }

    /**
     * Фильтр по товарам
     *
     * @param $context
     *
     * @return array
     */
    public function getProductFilterList($context)
    {
        switch ($this->getPromoType())
        {
            case Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
                $result = $this->getProductFilterListFromConditions($context);
            break;

            default:
                $result = $this->getProductFilterListFromActions($context);
            break;
        }

        return $result;
    }

	/**
	 * Список инфоблоков с подарками.
	 * При вовзрате null будут использованы инфоблоки, которые указаны в профиле выгрузки.
	 *
	 * @return int[]|null
	 */
	public function getGiftIblockList()
	{
		$result = null;

		if ($this->getPromoType() === Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE)
		{
			$iblockList = $this->getProductIblockListFromActions();
			$iblockList = $this->mergeIblockListOfferToCatalog($iblockList);

			if (!empty($iblockList))
			{
				$result = $iblockList;
			}
		}

		return $result;
	}

    /**
     * Фильтр по подаркам
     *
     * @param $context
     *
     * @return array
     */
    public function getGiftFilterList($context)
    {
        switch ($this->getPromoType())
        {
            case Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
                $result = $this->getProductFilterListFromActions($context);
            break;

            default:
                $result = []; // no gift
            break;
        }

        return $result;
    }

    /**
     * Применяем правило скидки к товару
     *
     * @param $productId
     * @param $price
     * @param null $currency
     * @param null $filterData
     *
     * @return float
     */
    public function applyDiscountRules($productId, $price, $currency = null, $filterData = null)
    {
        $result = $price;

        if (isset($filterData['RULE']))
        {
            $result = $this->applySimpleDiscountRule($filterData['RULE'], $price, $currency);
        }

        return $result;
    }

    /**
     * Применяем простое правило скидки к цене на товар
     *
     * @param $rule
     * @param $price
     * @param $currency
     *
     * @return float
     */
    protected function applySimpleDiscountRule($rule, $price, $currency)
    {
        return Market\Export\Promo\Rule\Simple::apply($rule, $price, $currency);
    }

    /**
     * Точность округления для применения скидки
     *
     * @return int
     */
    protected function getApplyPrecision()
    {
        return (int)Main\Config\Option::get('sale', 'value_precision', 2, '');
    }

    /**
     * Правило скидки (выбирается максимальное, конвертация не производится)
     *
     * @return array|null
     */
    protected function getPromoDiscountRule()
    {
        $actionList = $this->getActionList();
        $result = null;

        foreach ($actionList as $action)
        {
            $actionRule = $this->getActionDiscountRule($action);

            if (
                $actionRule !== null
                && (
                    $result === null
                    || ($result['DISCOUNT_UNIT'] === $actionRule['DISCOUNT_UNIT'] && $result['DISCOUNT_VALUE'] < $actionRule['DISCOUNT_VALUE'])
                )
            )
            {
                $result = $actionRule;
            }
        }

        return $result;
    }

	/**
	 * Список используемых ифноблоков в действиях скидки
	 *
	 * @return int[]
	 */
	protected function getProductIblockListFromActions()
	{
		$actionList = $this->getActionList();
		$result = [];

		foreach ($actionList as $action)
		{
			$actionIblockList = QueryBuilder::getActionIblockList($action);

			foreach ($actionIblockList as $actionIblockId)
			{
				if (!in_array($actionIblockId, $result, true))
				{
					$result[] = $actionIblockId;
				}
			}
		}

		return $result;
	}

	/**
	 * Заменяем инфоблок торговых предложений на каталог
	 *
	 * @param $iblockList int[]
	 *
	 * @return int[]
	 */
	protected function mergeIblockListOfferToCatalog($iblockList)
	{
		$result = [];

		foreach ($iblockList as $iblockId)
		{
			$catalogIblockId = Market\Export\Entity\Iblock\Provider::getCatalogIblockId($iblockId);

			if ($catalogIblockId === null)
			{
				$catalogIblockId = $iblockId;
			}

			if (!in_array($catalogIblockId, $result, true))
			{
				$result[] = $catalogIblockId;
			}
		}

		return $result;
	}

    /**
     * Фильтр элементов инфоблока на основе условий скидки
     *
     * @param $context
     *
     * @return array
     */
    protected function getProductFilterListFromConditions($context)
    {
        $conditionList = $this->getField('CONDITIONS_LIST');
        $filterList = QueryBuilder::convertActionToFilter($conditionList, $context);
        $result = [];

        if (!empty($filterList))
        {
            foreach ($filterList as $filter)
            {
                $result[] = [
                    'FILTER' => $filter,
                    'DATA' => null
                ];
            }
        }

        return $result;
    }

    /**
     * Фильтр элементов инфоблока на основе ограничений действий скидки
     *
     * @param $context array
     *
     * @return array
     */
    protected function getProductFilterListFromActions($context)
    {
        $actionList = $this->getActionList();
        $result = [];

        foreach ($actionList as $action)
        {
            $actionFilterList = QueryBuilder::convertActionToFilter($action, $context);

            if (!empty($actionFilterList))
            {
                $discountRule = $this->getActionDiscountRule($action);

                foreach ($actionFilterList as $filter)
                {
                    $result[] = [
                        'FILTER' => $filter,
                        'DATA' => [
                            'RULE' => $discountRule
                        ]
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Минимально требуемое количество для получения подарка
     *
     * @return int|null
     */
    protected function getRequiredQuantityFromConditions()
    {
        $result = null;
        $conditionList = $this->getField('CONDITIONS_LIST');
        $quantityConditionList = QueryBuilder::searchConditionList($conditionList, [
            static::SALE_CONDITION_BASKET_COUNT_GROUP,
            static::SALE_CONDITION_BASKET_ROW_GROUP,
            static::SALE_CONDITION_BASKET_QUANTITY
        ]);
        $supportCompareMap = [
            '>'  => true,
            '>=' => true,
            '='  => true
        ];

        foreach ($quantityConditionList as $quantityCondition)
        {
            $conditionValue = (float)QueryBuilder::getConditionValue($quantityCondition);
            $conditionCompare = QueryBuilder::getConditionCompare($quantityCondition);

            if (
                $conditionValue > 0 && ($result === null || $conditionValue < $result)
                && isset($supportCompareMap[$conditionCompare['QUERY']])
            )
            {
                $result = $conditionValue;
            }
        }

        return $result;
    }

	/**
	 * Актуальные дни недели скидки
	 *
	 * @return int[]|null
	 */
    protected function getWeekdaysLimitFromConditions()
    {
	    $result = null;
	    $conditionList = $this->getField('CONDITIONS_LIST');
	    $weekdaysConditions = QueryBuilder::searchConditionList($conditionList, [
		    static::SALE_CONDITION_TIME_WEEKDAY,
	    ]);
	    $compareMap = [
	    	'=' => true,
		    '!=' => false,
	    ];
	    $result = null;

	    foreach ($weekdaysConditions as $weekdaysCondition)
	    {
		    $conditionValue = QueryBuilder::getConditionValue($weekdaysCondition);
		    $conditionCompare = QueryBuilder::getConditionCompare($weekdaysCondition);

		    if (is_array($conditionValue))
		    {
			    $conditionWeekdays = $conditionValue;
			    Main\Type\Collection::normalizeArrayValuesByInt($conditionWeekdays);
		    }
		    else
		    {
			    $conditionWeekdays = null;
		    }

		    if (isset($compareMap[$conditionCompare['QUERY']]) && !empty($conditionValue))
		    {
			    $conditionWeekdays = (array)$conditionValue;
			    $compareResult = $compareMap[$conditionCompare['QUERY']];

				if ($compareResult)
				{
					if ($result === null)
					{
						$result = $conditionWeekdays;
					}
					else
					{
						$result = array_merge($result, $conditionWeekdays);
					}
				}
				else
				{
					if ($result === null)
					{
						$result = range(1, 7);
					}

					$result = array_diff($result, $conditionWeekdays);
				}
		    }
	    }

	    return $result;
    }

    /**
     * Действия скидки (выбираются только однотипные действия)
     *
     * @return array
     */
    protected function getActionList()
    {
        $actionList = $this->getField('ACTIONS_LIST');
        $firstActionType = null;
        $result = [];

        if (!empty($actionList['CHILDREN']) && is_array($actionList['CHILDREN']))
        {
            foreach ($actionList['CHILDREN'] as $action)
            {
                $actionType = $this->getActionType($action);

                if ($actionType !== null && ($firstActionType === null || $actionType === $firstActionType))
                {
                    $isValidAction = true;

                    if ($actionType !== Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE)
                    {
                        $rule = $this->parseActionDiscountRule($action);
                        $isValidAction = $this->isValidDiscountRule($rule);
                    }

                    if ($isValidAction)
                    {
                        $firstActionType = $actionType;
                        $result[] = $action;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Загружаем данные скидки
     *
     * @return array
     */
    protected function loadFields()
    {
        $result = [];

        if (Main\Loader::includeModule('sale'))
        {
            $query = Sale\Internals\DiscountTable::getList([
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

    /**
     * Купон скидки
     *
     * @return string|null
     */
    protected function getPromoCode()
    {
        return $this->getPublicCoupon();
    }

    protected function hasCoupons()
    {
    	return $this->getField('USE_COUPONS') === 'Y';
    }

    protected function getPublicCoupon()
    {
    	return $this->getFilterCoupon('public', [
		    '=USER_ID' => 0,
		    '=TYPE' => Sale\Internals\DiscountCouponTable::TYPE_MULTI_ORDER,
	    ]);
    }

    protected function getBonusCoupon()
    {
	    return $this->getFilterCoupon('bonus', [
		    '>USER_ID' => 0,
		    '=TYPE' => Sale\Internals\DiscountCouponTable::TYPE_MULTI_ORDER,
	    ]);
    }

    protected function getFilterCoupon($type, $filter)
    {
    	if (!array_key_exists($type, $this->filterCoupons))
	    {
	    	$this->filterCoupons[$type] = $this->fetchFilterCoupon($filter);
	    }

    	return $this->filterCoupons[$type];
    }

    protected function fetchFilterCoupon($filter)
    {
    	$result = null;

        if ($this->hasCoupons() && Main\Loader::includeModule('sale'))
        {
            $now = new Main\Type\DateTime();
            $queryFilter = array_merge(
	            [ '=DISCOUNT_ID' => $this->id ],
	            $filter,
	            [
		            '=ACTIVE' => 'Y',
		            [
			            'LOGIC' => 'OR',
			            'ACTIVE_FROM' => false,
			            '<=ACTIVE_FROM' => $now,
		            ],
		            [
			            'LOGIC' => 'OR',
			            'ACTIVE_TO' => false,
			            '>=ACTIVE_TO' => $now,
		            ]
	            ]
            );

            $queryCoupon = Sale\Internals\DiscountCouponTable::getList([
                'filter' => $queryFilter,
                'select' => [
                    'MAX_USE',
                    'USE_COUNT',
                    'COUPON'
                ],
                'limit' => 10
            ]);

            while ($coupon = $queryCoupon->fetch())
            {
                $maxUse = (int)$coupon['MAX_USE'];
                $couponValue = (string)$coupon['COUPON'];

                if ($couponValue !== '' && ($maxUse <= 0 || (int)$coupon['USE_COUNT'] < $maxUse))
                {
                    $result = $couponValue;
                }
            }
        }

        return $result;
    }

    /**
     * Тип действия
     *
     * @param $action
     *
     * @return string|null
     */
    protected function getActionType($action)
    {
        $result = null;

        if (!isset($action['CLASS_ID']))
        {
        	// nothing
        }
        else if ($action['CLASS_ID'] === static::SALE_ACTION_GIFT)
        {
	        $result = Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE; // no support for n plus m
        }
        else if ($action['CLASS_ID'] === static::SALE_ACTION_BASKET_DISCOUNT)
        {
	        if (!$this->hasPublicAccess())
	        {
	            $result = Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD;
	        }
	        else if ($this->hasCoupons())
	        {
	        	if ($this->getPublicCoupon() !== null)
		        {
		            $result = Market\Export\Promo\Table::PROMO_TYPE_PROMO_CODE;
                }
	        	else if ($this->getBonusCoupon() !== null)
		        {
			        $result = Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD;
		        }
	        }
	        else
	        {
	        	$result = Market\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT;
	        }
        }

        return $result;
    }

    /**
     * Правило скидки для действия
     *
     * @param $action
     *
     * @return array|null
     */
    protected function getActionDiscountRule($action)
    {
        $result = null;

        if (isset($action['CLASS_ID']))
        {
            switch ($action['CLASS_ID'])
            {
                case static::SALE_ACTION_BASKET_DISCOUNT:
                    $rule = $this->parseActionDiscountRule($action);

                    if ($this->isValidDiscountRule($rule))
                    {
                        $result = $rule;
                    }
                break;
            }
        }

        return $result;
    }

    /**
     * Поддерживаются ли правила действия для выгрузки
     *
     * @param $rule
     *
     * @return bool
     */
    protected function isValidDiscountRule($rule)
    {
        return ($rule['DISCOUNT_TYPE'] === static::SALE_DISCOUNT_TYPE_DISCOUNT && $rule['DISCOUNT_VALUE'] > 0);
    }

    /**
     * Правила скидки
     *
     * @param $action
     *
     * @return array
     */
    protected function parseActionDiscountRule($action)
    {
        return [
           'DISCOUNT_TYPE' => isset($action['DATA']['Type']) ? $action['DATA']['Type'] : static::SALE_DISCOUNT_TYPE_DISCOUNT,
           'DISCOUNT_LIMIT' => (float)(isset($action['DATA']['Max']) ? $action['DATA']['Max'] : 0),
           'DISCOUNT_VALUE' => (float)(isset($action['DATA']['Value']) ? $action['DATA']['Value'] : 0),
           'DISCOUNT_UNIT' =>
               isset($action['DATA']['Unit']) && $action['DATA']['Unit'] === static::SALE_DISCOUNT_UNIT_PERCENT
                   ? Market\Export\Promo\Table::DISCOUNT_UNIT_PERCENT
                   : Market\Export\Promo\Table::DISCOUNT_UNIT_CURRENCY,
           'DISCOUNT_CURRENCY' => Main\Config\Option::get('sale', 'default_currency', 'RUB', '')
        ];
    }

    protected function hasPublicAccess()
    {
    	if ($this->publicAccess === null && Main\Loader::includeModule('sale'))
	    {
			$anonymousGroups = $this->getAnonymousGroups();

		    $query = Sale\Internals\DiscountGroupTable::getList([
			    'filter' => [
				    '=DISCOUNT_ID' => $this->id,
				    '=GROUP_ID' => $anonymousGroups,
				    '=ACTIVE' => 'Y',
			    ],
			    'select' => [ 'GROUP_ID' ],
			    'limit' => 1
		    ]);

		    $this->publicAccess = (bool)$query->fetch();
	    }

		return $this->publicAccess;
    }

	protected function getAnonymousGroups()
	{
		if (method_exists(Main\UserTable::class, 'getUserGroupIds'))
		{
			$result = Main\UserTable::getUserGroupIds(0);
		}
		else
		{
			$result = [2];
		}

		return $result;
	}
}