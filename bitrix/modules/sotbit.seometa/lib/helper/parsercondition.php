<?
namespace Sotbit\Seometa\Helper;

use Sotbit\Seometa\Price\PriceManager;

class ParserCondition
{
    /**
     * Return parsed conditions array.
     *
     * @param $condition
     * @param $params
     * @return array
     */
    public function parseCondition($condition, $params, $iblockId = false)
    {
        $result = array();

        if (!empty($condition) && is_array($condition))
        {
            if ($condition['CLASS_ID'] === 'CondGroup')
            {
                if (!empty($condition['CHILDREN']))
                {
                    foreach ($condition['CHILDREN'] as $child)
                    {
                        $childResult = $this->parseCondition($child, $params, $iblockId);

                        // is group
                        if ($child['CLASS_ID'] === 'CondGroup')
                        {
                            $result[] = $childResult;
                        }
                        // same property names not overrides each other
                        elseif (isset($result[key($childResult)]))
                        {
                            $fieldName = key($childResult);

                            if (!isset($result['LOGIC']))
                            {
                                $result = array(
                                    'LOGIC' => $condition['DATA']['All'],
                                    array($fieldName => $result[$fieldName])
                                );
                            }

                            $result[][$fieldName] = $childResult[$fieldName];
                        }
                        else
                        {
                            $result += $childResult;
                        }
                    }

                    if (!empty($result))
                    {
                        $this->parsePropertyCondition($result, $condition, $params, $iblockId);

                        if (count($result) > 1)
                        {
                            $result['LOGIC'] = $condition['DATA']['All'];
                        }
                    }
                }
            }
            else
            {
                $result += $this->parseConditionLevel($condition, $params);
            }
        }

        return $result;
    }

    protected function parseConditionLevel($condition, $params)
    {
        $result = array();

        if (!empty($condition) && is_array($condition))
        {
            $name = $this->parseConditionName($condition);
            if (!empty($name))
            {
                $operator = $this->parseConditionOperator($condition);
                $value = $this->parseConditionValue($condition, $name);
                $result[$operator.$name] = $value;

                if ($name === 'SECTION_ID')
                {
                    $result['INCLUDE_SUBSECTIONS'] = isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'N' ? 'N' : 'Y';
                    if (isset($params['ACTIVE']) && $params['ACTIVE'] === 'Y') {
                        $result['ACTIVE'] = 'Y';
                    }

                    if (isset($params['AVAILABLE']) && $params['AVAILABLE'] === 'Y') {
                        $result['AVAILABLE'] = 'Y';
                    }

                    if (isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'A')
                    {
                        $result['SECTION_GLOBAL_ACTIVE'] = 'Y';
                    }

                    $result = array($result);
                }
            }
        }

        return $result;
    }

    protected function parseConditionName(array $condition)
    {
        $name = '';
        $conditionNameMap = array(
            'CondIBXmlID' => 'XML_ID',
            'CondIBSection' => 'SECTION_ID',
            'CondIBDateActiveFrom' => 'DATE_ACTIVE_FROM',
            'CondIBDateActiveTo' => 'DATE_ACTIVE_TO',
            'CondIBSort' => 'SORT',
            'CondIBDateCreate' => 'DATE_CREATE',
            'CondIBCreatedBy' => 'CREATED_BY',
            'CondIBTimestampX' => 'TIMESTAMP_X',
            'CondIBModifiedBy' => 'MODIFIED_BY',
            'CondIBTags' => 'TAGS',
            'CondCatQuantity' => 'QUANTITY',
            'CondCatWeight' => 'WEIGHT'
        );

        if (isset($conditionNameMap[$condition['CLASS_ID']]))
        {
            $name = $conditionNameMap[$condition['CLASS_ID']];
        }
        elseif (mb_strpos($condition['CLASS_ID'], 'CondIBProp') !== false)
        {
            $name = $condition['CLASS_ID'];
        }
        elseif (mb_strpos($condition['CLASS_ID'], 'FilterProperty') !== false)
        {
            $name = $condition['CLASS_ID'];
        }
        elseif (mb_strpos($condition['CLASS_ID'], 'FilterPrice') !== false)
        {
            $name = $condition['CLASS_ID'];
        }

        return $name;
    }

