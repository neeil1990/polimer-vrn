<?php
namespace Sotbit\Seometa\Helper;

/**
 * a little class for help open conditions of group
 *
 * @author Evgenij Sidorenko < e.sidorenko@sotbit.ru >
 *
 */

class Condition
{
    /**
     * var for help in the future, check arrays and diferrent levels of group condition
     * @param int
     */
    private $level = 0;
    
    /**
     * array which save entered values of peroperty (like singelton)
     */
    private $allEnterPropertyValues = array();
    
    /**
     * function with open group of conditions, it's a little similar recursion
     * @param array $Condition - conditions of tree from condition page
     *
     */
    public function openGroups(&$Condition)
    {
        $this->level++;
        $newConditions = array();
        $oldConditions = array();
        
        if(isset($Condition['DATA']['All']) && $Condition['DATA']['All'] == 'AND')
            $Condition['CHILDREN'] = array($Condition['CHILDREN']);
        elseif(isset($Condition['DATA']['All']) && $Condition['DATA']['All'] == 'OR')
            foreach($Condition['CHILDREN'] as &$Child)
                $Child = array($Child);
        else
            throw new \Exception('Bad condition');

        foreach($Condition['CHILDREN'] as $ids => $arCondition)
            foreach($arCondition as $id => $arChildCondition)
            {
                if($this->hasGroupChildren($arChildCondition)) {
                    $Condition['CHILDREN'][$ids][$id] = $this->openGroups($arChildCondition);
                     array_push($newConditions, $Condition['CHILDREN'][$ids][$id]);
                }
                else {
                    if(!$this->hasGroupChild($arChildCondition))
                        array_push($oldConditions, $arChildCondition);
                    else
                        array_push($newConditions, $arChildCondition);
                }
            }

        if(!empty($newConditions)) {
            $Condition = $this->assembleConditions($Condition, $oldConditions, $newConditions);

            if (!$this->needWrapperChildren($Condition['CHILDREN']) && count($Condition['CHILDREN']) > 1)
                $Condition['DATA']['All'] = 'OR';
            elseif($this->needWrapperChildren($Condition['CHILDREN']))
            {
                $Condition['CHILDREN'] = $this->wrapperByKey($Condition['CHILDREN'], $Condition['DATA']['All']);
            }
        }

        $this->level--;
        return $Condition;
    }
    
    /**
     * collect and return conditions with key [DATA][All]  = $key (OR|AND)
     * @param array $newConditions - list of conditions there are children
     * @param string $key
     *
     * return array - list of childrens, there are collected by $key
     */
    private function getByKeyAnd($newConditions, $key)
    {
        $result = array();
        $mainItems = array();

        foreach($newConditions as $Condition) {
            if (isset($Condition['DATA']['All']) && $Condition['DATA']['All'] == $key){
                if($key == "AND"){
//                    $result = array_merge($result, $Condition['CHILDREN']);
                    array_push($result, $Condition['CHILDREN']);
                }else {
                    if(empty($mainItems)){
                        $mainItems = $Condition['CHILDREN'];
                    }else{
                        $tmpAr = array();
                        foreach ($mainItems as $k => $mainItem){
                            foreach ($Condition['CHILDREN'] as $key => $childItem) {
                                $tmpRes = array();
                                array_push($tmpRes, $mainItem, $childItem);
                                array_push($tmpAr, $tmpRes);
                            }
                        }
                        $result = $tmpAr;
                    }
                }
            }
        }
        if(empty($result) && !empty($mainItems))
            $result = $mainItems;
        return $result;
    }

    private function getByKeyOr($newConditions, $key)
    {
        $result = array();
        $mainItems = array();

        foreach($newConditions as $Condition) {
            if (isset($Condition['DATA']['All']) && $Condition['DATA']['All'] == $key){
                if($key == "AND"){
                    //$result = array_merge($result, $Condition['CHILDREN']);
                    array_push($result, $Condition['CHILDREN']);
                }else{
                    foreach ($Condition['CHILDREN'] as $item)
                        array_push($result, $item);
                }
            }
        }
        return $result;
    }
    
