<?php

namespace Yandex\Market\Export\Promo\Discount;

use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Sale;
use Yandex\Market;

class QueryBuilder
{
    const CONDITION_GROUP = 'CondGroup';
    const CONDITION_SUBGROUP = 'ActSaleSubGrp';
	const CONDITION_ROWGROUP = 'CondBsktRowGroup';
    const CONDITION_BASKET_GROUP = 'CondBsktProductGroup';

    /** @var array */
    protected static $fieldMap;
    /** @var array */
    protected static $compareMap;

    /**
     * Генерируем список фильтров на основании действия скидки
     *
     * @param $action
     * @param $context
     * @param $isInvertLogic bool
     * @param $filterChain string|null
     *
     * @return array
     */
    public static function convertActionToFilter($action, $context, $isInvertLogic = false)
    {
        $result = [];

        if (!empty($action['CHILDREN']) && is_array($action['CHILDREN']))
        {
            $conditionDataList = [];
            $subFilterList = null;
            $isLogicAnd = static::isLogicAnd($action);

            if (static::isInvertLogic($action))
            {
                $isInvertLogic = !$isInvertLogic;
            }

            foreach ($action['CHILDREN'] as $condition)
            {
                if (static::isGroupCondition($condition))
                {
                    $childFilterList = static::convertActionToFilter($condition, $context, $isInvertLogic);

                    if (empty($childFilterList))
                    {
                        // nothing
                    }
                    else if (!$isLogicAnd)
                    {
                        foreach ($childFilterList as $filter)
                        {
                            if (!static::isFoundFilterInList($filter, $result))
                            {
                                $result[] = $filter;
                            }
                        }
                    }
                    else if ($subFilterList === null)
                    {
                        $subFilterList = $childFilterList;
                    }
                    else
                    {
                        $newChainSubFilterList = [];

                        foreach ($subFilterList as $previousFilter)
                        {
                            foreach ($childFilterList as $newFilter)
                            {
                                $newChainSubFilterList[] = array_merge_recursive($previousFilter, $newFilter);
                            }
                        }

                        $subFilterList = $newChainSubFilterList;
                    }
                }
                else
                {
                    $conditionData = static::parseCondition($condition, $context, $isInvertLogic);

                    if ($conditionData !== null)
                    {
                        $conditionDataKey = $conditionData['FIELD']['TYPE'] . ':' . $conditionData['FIELD']['FIELD'] . ':' . $conditionData['COMPARE']['QUERY'];

                        if (isset($conditionDataList[$conditionDataKey]))
                        {
                            $conditionDataList[$conditionDataKey]['VALUE'] = array_merge(
                                (array)$conditionDataList[$conditionDataKey]['VALUE'],
                                (array)$conditionData['VALUE']
                            );
                        }
                        else
                        {
                            $conditionDataList[$conditionDataKey] = $conditionData;
                        }
                    }
                }
            }

            // get self filter by conditions

            $selfFilterList = static::makeElementFilterList($conditionDataList, $context, $isLogicAnd);
            $offerFilterList = static::makeOfferFilterList($conditionDataList, $context, $isLogicAnd);

            foreach ($offerFilterList as $offerFilter)
            {
                if (!static::isFoundFilterInList($offerFilter, $selfFilterList))
                {
                    $selfFilterList[] = $offerFilter;
                }
            }

            // merge with subfilter

            $hasSelfFilter = !empty($selfFilterList);
            $hasSubFilter = !empty($subFilterList);

            if ($hasSelfFilter && $hasSubFilter)
            {
                foreach ($selfFilterList as $selfFilter)
                {
                    foreach ($subFilterList as $subFilter)
                    {
                        $mergedFilter = array_merge_recursive($selfFilter, $subFilter);

                        if (!static::isFoundFilterInList($mergedFilter, $result))
                        {
                            $result[] = $mergedFilter;
                        }
                    }
                }
            }
            else if ($hasSelfFilter)
            {
                foreach ($selfFilterList as $selfFilter)
                {
                    if (!static::isFoundFilterInList($selfFilter, $result))
                    {
                        $result[] = $selfFilter;
                    }
                }
            }
            else if ($hasSubFilter)
            {
                foreach ($subFilterList as $subFilter)
                {
                    if (!static::isFoundFilterInList($subFilter, $result))
                    {
                        $result[] = $subFilter;
                    }
                }
            }
        }

        return $result;
    }