    protected function parseConditionOperator($condition)
    {
        $operator = '';

        switch ($condition['DATA']['logic'])
        {
            case 'Equal':
                $operator = '=';
                break;
            case 'Not':
                $operator = '!';
                break;
            case 'Contain':
                $operator = '%';
                break;
            case 'NotCont':
                $operator = '!%';
                break;
            case 'Great':
                $operator = '>';
                break;
            case 'Less':
                $operator = '<';
                break;
            case 'EqGr':
                $operator = '>=';
                break;
            case 'EqLs':
                $operator = '<=';
                break;
        }

        return $operator;
    }

    protected function parseConditionValue($condition, $name)
    {
        $value = $condition['DATA']['value'];

        switch ($name)
        {
            case 'DATE_ACTIVE_FROM':
            case 'DATE_ACTIVE_TO':
            case 'DATE_CREATE':
            case 'TIMESTAMP_X':
                $value = \ConvertTimeStamp($value, 'FULL');
                break;
        }

        return $value;
    }

    protected function parsePropertyOfType(
        &$queryItem,
        $property,
        $value,
        $operator
    ){
        switch ($property['PROPERTY_TYPE']) {
            case 'N':
                foreach ($value as $index => $val) {
                    if($index > 0) {
                        $operator = ($operator == '>=' || $operator == '>' ? '<=' : '>=');
                    }
                    $queryItem[$operator . 'PROPERTY_' . $property['CODE']] = $val;
                }
                break;
            case 'S':
                $queryItem[$operator . 'PROPERTY_' . $property['CODE']] = $value;
                break;
            case 'E':
                $queryItem[$operator . 'PROPERTY_' . $property['CODE'] . '.NAME'] = $value;
                break;
            default:
                $queryItem[$operator . 'PROPERTY_' . $property['CODE'] . '_VALUE'] = $value;
        }
    }