    /**
     * assemble conditions by condition where there aren't children with group of condition and condition there aren't children
     * @param array $Condition - current Condition
     * @param array $oldConditions - condition there aren't children
     * @param array $newConditions - condition there are children, but there aren't group of condition at children (afret function openGroup level up)
     *
     * @return array $Condition with new Children by assemble condition with group and without
     */
    private function assembleConditions(array $Condition, array $oldConditions, array $newConditions)
    {
        $result = array();

        if(isset($Condition['DATA']['All']) && $Condition['DATA']['All'] == 'AND')
        {
            $andNew = $this->getByKeyAnd($newConditions, 'AND');

            if($this->needWrapperChildren($andNew))
                $oldConditions = array_merge($oldConditions, $andNew);
            else
                foreach($andNew as $andNs)
                    foreach($andNs as $andN)
                        $oldConditions = array_merge($oldConditions, array($andN));

            $newConditions = $this->getByKeyAnd($newConditions, 'OR');

            foreach($newConditions as $arCond) {
                if (isset($arCond['CLASS_ID']))
                    $arCond = array($arCond);
                
                if(empty($arCond))
                    continue;
                
                array_push($result, array_merge($oldConditions, $arCond));
            }
            if(empty($newConditions)) {
                // if($this->needWrapperChildren($oldConditions))
                //     $oldConditions = array($oldConditions);
                
                $result = $oldConditions;
            }
        }
        elseif(isset($Condition['DATA']['All']) && $Condition['DATA']['All'] == 'OR')
        {
            foreach($oldConditions as $k => &$arCond)
                if (isset($arCond['CLASS_ID']))
                    $arCond = array($arCond);
            
            $orNew = $this->getByKeyOr($newConditions, 'OR');
            if($this->needWrapperChildren($orNew))
                foreach ($orNew as &$or){
                    $or = array($or);
                }

            $result = array_merge($oldConditions, $orNew);

            $newConditions = $this->getByKeyOr($newConditions, 'AND');

            if(!empty($newConditions))
            {
                if($this->needWrapperChildren($newConditions))
                    array_push($result, $newConditions);
                else
                    foreach($newConditions as $newC)
                        array_push($result, $newC);
            }
        }
        /*if(empty($newConditions)) {
            $result = $oldConditions;
          }*/
        $Condition['CHILDREN'] = $result;
        return $Condition;
    }
    
    private function needWrapperChildren($Children)
    {
        $current = current($Children);
        return isset($current['CLASS_ID']);
    }
    
    private function wrapperByKey($Children , $key)
    {
        if($key == 'AND')
            $Children = array($Children);
        elseif($key == 'OR')
            foreach($Children as &$Child){
                if(isset($Child["CLASS_ID"]))
                    $Child = array($Child);
                else
                    $Child = $Child;
            }
        
        return $Children;
    }
    
    /**
     * check exist group of condition at children of condition if there are
     * @param array $Condition
     *
     * @return boolean
     */
    public function hasGroupChildren($Condition)
    {
        $result = array();
        if(isset($Condition['CHILDREN'])) {
            foreach ($Condition['CHILDREN'] as $arConditions) {
                
                if (isset($arConditions['CLASS_ID'])) {
                    array_push($result, intval($this->hasGroupChild($arConditions)));
                }elseif (!isset($arConditions['CLASS_ID']) && !isset($arConditions['DATA'])) {
                    foreach ($arConditions['CHILDREN'] as $Child) {
                        if ($this->hasGroupChild($Child))
                            return true;
                    }
                }
            }
        }
        elseif(!isset($Condition['CLASS_ID']))
        {
            foreach($Condition as $arCondition)
                array_push($result, intval($this->hasGroupChildren($arCondition)));
        }
        return in_array(1, $result);
    }
    
    /**
     * check exist group of conditions in condiion
     * @param array $Condition
     *
     * @return boolean
     */
    public function hasGroupChild(array $Condition)
    {
        return isset($Condition['CLASS_ID']) && $Condition['CLASS_ID'] == 'CondGroup';
    }
    
    /////////////////////////////////////////////////////////////////////////////
    /**
     * With desires put second pard of class to deferrent class
     */
    