	/**
	 * Ид инфоблок для действия скидки
	 *
	 * @param $action
	 *
	 * @return int[]|null
	 */
	public static function getActionIblockList($action)
	{
		$result = [];

		if (!empty($action['CHILDREN']) && is_array($action['CHILDREN']))
		{
			foreach ($action['CHILDREN'] as $condition)
			{
				if (static::isGroupCondition($condition))
				{
					$conditionIblockList = static::getActionIblockList($condition);
				}
				else
				{
					$conditionData = static::parseCondition($condition);

					if ($conditionData === null)
					{
						$conditionIblockList = [];
					}
					else if (isset($conditionData['IBLOCK_ID']))
					{
						$conditionIblockList = [ (int)$conditionData['IBLOCK_ID'] ];
					}
					else
					{
						$conditionIblockList = static::getConditionIblockList($conditionData);
					}
				}

				foreach ($conditionIblockList as $conditionIblockId)
				{
					if (!in_array($conditionIblockId, $result, true))
					{
						$result[] = $conditionIblockId;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Список инфоблоков для условия действия скидки
	 *
	 * @param $condition
	 *
	 * @return int[]
	 */
	protected static function getConditionIblockList($condition)
	{
		$result = [];

		if ($condition['FIELD']['TYPE'] === Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD)
		{
			$sourceType = $condition['FIELD']['TYPE'];
			$source = Market\Export\Entity\Manager::getSource($sourceType);
			$conditionFilter = [
				'FIELD' => $condition['FIELD']['FIELD'],
				'COMPARE' => $condition['COMPARE']['QUERY'],
				'VALUE' => $condition['VALUE']
			];
			$queryFilter = $source->getQueryFilter([ $conditionFilter ], []);

			if (!empty($queryFilter['ELEMENT']) && Main\Loader::includeModule('iblock'))
			{
				$query = \CIBlockElement::GetList(
					[],
					$queryFilter['ELEMENT'],
					[ 'IBLOCK_ID' ],
					false,
					[ 'IBLOCK_ID' ]
				);

				while ($row = $query->Fetch())
				{
					$iblockId = (int)$row['IBLOCK_ID'];

					if (!in_array($iblockId, $result, true))
					{
						$result[] = $iblockId;
					}
				}
			}
		}

		return $result;
	}

    /**
     * Условия является группой
     *
     * @param $condition array
     *
     * @return bool
     */
    public static function isGroupCondition($condition)
    {
        return (
            isset($condition['CLASS_ID'])
            && (
                $condition['CLASS_ID'] === static::CONDITION_GROUP
                || $condition['CLASS_ID'] === static::CONDITION_SUBGROUP
                || $condition['CLASS_ID'] === static::CONDITION_ROWGROUP
                || $condition['CLASS_ID'] === static::CONDITION_BASKET_GROUP
            )
        );
    }

    /**
     * Логика условия И или ИЛИ
     *
     * @param $condition array
     *
     * @return bool
     */
    public static function isLogicAnd($condition)
    {
        return (!isset($condition['DATA']['All']) || $condition['DATA']['All'] !== 'OR');
    }

    /**
     * Инвертировать сравнение скидки
     *
     * @param $condition array
     *
     * @return bool
     */
    public static function isInvertLogic($condition)
    {
        return (
            (isset($condition['DATA']['True']) && $condition['DATA']['True'] === 'False')
            || (isset($condition['DATA']['Found']) && $condition['DATA']['True'] === 'NoFound')
        );
    }

    /**
     * Разбираем условие действия скидки
     *
     * @param array $condition
     * @param array|null $context
     * @param bool $isInvertLogic
     *
     * @return array|null
     */
    public static function parseCondition($condition, $context = null, $isInvertLogic = false)
    {
        $result = null;
        $field = static::getConditionField($condition, $context);
        $conditionValue = static::getConditionValue($condition);

        if ($field !== null && $conditionValue !== null && $conditionValue !== '')
        {
            $compare = static::getConditionCompare($condition);

            if ($isInvertLogic)
            {
                $compare['LOGIC'] = ($compare['LOGIC'] === 'AND' ? 'OR' : 'AND');

                if (Market\Data\TextString::getPosition($compare['QUERY'], '!') === 0)
                {
                    $compare['QUERY'] = Market\Data\TextString::getSubstring($compare['QUERY'], 1);
                }
                else
                {
                    $compare['QUERY'] = '!' . $compare['QUERY'];
                }
            }

            $result = [
                'FIELD' => $field,
                'COMPARE' => $compare,
                'VALUE' => $conditionValue
            ];
        }

        return $result;
    }

    /**
     * Поля для фильтра по условию действия скидки
     *
     * @param $condition
     * @param $context
     *
     * @return array|null
     */
    public static function getConditionField($condition, $context = null)
    {
        $result = null;

        if (isset($condition['CLASS_ID']))
        {
            $classId = $condition['CLASS_ID'];
            $field = null;
            $fieldMap = static::getFieldMap();

            if (isset($fieldMap[$classId]))
            {
                if (isset($fieldMap[$classId]['TAG']))
                {
                    $tagName = $fieldMap[$classId]['TAG'];

                    if (isset($context['TAGS'][$tagName]['TYPE'], $context['TAGS'][$tagName]['FIELD']))
                    {
                        $result = $fieldMap[$classId] + $context['TAGS'][$tagName];
                    }
                }
                else
                {
                    $result = $fieldMap[$classId];
                }
            }
            else if (Market\Data\TextString::getPosition($classId, 'CondIBProp') === 0) // is iblock property
            {
                $fieldParts = explode(':', $classId);

                if (isset($fieldParts[2]))
                {
                    $fieldIblockId = (int)$fieldParts[1];
                    $fieldPropertyId = (int)$fieldParts[2];

                    $result = [
                        'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY,
                        'FIELD' => $fieldPropertyId,
                        'PARENT' => false,
                        'IBLOCK_ID' => $fieldIblockId,
                        'PROPERTY_ID' => $fieldPropertyId,
                        'LOGIC' => null
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Сравнение для фильтра по условию действия скидки
     *
     * @param $condition
     *
     * @return array
     */
    public static function getConditionCompare($condition)
    {
        $map = static::getCompareMap();

        if (isset($map[$condition['DATA']['logic']]))
        {
            $logic = $condition['DATA']['logic'];
        }
        else
        {
            $logic = 'Equal';
        }

        return $map[$logic];
    }

	/**
	 * Значения для фильтра по условию действия скидки
	 *
	 * @param $condition
	 *
	 * @return mixed|null
	 */
	public static function getConditionValue($condition)
    {
	    $result = null;

	    if (isset($condition['DATA']['Value']))
	    {
		    $result = $condition['DATA']['Value'];
	    }
	    else if (isset($condition['DATA']['value']))
	    {
		    $result = $condition['DATA']['value'];
	    }

	    return $result;
    }

    /**
     * Поиск условий с указанными классами
     *
     * @param array $action
     * @param array $classList
     * @param bool $isClassListMap
     *
     * @return array
     */
    public static function searchConditionList($action, $classList, $isClassListMap = false)
    {
        $classMap = ($isClassListMap ? $classList : array_flip($classList));
        $result = [];

        // check self

        if (isset($classMap[$action['CLASS_ID']]))
        {
            $result[] = $action;
        }

        // find children

        if (!empty($action['CHILDREN']) && is_array($action['CHILDREN']))
        {
            foreach ($action['CHILDREN'] as $condition)
            {
                $foundConditionList = static::searchConditionList($condition, $classMap, true);

                foreach ($foundConditionList as $foundCondition)
                {
                    $result[] = $foundCondition;
                }
            }
        }

        return $result;
    }

    /**
     * Соответсвие классов действий скидок и полей
     *
     * @return array
     */
    protected static function getFieldMap()
    {
        if (static::$fieldMap === null)
        {
            static::$fieldMap =
                static::loadDefaultFieldMap()
                + static::loadCatalogFieldMap()
                + static::loadGiftFieldMap()
                + static::loadSaleFieldMap();
        }

        return static::$fieldMap;
    }

    /**
     * Загружаем соответсвие классов действий скидок и полей из модуля Торговый каталог
     *
     * @return array
     */
    protected static function loadCatalogFieldMap()
    {
        $result = [];

        if (Main\Loader::includeModule('catalog'))
        {
            $controls = \CCatalogCondCtrlIBlockFields::GetControls();

            if (!empty($controls) && is_array($controls))
            {
                foreach ($controls as $classId => $control)
                {
                    if (isset($control['FIELD']))
                    {
                        $sourceType = Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD;
                        $tableField = !empty($control['FIELD_TABLE']) ? $control['FIELD_TABLE'] : $control['FIELD'];
                        $hasParent = false;
                        $parentLogic = null;

                        if (isset($control['PARENT']))
                        {
                            $hasParent = true;
                            $parentLogic = Market\Data\TextString::getPosition($control['PARENT'], '&&') !== false ? 'AND' : 'OR';
                        }

                        if (isset($control['ENTITY']))
                        {
                            switch ($control['ENTITY'])
                            {
                                case 'PRODUCT':
                                    $sourceType = Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT;
                                    $hasParent = false;
                                    $parentLogic = null;
                                break;
                            }
                        }

                        $result[$classId] = [
                            'TYPE' => $sourceType,
                            'FIELD' => $tableField,
                            'PARENT' => $hasParent,
                            'LOGIC' => $parentLogic
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Загружаем соответсвие классов действий скидок и полей для Подарков
     *
     * @return array
     */
    protected static function loadGiftFieldMap()
    {
        return [
            'GifterCondIBElement' => [
                'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'ID',
                'PARENT' => false,
                'LOGIC' => null,
            ],
            'GifterCondIBSection' => [
                'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'SECTION_ID',
                'PARENT' => true,
                'LOGIC' => null,
            ]
        ];
    }

    /**
     * Загружаем соответсвие классов действий скидок и полей для полей по умолчанию
     *
     * @return array
     */
    protected static function loadDefaultFieldMap()
    {
        return [
            'CondIBElement' => [
                'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'ID',
                'PARENT' => false,
                'LOGIC' => null,
            ],
            'CondIBIBlock' => [
                'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'IBLOCK_ID',
                'PARENT' => false,
                'LOGIC' => null,
            ],
            'CondIBSection' => [
                'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'SECTION_ID',
                'PARENT' => true,
                'LOGIC' => null,
            ],
        ];
    }

    /**
     * Загружаем соответсвие классов действий скидок из модуля Интернет-магазин и полей для полей по умолчанию
     *
     * @return array
     */
    protected static function loadSaleFieldMap()
    {
        return [
            'CondBsktFldProduct' => [
                'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'ID',
                'PARENT' => false,
                'LOGIC' => null,
            ],
            'CondBsktFldPrice' => [
                'TAG' => 'oldprice',
                'PARENT' => false,
                'LOGIC' => null,
            ],
            'CondBsktFldSumm' => [
                'TAG' => 'oldprice',
                'PARENT' => false,
                'LOGIC' => null,
            ],
        ];
    }

    /**
     * Соответсвие классов сравнения действия скидок и сравнения запроса к бд
     *
     * @return array
     */
    protected static function getCompareMap()
    {
        if (static::$compareMap === null)
        {
            static::$compareMap =
                static::loadCatalogCompareMap()
                + static::loadDefaultCompareMap();
        }

        return static::$compareMap;
    }

    /**
     * Загружаем сравнения из модуля Торговый каталог
     *
     * @return array
     */
    protected static function loadCatalogCompareMap()
    {
        $result = [];

        if (Main\Loader::includeModule('catalog'))
        {
            $operatorList = \CCatalogCondCtrlIBlockFields::GetLogic();
            $queryMap = static::getCompareQueryMap();

            if (!empty($operatorList) && is_array($operatorList))
            {
                foreach ($operatorList as $operator)
                {
                    if (isset($queryMap[$operator['VALUE']]))
                    {
                        $result[$operator['VALUE']] = [
                            'QUERY' => $queryMap[$operator['VALUE']],
                            'PARENT' => isset($operator['PARENT'])
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Загружаем сравнения по умолчанию
     *
     * @return array
     */
    protected static function loadDefaultCompareMap()
    {
        return [
            'Equal' => [
                'QUERY' => '=',
                'PARENT' => true
            ],
            'Not' => [
                'QUERY' => '!=',
                'PARENT' => true
            ]
        ];
    }

    /**
     * Соответствие классов сравнения модуля Торговый каталог и сравнений запросов к бд
     *
     * @return array
     */
    protected static function getCompareQueryMap()
    {
        return [
            'Equal' => '=',
            'Not' => '!=',
            'Great' => '>',
            'Less' => '<',
            'EqGr' => '>=',
            'EqLs' => '<=',
            'Contain' => '%',
            'NotCont' => '!%'
        ];
    }

    /**
     * Фильтр по элементам
     *
     * @param array $conditionDataList
     * @param array $context
     * @param bool $isLogicAnd
     *
     * @return array
     */
    protected static function makeElementFilterList($conditionDataList, $context, $isLogicAnd = false)
    {
        $elementFilter = [];
        $hasContextOffer = isset($context['OFFER_IBLOCK_ID']);
        $result = [];

        foreach ($conditionDataList as $conditionData)
        {
            $conditionSource = $conditionData['FIELD']['TYPE'];
            $condition = [
                'FIELD' => $conditionData['FIELD']['FIELD'],
                'COMPARE' => $conditionData['COMPARE']['QUERY'],
                'VALUE' => $conditionData['VALUE'],
	            'STRICT' => true,
            ];
            $conditionIblockId = null;

            if (isset($conditionData['FIELD']['IBLOCK_ID']))
            {
                $conditionIblockId = (int)$conditionData['FIELD']['IBLOCK_ID'];
            }
            else if ($condition['FIELD'] === 'IBLOCK_ID' && $condition['COMPARE'] === '=')
            {
                $conditionIblockId = (int)$condition['VALUE'];
            }

            if ($conditionIblockId !== null)
            {
                if ($conditionIblockId === $context['IBLOCK_ID'])
                {
                    // nothing
                }
                else if ($hasContextOffer && $conditionIblockId === $context['OFFER_IBLOCK_ID'])
                {
                    $conditionSource = static::convertElementSourceToOffer($conditionSource);
                }
                else if ($isLogicAnd)
                {
                    $result = []; // invalid filter => empty result
                    $elementFilter = null;
                    break;
                }
                else
                {
                    continue; // ignore this condition
                }
            }

            if ($isLogicAnd)
            {
                if (!isset($elementFilter[$conditionSource])) { $elementFilter[$conditionSource] = []; }

                $elementFilter[$conditionSource][] = $condition;
            }
            else
            {
                $result[] = [
                    $conditionSource => [ $condition ]
                ];
            }
        }

        if ($isLogicAnd && !empty($elementFilter))
        {
            $result[] = $elementFilter;
        }

        return $result;
    }

    /**
     * Фильтр по торговым предложениям
     *
     * @param array $conditionDataList
     * @param array $context
     * @param bool $isLogicAnd
     *
     * @return array
     */
    protected static function makeOfferFilterList($conditionDataList, $context, $isLogicAnd = false)
    {
        $result = [];

        if (isset($context['OFFER_IBLOCK_ID']))
        {
            $hasMultipleSourceLogicAnd = false;
            $multipleSourceConditionList = [];
            $elementFilter = [];

            foreach ($conditionDataList as $conditionData)
            {
                $isValidCondition = true;
                $conditionElementSource = $conditionData['FIELD']['TYPE'];
                $conditionSource = null;
                $condition = [
                    'FIELD' => $conditionData['FIELD']['FIELD'],
                    'COMPARE' => $conditionData['COMPARE']['QUERY'],
                    'VALUE' => $conditionData['VALUE'],
	                'STRICT' => true,
                ];
                $conditionIblockId = null;

                if (isset($conditionData['FIELD']['IBLOCK_ID']))
                {
                    $conditionIblockId = (int)$conditionData['FIELD']['IBLOCK_ID'];
                }
                else if ($condition['FIELD'] === 'IBLOCK_ID' && $condition['COMPARE'] === '=')
                {
                    $conditionIblockId = (int)$condition['VALUE'];
                }

                if ($conditionIblockId !== null) // detect source by iblock id
                {
                    if ($conditionIblockId === $context['IBLOCK_ID'])
                    {
                        $conditionSource = $conditionElementSource;
                    }
                    else if ($conditionIblockId === $context['OFFER_IBLOCK_ID'])
                    {
                        $conditionSource = static::convertElementSourceToOffer($conditionElementSource);
                    }
                    else
                    {
                        $isValidCondition = false;
                    }
                }
                else if ($condition['FIELD'] === 'SECTION_ID') // is only element filter
                {
                    $conditionSource = $conditionElementSource;
                }
                else if (!$isLogicAnd || $conditionData['FIELD']['PARENT'] === false || $conditionData['COMPARE']['PARENT'] === false)
                {
                    $conditionSource = static::convertElementSourceToOffer($conditionElementSource);
                }

                if ($isValidCondition)
                {
                    if ($conditionSource === null)
                    {
                        $multipleSourceConditionList[] = [
                            'LOGIC' => $conditionData['FIELD']['LOGIC'],
                            'SOURCE' => $conditionElementSource,
                            'CONDITION' => $condition
                        ];

                        if ($conditionData['FIELD']['LOGIC'] === 'AND')
                        {
                            $hasMultipleSourceLogicAnd = true;
                        }
                    }
                    else if ($isLogicAnd)
                    {
                        if (!isset($elementFilter[$conditionSource])) { $elementFilter[$conditionSource] = []; }

                        $elementFilter[$conditionSource][] = $condition;
                    }
                    else
                    {
                        $result[] = [
                            $conditionSource => [ $condition ]
                        ];
                    }
                }
                else if ($isLogicAnd)
                {
                    $result = []; // invalid filter => empty result
                    $elementFilter = null;
                    break;
                }
            }

            if ($isLogicAnd && $elementFilter !== null)
            {
                // add conditions requires parent

                if ($hasMultipleSourceLogicAnd || count($multipleSourceConditionList) > 1)
                {
                    foreach ($multipleSourceConditionList as $multipleSourceCondition)
                    {
                        $offerCondition = $multipleSourceCondition['CONDITION'];
                        $offerConditionSource = static::convertElementSourceToOffer($multipleSourceCondition['SOURCE']);
                        $elementConditionSource = Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY;
                        $elementConditionField = $context['OFFER_PROPERTY_ID'] . '.' . $offerCondition['FIELD'];
                        $elementCondition = [ 'FIELD' => $elementConditionField ] + $offerCondition;

                        if ($multipleSourceCondition['LOGIC'] === 'AND')
                        {
                            // offer condition

                            if (!isset($elementFilter[$offerConditionSource])) { $elementFilter[$offerConditionSource] = []; }

                            $elementFilter[$offerConditionSource][] = $offerCondition;

                            // element condition

                            if (!isset($elementFilter[$elementConditionSource])) { $elementFilter[$elementConditionSource] = []; }

                            $elementFilter[$elementConditionSource][] = $elementCondition;
                        }
                        else
                        {
                            $elementFilter[] = [
                                'LOGIC' => $multipleSourceCondition['LOGIC'],
                                $offerConditionSource => [ $offerCondition ],
                                $elementConditionSource => [ $elementCondition ],
                            ];
                        }
                    }
                }
                else
                {
                    foreach ($multipleSourceConditionList as $multipleSourceCondition)
                    {
                        $conditionSource = static::convertElementSourceToOffer($multipleSourceCondition['SOURCE']);
                        $condition = $multipleSourceCondition['CONDITION'];

                        if (!isset($elementFilter[$conditionSource])) { $elementFilter[$conditionSource] = []; }

                        $elementFilter[$conditionSource][] = $condition;
                    }
                }

                // add to result

	            if (!empty($elementFilter))
	            {
                    $result[] = $elementFilter;
                }
            }
        }

        return $result;
    }

    /**
     * Конвертируем источник данных элемента в источник данных для торгового предложения
     *
     * @param string $sourceType
     *
     * @return string
     */
    protected static function convertElementSourceToOffer($sourceType)
    {
        $result = $sourceType;

        switch ($sourceType)
        {
            case Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD:
                $result = Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD;
            break;

            case Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY:
                $result = Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY;
            break;
        }

        return $result;
    }

    /**
     * Присутствует ли фильтр в списке фильтров
     *
     * @param array $searchFilter
     * @param array $list
     *
     * @return bool
     */
    protected static function isFoundFilterInList($searchFilter, $list)
    {
        $result = false;

        foreach ($list as $listFilter)
        {
            $isMatchItem = true;

            foreach ($searchFilter as $filterSource => $filterConditions)
            {
                if (!isset($listFilter[$filterSource]) || $listFilter[$filterSource] != $filterConditions)
                {
                    $isMatchItem = false;
                    break;
                }
            }

            if ($isMatchItem)
            {
                $result = true;
                break;
            }
        }

        return $result;
    }
}