    protected function parsePropertyCondition(array &$result, array $condition, $params, $iblockId)
    {
        if (!empty($result)) {
            $subFilter = array();

            foreach ($result as $name => $value) {
                if (
                    ($ind = mb_strpos($name, 'CondIBProp')) !== false ||
                    mb_strpos($name, 'FilterProperty') !== false ||
                    mb_strpos($name, 'FilterPrice') !== false
                ) {
                    list($prefix, $iblock, $propertyId) = explode(':', $name);
                    $operator = $ind > 0 ? mb_substr($prefix, 0, $ind) : '';

                    if(empty($operator)) {
                        $operator = '=';
                    }

                    if(mb_stripos($name, 'MIN') !== false) {
                        $operator = '>' . $operator;
                    } elseif(mb_stripos($name, 'MAX') !== false) {
                        $operator = '<' . $operator;
                    }

                    $catalogInfo = array();
                    if(class_exists('CCatalogSku')) {
                        $catalogInfo = \CCatalogSku::GetInfoByIBlock($iblock);
                    }

                    if (!empty($catalogInfo)) {
                        $value = preg_replace('/"|(&quot;)/', '"', $value);
                        $property = \CIBlockProperty::GetByID($propertyId, $iblock)->fetch();
                        if (
                            class_exists('CCatalogSku') &&
                            $catalogInfo['CATALOG_TYPE'] != \CCatalogSku::TYPE_CATALOG &&
                            $catalogInfo['IBLOCK_ID'] == $iblock
                        ) {
                            $this->parsePropertyOfType(
                                $subFilter,
                                $property,
                                $value,
                                $operator
                            );
                        }
                        else {
                            $this->parsePropertyOfType(
                                $result,
                                $property,
                                $value,
                                $operator
                            );
                        }
                    }
                    elseif (mb_strpos($name, 'FilterPrice') !== false) {
                        $priceCode = preg_replace('/.*FilterPrice/m', '', $name);
                        $price = PriceManager::getInstance();
                        if (!$iblockId) {
                            $result['PRICE_EXIST'] = true;
                        }
                        if ($price = $price->getData()[$priceCode]) {
                            if (is_array($value)) {
                                foreach ($value as $item) {
                                    if (isset($item['MAX']) || isset($item['MAXFILTER'])) {
                                        $operator = '<=';
                                    } elseif (isset($item['MIN']) || isset($item['MINFILTER'])) {
                                        $operator = '>=';
                                    }
                                    if ($iblockId) {
                                        $priceFilter[$operator . 'PRICE'] = $item['value'];
                                    } else {
                                        $result[$operator . 'catalog_PRICE_' . $price['ID']] = $item['value'];
                                    }
                                }
                            } else {
                                if ($iblockId) {
                                    $priceFilter[$operator . 'PRICE'] = $value;
                                } else {
                                    $result[$operator . 'catalog_PRICE_' . $price['ID']] = $value;
                                }
                            }
                        }
                    }

                    if ($priceFilter && $iblockId) {
                        if (class_exists('CCatalogSku')) {
                            $skuIblockInfo = \CCatalogSku::GetInfoByIBlock($iblockId);
                        }
                        $offerPropFilter = array(
                            'IBLOCK_ID' => $skuIblockInfo['IBLOCK_ID'],
                            'ACTIVE_DATE' => 'Y',
                            'ACTIVE' => 'Y'
                        );

                        if($params['AVAILABLE'] === 'Y') {
                            $offerPropFilter['AVAILABLE'] = 'Y';
                        }

                        if ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'Y') {
                            $offerPropFilter['HIDE_NOT_AVAILABLE'] = 'Y';
                        } elseif ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'L') {
                            $offerPropFilter[] = array(
                                'LOGIC' => 'OR',
                                'AVAILABLE' => 'Y',
                                'SUBSCRIBE' => 'Y'
                            );
                        }

                        $result['=ID'] = \CIBlockElement::SubQuery(
                            'PROPERTY_' . $skuIblockInfo['SKU_PROPERTY_ID'],
                            $offerPropFilter + $priceFilter
                        );

                        unset($priceFilter);
                    }

                    unset($result[$name]);
                }
                elseif (!empty($result[$name]) && is_array($result[$name])) {
                    $this->parsePropertyCondition($result[$name], $condition, $params, $iblockId);
                }
            }

            if (!empty($subFilter) && !empty($catalogInfo)) {
                $offerPropFilter = array(
                    'IBLOCK_ID' => $catalogInfo['IBLOCK_ID'],
                    'ACTIVE_DATE' => 'Y',
                    'ACTIVE' => 'Y'
                );

                if($params['AVAILABLE'] === 'Y') {
                    $offerPropFilter['AVAILABLE'] = 'Y';
                }

                if ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'Y') {
                    $offerPropFilter['HIDE_NOT_AVAILABLE'] = 'Y';
                } elseif ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'L') {
                    $offerPropFilter[] = array(
                        'LOGIC' => 'OR',
                        'AVAILABLE' => 'Y',
                        'SUBSCRIBE' => 'Y'
                    );
                }

                if (count($subFilter) > 1) {
                    $subFilter['LOGIC'] = $condition['DATA']['All'];
                    $subFilter = array($subFilter);
                }

                if ($result['=ID']) {
                    $filter = array_merge($offerPropFilter, $subFilter, $result["=ID"]->arFilter);
                } else {
                    $filter = array_merge($offerPropFilter, $subFilter);
                }

                $result['=ID'] = \CIBlockElement::SubQuery(
                    'PROPERTY_'.$catalogInfo['SKU_PROPERTY_ID'],
                    $filter
                );
            }
        }
    }
}