    /**
     * Check the exist '...' in conditions
     * @param array $Condition
     *
     * @return boolean
     */
    public function existEmptyValue($Condition)
    {
        foreach($Condition['CHILDREN'] as $ids => $arChildren)
            foreach($arChildren as $id => $Child)
                if(isset($Child['DATA']['value']) && empty($Child['DATA']['value']))
                    return true;
        
        return false;
    }
    
    public function FillEmptyValues($Condition, $ConditionSections)
    {
        $ind = 0;
        
        while($this->existEmptyValue($Condition) && $ind++ < 100)
        {
            foreach($Condition['CHILDREN'] as $ids => $arCondition)
            {
                //var_dump($this->partHasEmptyValue($arCondition));
                if($this->partHasEmptyValue($arCondition))
                {
                    $resertCondition = $arCondition;
                    unset($Condition['CHILDREN'][$ids]);
                    $index = $this->indexOfEmptyValue($arCondition);
                    $resertCond = $resertCondition[$index];
                    $conditions = $this->CollectValuesByCondition($resertCondition[$index], $ConditionSections);
                    
                    unset($resertCondition[$index]);
                    
                    foreach($conditions as $condition)
                    {
                        $tempCond = $resertCond;
                        $tempCond['DATA']['value'] = $condition;
                        
                        if(in_array($tempCond, $resertCondition))
                            continue;
                        
                        $tmp = array_merge($resertCondition, array($tempCond));
                        usort($tmp, array($this, 'cmpCondition'));
                        
                        if(in_array($tmp, $Condition['CHILDREN']))
                            continue;
                        
                        array_push($Condition['CHILDREN'], $tmp);
                    }
                }
            }
        }
        
        // show result after fill empty fields, for checking
        //        echo '<pre>';
        //        foreach($Condition['CHILDREN'] as $children)
        //        {
        //            foreach($children as $child)
        //                echo $child['DATA']['value'].'<br>';
        //
        //            echo '<br>';
        //        }
        //        exit();
        
        return $Condition;
    }
    
    /**
     * function have been used php function usorf for sort condition
     * @param array $condA
     * @param array $condB
     *
     * @return int (-1 0 1)
     */
    private function cmpCondition($condA, $condB)
    {
        return strcmp($condA['DATA']['value'],$condB['DATA']['value']);
    }
    
    /**
     * function return index condition with empty values
     */
    private function indexOfEmptyValue($arConditions)
    {
        foreach($arConditions as $index => $Condition)
            if($this->condHasEmptyValue($Condition))
                return $index;
        
        return -1;
    }
    
    /**
     * Check empty value in conditions of array
     */
    private function partHasEmptyValue($arConditions)
    {
        foreach($arConditions as $Condition)
            if($this->condHasEmptyValue($Condition))
                return true;
        
        return false;
    }
    
    /**
     * check exist empty valye in condition data
     */
    private function condHasEmptyValue(array $Condition)
    {
        return isset($Condition['DATA']['value']) && empty($Condition['DATA']['value']);
    }
    
    /**
     * find element in condition with empty value and add new elements with all posible values
     * @param array $Condition
     * @return array
     * */
    protected function CollectValuesByCondition($Condition, $ConditionSections)
    {
        if(isset($Condition['DATA']['value']) && empty($Condition['DATA']['value']))
        {
            $arCond = explode(':', $Condition['CLASS_ID']);
            $IdIblock = $arCond[1];
            $IdProperty = $arCond[2];
//            $property = \CIBlockProperty::GetByID($IdProperty)->fetch();
            $property = \Sotbit\Seometa\IblockProperty::getIblockProp($IdProperty);
            switch ($property['PROPERTY_TYPE'])
            {
                case 'L':
                    $prop_values = self::AllPropertiesByList($IdIblock, $ConditionSections, $IdProperty, $property);
                    break;
                default:
                    //$prop_values = self::GetValuesIfEmptyValue($IdIblock, $IdProperty, $ConditionSections);
    
                    if (!class_exists('CCatalogSKU') || is_array(\CCatalogSKU::GetInfoByProductIBlock($property['IBLOCK_ID']))
                        || (\CCatalogSKU::GetInfoByProductIBlock( $property['IBLOCK_ID'] ) == false && \CCatalogSKU::GetInfoByOfferIBlock( $property['IBLOCK_ID'] ) == false))
                    {
                        $prop_values = self::AllEnteredProperties($IdIblock, $ConditionSections, $IdProperty, $property);
                    }
                    else
                    {
                        $prop_values = self::GetValuesIfEmptyValue($IdIblock, $IdProperty, $ConditionSections);
                    }
            }
        }

        return $prop_values;
    }
    
    /**
     * collect all property values from propetry type list in infoblock
     * @param int $IdIblock - ID of block where need search values of property
     * @param array $ConditionSections - array by ID of categories where need search values of property
     * @param int $IdProperty - ID of property
     * @param array $property
     * @return array $prop_values  - array with values of property
     * */
    protected function AllPropertiesByList($IdIblock, $ConditionSections, $IdProperty, $property)
    {
        if(isset($this->allEnterPropertyValues[$IdProperty]))
            return $this->allEnterPropertyValues[$IdProperty];
        
        // $Sections = $this->collectSections($ConditionSections);
        $code = $property['CODE'];
        
        $arFilter = array(
            "IBLOCK_ID" => $IdIblock,
            "PROPERTY_ID" => $IdProperty,
            "!PROPERTY_$code" => false
        );
        $filterBySection = true;
        if(!class_exists('CCatalogSKU') || is_array(\CCatalogSKU::GetInfoByOfferIBlock($IdIblock)))
            $filterBySection = false;
        
        $prop_values = array();
        
        foreach($ConditionSections as $Section)
        {
            if($filterBySection)
                $arFilter = array_merge($arFilter, ["SECTION_ID" => $Section, "INCLUDE_SUBSECTIONS" => 'Y']);

            $properties = \CIBlockElement::GetList(
                array(
                    "SORT" => "ASC",
                    "IBLOCK_ID" => $IdIblock
                ),
                $arFilter,
                false,
                false,
                array("ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_ID", "XML_ID", "PROPERTY_$code")
            );
            
//            $prop_values = array_merge($prop_values, $this->getPropertyValueByKey($properties, $code, 'VALUE_ENUM_ID'));//VALUE_ENUM_ID
            $prop_values = array_merge($prop_values, $this->getPropertyValueByKey($properties, $code, 'ENUM_ID'));//VALUE_ENUM_ID
        }
        
        unset($code, $Sections);
        
        $result = array_unique($prop_values);
        $this->allEnterPropertyValues[$IdProperty] = $result;
        
        return array_unique($prop_values);
    }
    
    /**
     * collect all property values without repeat in infoblock
     * */
    protected function AllEnteredProperties($IdIblock, $ConditionSections, $IdProperty, $property)
    {
        if(isset($this->allEnterPropertyValues[$IdProperty]))
            return $this->allEnterPropertyValues[$IdProperty];
        
        $code = $property['CODE'];
        // $Sections = $this->collectSections($ConditionSections);
        $prop_values = array();
        
        foreach($ConditionSections as $Section)
        {
            $properties = \CIBlockElement::GetList(Array(
                "SORT"=>"ASC",
            ),
                array(
                    "IBLOCK_ID" => $IdIblock,
                    "PROPERTY_ID"=>$IdProperty,
                    "!PROPERTY_$code" => false,
                    "SECTION_ID" => $Section,
                    'INCLUDE_SUBSECTIONS' => 'Y'
                ),
                false,
                false,
                Array("ID", "IBLOCK_ID", "NAME", "CODE")
            
            );
            
            $prop_values = array_merge($prop_values, $this->getPropertyValueByKey($properties, $code, 'VALUE'));
        }
        
        $result = array_unique($prop_values);
        
        $this->allEnterPropertyValues[$IdProperty] = $result;
        
        return $result;
    }
    
    public function GetValuesIfEmptyValue($IdIblock, $IdProperty, $ConditionSections)
    {
        // All products - need for empty values
        $return = array();
        if(class_exists('CCatalogSku')) {
            $CatalogResult = \CCatalogSKU::GetInfoByProductIBlock($IdIblock);
            if (!is_array($CatalogResult)) {
                $OffersResult = \CCatalogSKU::GetInfoByOfferIBlock($IdIblock);
            }
        }
        
        if ($IdIblock == $CatalogResult['PRODUCT_IBLOCK_ID']) // If property of product
        {
            $res = \CIBlockElement::GetList( Array(), Array(
                "IBLOCK_ID" => $IdIblock,
                "ACTIVE" => "Y",
                "SECTION_ID" => $ConditionSections,
                "INCLUDE_SUBSECTIONS" => "Y"
            ), false, false, array(
                'PROPERTY_' . $IdProperty
            ) );
            while ( $ob = $res->GetNextElement() )
            {
                $arFields = $ob->GetFields();
                $return[] = $arFields;
            }
        }
        elseif ($IdIblock == $OffersResult['IBLOCK_ID']) // If property of offer
        {
            $res = \CIBlockElement::GetList( Array(), Array(
                "IBLOCK_ID" => $IdIblock,
                "ACTIVE" => "Y"
            ), false, false, array(
                'ID',
                'PROPERTY_' . $IdProperty
            ) );
            $Offers = array();
            $OffersIds = array();
            while ( $ob = $res->GetNextElement() )
            {
                $arFields = $ob->GetFields();
                if (!in_array( $arFields['PROPERTY_' . $IdProperty . '_VALUE'], $Offers ) && !is_null( $arFields['PROPERTY_' . $IdProperty . '_VALUE'] ))
                {
                    $OffersIds[] = $arFields['ID'];
                    $Offers[$arFields['ID']]['VALUE'] = $arFields['PROPERTY_' . $IdProperty . '_VALUE'];
                }
            }

            if(class_exists('CCatalogSku')) {
                // Find products for offers
                $ProductsOffers = \CCatalogSKU::getProductList($OffersIds,
                    $IdIblock);
                $Products = array();
                foreach ($ProductsOffers as $OfferKey => $Prod) {
                    $Offers[$OfferKey]['PROD'] = $Prod['ID'];
                    if (!in_array($Prod['ID'],
                            $Products) && !is_null($Prod['ID'])) {
                        $Products[] = $Prod['ID'];
                    }
                }
            }
            // Find in section
            $NeedPropducts = array();
            $res = \CIBlockElement::GetList( Array(), Array(
                "ID" => $Products,
                "IBLOCK_ID" => $OffersResult['PRODUCT_IBLOCK_ID'],
                "ACTIVE" => "Y",
                "SECTION_ID" => $ConditionSections,
                "INCLUDE_SUBSECTIONS" => "Y"
            ), false, false, array(
                'ID'
            ) );
            while ( $ob = $res->GetNextElement() )
            {
                $arFields = $ob->GetFields();
                if (!in_array( $arFields['ID'], $NeedPropducts ) && !is_null( $arFields['ID'] ))
                    $NeedPropducts[] = $arFields['ID'];
            }
            foreach ( $Offers as $IdOffer => $Val )
            {
                if (!in_array( $Val['PROD'], $NeedPropducts ))
                    unset( $Offers[$IdProd] );
                elseif (!in_array( $Val['VALUE'], $return ))
                    $return[] = $Val['VALUE'];
            }
        }
        return $return;
    }
    
    /**
     * collect all sub section for sections
     * @param array $ConditionSections
     *
     * @return array sections
     */
    private function collectSections($ConditionSections)
    {
        $Sections = array();
        
        if(!is_array($ConditionSections))
            $ConditionSections = array($ConditionSections);


        foreach($ConditionSections as $sectionId)
        {
            $rsParentSection = \CIBlockSection::GetList(array('SORT' => 'ASC'), array('ID' => $sectionId), false, array('ID', 'NAME', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'));
            // $rsParentSection = \CIBlockSection::GetByID($sectionId);
            if ($arParentSection = $rsParentSection->GetNext())
            {
                $Sections[$arParentSection['ID']] = array('ID' => $arParentSection['ID']);
                
                $arFilter = array('IBLOCK_ID' => $arParentSection['IBLOCK_ID'],'>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],'<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],'>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']); // ??????? ???????? ??? ????? ??????????
                $rsSect = \CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter, false, array('ID'));
                while ($arSect = $rsSect->GetNext())
                {
                    $Sections[$arSect['ID']] = $arSect;
                }
            }
        }
        
        return array_keys($Sections);
    }
    
    /**
     * get property from CIBlockResult (CIBlockElement::GetList) by property code and collect field of property by key
     * @param CIBlockResult $properties
     * @param string $code - property code
     * @param $key - field of proeprty which need collect
     *
     * @return array
     */
    private function getPropertyValueByKey(\CIBlockResult $properties, $code, $key = 'VALUE')
    {
        $prop_values = array();
        
        while($prop_fields = $properties->GetNextElement())
        {
            /** раскомметировал для корректной работы условий с типом свойств: привязка к элементам  */
            $props = $prop_fields->GetProperties(false, array('CODE' => $code));
            if (empty($props) || !isset($props[$code]))
                continue;

            if (!empty($props[$code][$key]) && !in_array($props[$code][$key], $prop_values))
            {
                if(is_array($props[$code][$key]))
                {
                    foreach ($props[$code][$key] as $val)
                        if (!in_array($val, $prop_values))
                        {
                            $prop_values[] = $val;
                        }
                }
                else
                {
                    $prop_values[] = $props[$code][$key];
                }
            }
            /* END OF раскомметировал для корректной работы условий с типом свойств: привязка к элементам  */
            if(isset($prop_fields->fields['PROPERTY_'.$code.'_'.$key]) && !empty($prop_fields->fields['PROPERTY_'.$code.'_'.$key]) && !in_array($prop_fields->fields['PROPERTY_'.$code.'_'.$key], $prop_values))
                $prop_values[] = $prop_fields->fields['PROPERTY_'.$code.'_'.$key];
        }
        
        return $prop_values;
    }

    /**
     * Return parsed conditions array.
     *
     * @param $condition
     * @param $params
     * @return array
     */
    public function parseCondition($condition, $params)
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
                        $childResult = $this->parseCondition($child, $params);

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
                        $this->parsePropertyCondition($result, $condition, $params);

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

        return $name;
    }

    protected function parseConditionOperator($condition)
    {
        $operator = '';

        switch ($condition['DATA']['logic'])
        {
            case 'Equal':
                $operator = '';
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

    protected function parsePropertyCondition(array &$result, array $condition, $params)
    {
        if (!empty($result))
        {
            $subFilter = array();

            foreach ($result as $name => $value)
            {
                if (!empty($result[$name]) && is_array($result[$name]))
                {
                    $this->parsePropertyCondition($result[$name], $condition, $params);
                }
                else
                {
                    if (($ind = mb_strpos($name, 'CondIBProp')) !== false)
                    {
                        list($prefix, $iblock, $propertyId) = explode(':', $name);
                        $operator = $ind > 0 ? mb_substr($prefix, 0, $ind) : '';

                        if(class_exists('CCatalogSku')) {
                            $catalogInfo = \CCatalogSku::GetInfoByIBlock($iblock);
                            if (!empty($catalogInfo)) {
                                if (
                                    $catalogInfo['CATALOG_TYPE'] != \CCatalogSku::TYPE_CATALOG
                                    && $catalogInfo['IBLOCK_ID'] == $iblock
                                ) {
                                    $subFilter[$operator . 'PROPERTY_' . $propertyId] = $value;
                                } else {
                                    $result[$operator . 'PROPERTY_' . $propertyId] = $value;
                                }
                            }
                        }
                        unset($result[$name]);
                    }
                }
            }

            if (!empty($subFilter) && !empty($catalogInfo))
            {
                $offerPropFilter = array(
                    'IBLOCK_ID' => $catalogInfo['IBLOCK_ID'],
                    'ACTIVE_DATE' => 'Y',
                    'ACTIVE' => 'Y'
                );

                if ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'Y')
                {
                    $offerPropFilter['HIDE_NOT_AVAILABLE'] = 'Y';
                }
                elseif ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'L')
                {
                    $offerPropFilter[] = array(
                        'LOGIC' => 'OR',
                        'AVAILABLE' => 'Y',
                        'SUBSCRIBE' => 'Y'
                    );
                }

                if (count($subFilter) > 1)
                {
                    $subFilter['LOGIC'] = $condition['DATA']['All'];
                    $subFilter = array($subFilter);
                }

                $result['=ID'] = \CIBlockElement::SubQuery(
                    'PROPERTY_'.$catalogInfo['SKU_PROPERTY_ID'],
                    $offerPropFilter + $subFilter
                );
            }
        }
    }
}