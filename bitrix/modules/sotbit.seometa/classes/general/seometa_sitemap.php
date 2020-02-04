<?
if (!defined( "B_PROLOG_INCLUDED" ) || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

class CSeoMetaSitemap extends CCSeoMeta
{
    public static $isGenerateChpu = false;
    private static $AllPropsForSort = array();
    private static $AllPropsForSortByIblock = array();

    protected static function cmpCondition($a, $b)
    {
        if($a[0]['CLASS_ID'] != 'CondGroup')
            return -1;

        if($a[0]['CLASS_ID'] == 'CondGroup' && $a[0]['DATA']['All'] == 'OR')
            return 1;

        if($a[0]['CLASS_ID'] == 'CondGroup' && $a[0]['DATA']['All'] == 'AND' && $b[0]['CLASS_ID'] != 'CondGroup')
            return 1;

        return 0;
    }

    /**
     * find element in condition with empty value and add new elements with all possible values
     * @param array $Condition
     * @return array
     * */
    protected function PrepareCondition($Condition, $ConditionSections)
    {
        foreach($Condition['CHILDREN'] as $id => $child)
        {
            if(!isset($child['CLASS_ID']) && $child['CLASS_ID'] == null)
                $child = current($child);

            if(isset($child['DATA']['value']) && empty($child['DATA']['value']))
            {
                if($child['CLASS_ID'] == null)
                    $child = current($child);

                $arCond = explode( ':', $child['CLASS_ID'] );
                $IdIblock = $arCond[1];
                $IdProperty = $arCond[2];
                $property = CIBlockProperty::GetByID($IdProperty)->fetch();
                switch($property['PROPERTY_TYPE'])
                {
                    case 'L':
                        $prop_values = self::AllPropertiesByList($IdIblock, $ConditionSections, $IdProperty, $property);
                        break;
                    default:
                        $prop_values = self::AllEnteredPRoperties($IdIblock, $ConditionSections, $IdProperty, $property);

                    /*if(is_array(CCatalogSKU::GetInfoByProductIBlock($property['IBLOCK_ID']))) {
                        $prop_values = self::AllEnteredPRoperties($IdIblock, $ConditionSections, $IdProperty, $property);
                    } else {
                        $prop_values = self::GetValuesIfEmptyValue($IdIblock, $IdProperty, $ConditionSections);
                    }*/
                }

                $el = $Condition['CHILDREN'][$id];

                if ($el['CLASS_ID'] == null) {
                    $el = current($el);
                }

                unset($Condition['CHILDREN'][$id]);

                foreach($prop_values as $prop)
                {
                    $el['DATA']['value'] = $prop;

                    if(!self::conditionExist(array($el), $Condition['CHILDREN']))
                        $Condition['CHILDREN'][] = array($el);

                }
            }
        }

        return $Condition;
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
        $rsParentSection = CIBlockSection::GetByID(current($ConditionSections));
        if ($arParentSection = $rsParentSection->GetNext())
        {
            $Sections[] = $arParentSection;

            $arFilter = array('IBLOCK_ID' => $arParentSection['IBLOCK_ID'],'>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],'<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],'>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']); // ������� �������� ��� ����� ����������
            $rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter, false, array('ID'));
            while ($arSect = $rsSect->GetNext())
            {
                $Sections[] = $arSect;
            }
        }

        $code = strtoupper($property['CODE']);

        $prop_values = array();
        foreach($Sections as $Section)
        {
            $properties = CIBlockElement::GetList(Array(
                "SORT"=>"ASC",
                "IBLOCK_ID" => $IdIblock,
                "PROPERTY_CODE" => $code,
                //"IBLOCK_SECTION_ID" => 8,
            ),
                array(
                    "LOGIC" => "AND",
                    array( "IBLOCK_ID" => $IdIblock),
                    array("PROPERTY_CODE"=>$code),
                    array("!PROPERTY_$code"=>false),
                    array("SECTION_ID" => $Section['ID'])
                ),
                false,
                false,
                Array("ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_ID", "XML_ID", "PROPERTY_$code")

            );


            while ($prop_fields = $properties->GetNext())
            {
                if(!empty($prop_fields["PROPERTY_$code"."_ENUM_ID"]) && !in_array($prop_fields["PROPERTY_$code"."_ENUM_ID"], $prop_values))
                    $prop_values[] = $prop_fields["PROPERTY_$code"."_ENUM_ID"];
            }

        }
        unset($code);
        return $prop_values;
    }

    /**
     * collect all property values without repeat in infoblock
     * */
    protected function AllEnteredProperties($IdIblock, $ConditionSections, $IdProperty, $property)
    {
        $code = strtoupper($property['CODE']);
        foreach($ConditionSections as $sectionId)
        {
            $rsParentSection = CIBlockSection::GetByID($sectionId);
            if ($arParentSection = $rsParentSection->GetNext())
            {
                $Sections[] = $arParentSection;

                $arFilter = array('IBLOCK_ID' => $arParentSection['IBLOCK_ID'],'>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],'<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],'>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']); // ������� �������� ��� ����� ����������
                $rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter, false, array('ID'));
                while ($arSect = $rsSect->GetNext())
                {
                    $Sections[$arSect['ID']] = $arSect;
                }
            }
        }

        $prop_values = array();
        foreach($Sections as $Section)
        {
            $properties = CIBlockElement::GetList(Array(
                "SORT"=>"ASC",
                "IBLOCK_ID" => $IdIblock,
                "PROPERTY_CODE" => $code,
                //"IBLOCK_SECTION_ID" => 8,
            ),
                array(
                    "LOGIC" => "AND",
                    array( "IBLOCK_ID" => $IdIblock),
                    array("PROPERTY_CODE"=>$code),
                    //  array(
                    //        'LOGIC' => 'OR',
                    //  array("!PROPERTY_$code"=>false),
                    //  array("!PROPERTY_".$code."_VALUE_ID"=>false),
                    //                    ),
                    array("!PROPERTY_$code"=>false),
                    array("SECTION_ID" => $Section['ID']),
                    //  array('INCLUDE_SUBSECTIONS' => 'Y')
                ),
                false,
                false,
                Array("ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_ID", "XML_ID", "PROPERTY_$code", "PROPERTY_".$code."_VALUE_ID")

            );
            while ($prop_fields = $properties->GetNext())
            {
                if(!empty($prop_fields["PROPERTY_".$code."_VALUE"]) && !in_array($prop_fields["PROPERTY_".$code."_VALUE"], $prop_values))
                    $prop_values[] = $prop_fields["PROPERTY_".$code."_VALUE"];
            }

        }
        return $prop_values;
    }

    /**
     * Parse all conditions and open condition group
     * */
    public function ParseArray($Condition, $ConditionSections)
    {
        $cond = new \Sotbit\Seometa\Helper\Condition();
        $Condition = $cond->openGroups($Condition);
//        echo '<pre>1';
//        print_r($Condition);
//        exit();
        if($cond->existEmptyValue($Condition))
            $Condition = $cond->FillEmptyValues($Condition, $ConditionSections);
        return $Condition;
    }

    /***/
    public function PrepareConditions($conditions, $ConditionSections)
    {
        $return = array();
        $i = 0;
        $PropVals = array();

        foreach ( $conditions['CHILDREN'] as $id => $condition )
        {
            if(strpos( $condition['CLASS_ID'], 'FilterPrice' ) !== false) // If filter price
            {
                $arCond = explode( 'FilterPrice', str_replace( 'CondIB', '', $condition['CLASS_ID'] ) );
                $PropVals['CHILDREN'][] = self::GetPropVal( $condition['DATA']['value'], 0, 0, $condition['DATA']['logic'], $ConditionSections, $arCond[0], $arCond[1] );
            }
            else if(strpos( $condition['CLASS_ID'], 'FilterProperty' ) !== false) // If filter property
            {
                $arCond = explode( 'FilterProperty', str_replace( 'CondIB', '', $condition['CLASS_ID'] ) );
                $arCondProp = explode( ':', $arCond[1] );

                $IdIblock = $arCondProp[1];
                $IdProperty = $arCondProp[2];

                $PropVals['CHILDREN'][] = self::GetPropVal( $condition['DATA']['value'], $IdProperty, $IdIblock, $condition['DATA']['logic'], $ConditionSections, $arCond[0], $arCond[1] );
            }
            //additory fields for column of price
            elseif(strpos( $condition['CLASS_ID'], 'Price' ) !== false)
            {
                $arCond = explode( 'Price', str_replace( 'CondIB', '', $condition['CLASS_ID'] ) );
                $PropVals['CHILDREN'][] = self::GetPropVal( $condition['DATA']['value'], false, false, $condition['DATA']['logic'], $ConditionSections, $arCond[0], $arCond[1] );
            }
            else
            {
                $arCond = explode( ':', $condition['CLASS_ID'] );
                $IdIblock = $arCond[1];
                $IdProperty = $arCond[2];
                $PropVals['CHILDREN'][] = self::GetPropVal( $condition['DATA']['value'], $IdProperty, $IdIblock, $condition['DATA']['logic'], $ConditionSections );
            }
        }
        $PropVals['DATA'] = $conditions['DATA'];
        return $PropVals;
    }

    /**
     * collect all posible variants from array
     * @param array $Conditions - array with condition
     * @param boolean $repeat - if true can repeat elements, default false
     * @return array
     * */
    public function allVariants($Conditions, $repeat = false)
    {
        return $Conditions;

        /*
        if(empty($Conditions))
            return array();

        $array = Array();
        $array[1] = $main_array = $Conditions;

        $size = sizeof($array[1]);

        $arr_name = 1;
        for ($count = 2; $count <= $size; $count++) {

            $this_size = sizeof($array[$arr_name]);
            $arr_name++;

            for ($i = 0; $i < $this_size; $i++) {
                $gg = $array[($arr_name-1)][$i];

                if($gg['CLASS_ID'] != null)
                    $gg = array($gg);


                for ($x = 0; $x < $size; $x++)
                {
                    if(!self::conditionExist($array[1][$x], $gg) && !self::sameArray(array_merge($gg, $array[1][$x]), $array) && !empty($array[1][$x]))
                       $array[$arr_name][] = array_merge($gg, $array[1][$x]);
                }
            }
            $main_array = array_merge($main_array, $array[$arr_name]);
            if ($arr_name > 2)
                unset($array[($arr_name-1)]);
        }
        return $main_array;
        */
    }

    /**
     * find the same elements in array
     * @param array $array whitch find
     * @param array $main_array where find
     * @return boolean true if found the same array, false is't
     * */
    protected function sameArray($array, $main_array)
    {
        $return = false;

        foreach ($main_array as $arr1)
            foreach($arr1 as $arr)
                if(count($arr) == count($array) && is_array($arr))
                {
                    $return = array();
                    foreach($array as $ar)
                        if(self::conditionExist(array($ar), $arr))
                            $return[] = true;

                    if(count($return) == count($array) && self::allTrue($return))
                        return true;
                }

        return false;
    }

    /**
     * if all elements of array is true
     * @return bollean
     * */
    protected function allTrue($return)
    {
        foreach($return as $re)
            if(!$re)
                return false;

        return true;
    }

    /**
     * check condition in array
     * */
    protected function conditionExist($condition, $conditions)
    {
        if(empty($conditions))
            return false;
        foreach($conditions as $cond)
        {
            if($cond['CLASS_ID'] == current($condition)['CLASS_ID']
                && $cond['DATA']['logic'] == current($condition)['DATA']['logic']
                && $cond['DATA']['value'] == current($condition)['DATA']['value'])
                return  true;
        }

        return false;
    }

    public function GetValuesIfEmptyValue($IdIblock, $IdProperty, $ConditionSections)
    {
        // All products - need for empty values
        $return = array();
        $CatalogResult = CCatalogSKU::GetInfoByProductIBlock( $IdIblock );

        if (!is_array( $CatalogResult ))
        {
            $OffersResult = CCatalogSKU::GetInfoByOfferIBlock( $IdIblock );
        }

        if ($IdIblock == $CatalogResult['PRODUCT_IBLOCK_ID']) // If property of product
        {
            if(!self::$isGenerateChpu)
            {
                $res = CIBlockElement::GetList( Array(), Array(
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
            else
            {
                $res = CIBlockProperty::GetPropertyEnum(
                    $IdProperty,
                    array(),
                    array(
                        'IBLOCK_ID'=>$IdIblock,
                    )
                );
                while ( $arFields = $res->fetch() )
                {
                    $return[] = $arFields;
                }
            }
        }
        elseif ($IdIblock == $OffersResult['IBLOCK_ID']) // If property of offer
        {
            $res = CIBlockElement::GetList( Array(), Array(
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
            // Find products for offers
            $ProductsOffers = CCatalogSKU::getProductList( $OffersIds, $IdIblock );
            $Products = array();
            foreach ( $ProductsOffers as $OfferKey => $Prod )
            {
                $Offers[$OfferKey]['PROD'] = $Prod['ID'];
                if (!in_array( $Prod['ID'], $Products ) && !is_null( $Prod['ID'] ))
                {
                    $Products[] = $Prod['ID'];
                }
            }
            // Find in section
            $NeedPropducts = array();
            $res = CIBlockElement::GetList( Array(), Array(
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
     * save all properties with filter type $FilterType to variable self::$AllPropsForSort
     * */
    public function SetListOfProps($FilterType)
    {
        $TmpAllProps = array();
        foreach(self::$AllPropsForSortByIblock as $IdIblock => $Props)
        {
            if ($IdIblock != "PRICES" && $IdIblock != "FILTER")
            {
                if (CModule::IncludeModule("catalog") && ($CatalogResult = CCatalogSKU::GetInfoByProductIBlock($IdIblock)) && is_array($CatalogResult))
                {
                    foreach($Props as $Prop)
                        $TmpAllProps['PRODUCT'][] = $Prop;
                }
                else
                {
                    foreach($Props as $Prop)
                        $TmpAllProps['OFFERS'][] = $Prop;
                }
            }
            else
                $TmpAllProps[$IdIblock] = $Props;
        }

        $arFilterPropIDs = array();
        if(isset($TmpAllProps['FILTER']) && is_array($TmpAllProps['FILTER']))
            $arFilterPropIDs = array_keys($TmpAllProps['FILTER']);
        foreach($arFilterPropIDs as &$item)
            $item = str_replace('filter', '', $item);

        self::$AllPropsForSort = array();

        if($FilterType == "BITRIX" || $FilterType == 'bitrix_chpu' || $FilterType == 'bitrix_not_chpu')
        {
            if(isset($TmpAllProps['PRICES']))
                self::$AllPropsForSort[]["ID"] = "PRICES";
        }

        if(isset($TmpAllProps['PRODUCT']))
            foreach($TmpAllProps['PRODUCT'] as $Prop)
            {
                if($FilterType == "BITRIX" || $FilterType == 'bitrix_chpu' || $FilterType == 'bitrix_not_chpu')
                {
                    if(in_array($Prop['ID'], $arFilterPropIDs))
                        $Prop['FILTER_TYPE'] = 'BITRIX';
                }
                if($FilterType == "MISSSHOP" || $FilterType == "misshop_chpu")
                {
                    if(in_array($Prop['ID'], $arFilterPropIDs))
                        $Prop['FILTER_TYPE'] = 'MISSSHOP';
                }
                if(!in_array($Prop, self::$AllPropsForSort))
                    self::$AllPropsForSort[] = $Prop;
            }

        if(isset($TmpAllProps['OFFERS']))
            foreach($TmpAllProps['OFFERS'] as $Prop)
            {
                if($FilterType == "BITRIX" || $FilterType == 'bitrix_chpu' || $FilterType == 'bitrix_not_chpu')
                {
                    if(in_array($Prop['ID'], $arFilterPropIDs))
                        $Prop['FILTER_TYPE'] = 'BITRIX';
                }
                if($FilterType == "MISSSHOP" || $FilterType == "misshop_chpu")
                {
                    if(in_array($Prop['ID'], $arFilterPropIDs))
                        $Prop['FILTER_TYPE'] = 'MISSSHOP';
                }
                if(!in_array($Prop, self::$AllPropsForSort))
                    self::$AllPropsForSort[] = $Prop;
            }

        if($FilterType == "MISSSHOP" || $FilterType == "misshop_chpu")
        {
            if(isset($TmpAllProps['PRICES']))
                self::$AllPropsForSort[]["ID"] = "PRICES";
        }

        unset($TmpAllProps);
    }

    public function SortPagesCodes($Pages)
    {
        $return = array();
        $arFiltersMS = array();
        $arPricesMS = array();
        $flagMS = false;

        $sortIdPropXML = [];
        $sortIdPropCODE = [];

        foreach($Pages['CHILDREN'] as $i => &$Children)
        {
            $sortChild = array();


            foreach(self::$AllPropsForSort as $Prop)
            {
                if(is_array($Children['CHILDREN']))
                    foreach($Children['CHILDREN'] as $j => &$Child)
                    {
                        if(isset($Child[$Prop['ID']]) || (isset($Child['FILTER'][$Prop['ID']]) && ($Prop['FILTER_TYPE'] == 'BITRIX' || $Prop['FILTER_TYPE'] == 'MISSSHOP')))
                        {
                            if(isset($Child[$Prop['ID']]))
                            {
                                $sortChild[][$Prop['ID']] = $Child[$Prop['ID']];

                                if (isset($Child[$Prop['ID']]["SORT_XML"])) {
                                    $sortIdPropXML[$i] = $Child[$Prop['ID']]["SORT_XML"];
                                }

                                if (isset($Child[$Prop['ID']]["SORT_CODE"])) {
                                    $sortIdPropCODE[$i] = $Child[$Prop['ID']]["SORT_CODE"];
                                }
                            }
                            if(isset($Child['FILTER'][$Prop['ID']]) && $Prop['FILTER_TYPE'] == 'BITRIX')
                            {
                                $sortChild[]['FILTER'] = $Child['FILTER'];
                            }
                            if(isset($Child['FILTER'][$Prop['ID']]) && $Prop['FILTER_TYPE'] == 'MISSSHOP')
                            {
                                $arFiltersMS[]['FILTER'] = $Child['FILTER'];
                                $flagMS = true;
                            }
                        }
                        elseif($Prop['ID'] == 'PRICES' && isset($Child['PRICE']))
                        {
                            if($flagMS)
                            {
                                $arPricesMS[]['PRICE'] = $Child['PRICE'];
                                continue;
                            }
                            $sortChild[]['PRICE'] = $Child['PRICE'];
                        }
                    }
            }

            // write to the end for MissShop filter
            foreach($arFiltersMS as $filter)
                $sortChild[] = $filter;
            foreach($arPricesMS as $price)
                $sortChild[] = $price;

            $Children['CHILDREN'] = $sortChild;
        }

        $index = 0;

        foreach($Pages['CHILDREN'] as $j => $Page)
        {
            foreach($Page['CHILDREN'] as $i => $Cond)
            {
                $keys = array_keys($Cond);
                $condId = $keys[0];
                $returnId = 0;
                $exist = false;

                $conditionKey = 0;

                if(is_array($return[$index]))
                {
                    foreach($return[$index] as $ind => $child)
                    {
                        if(isset($child[$condId]) && $condId != 'FILTER')
                        {
                            $returnId = $ind;
                            $exist = true;
                            break;
                        }
                        else if($condId == 'FILTER')
                        {
                            $keysC = array_keys($Cond['FILTER']);
                            $keyC = $keysC[0];

                            if(is_array($child[$condId]) && in_array($keyC, array_keys($child[$condId])))
                            {
                                $returnId = $ind;
                                $conditionKey = $keyC;
                                $exist = true;
                                break;
                            }
                        }
                    }
                }

                if ($condId != 'PRICE')
                {
                    if(!$exist)
                        $return[$index][] = $Cond;
                    else
                    {
                        if($condId == 'FILTER')
                        {
                            $return[$index][$returnId][$condId][$conditionKey]['TYPE'][] = $Cond[$condId][$conditionKey]['TYPE'][0];
                            $return[$index][$returnId][$condId][$conditionKey]['ID'][] = $Cond[$condId][$conditionKey]['ID'][0];
                            $return[$index][$returnId][$condId][$conditionKey]['VALUE'][] = $Cond[$condId][$conditionKey]['VALUE'][0];
                        }
                        else
                        {
                            $return[$index][$returnId][$condId]['MISSSHOP'][1][] = $Cond[$condId]['MISSSHOP'][1][0];
                            $return[$index][$returnId][$condId]['MISSSHOP'][0][] = $Cond[$condId]['MISSSHOP'][0][0];
                            $return[$index][$returnId][$condId]['BITRIX'][1][] = $Cond[$condId]['BITRIX'][1][0];
                            $return[$index][$returnId][$condId]['BITRIX'][0][] = $Cond[$condId]['BITRIX'][0][0];

                            if(!is_array($return[$index][$returnId][$condId]['VALUE']))
                                $return[$index][$returnId][$condId]['VALUE'] = array($return[$index][$returnId][$condId]['VALUE']);

                            $return[$index][$returnId][$condId]['VALUE'][] = $Cond[$condId]['VALUE'];

                            if(!empty($return[$index][$returnId][$condId]['ORIGIN_VALUE']) && !is_array($return[$index][$returnId][$condId]['ORIGIN_VALUE']))
                                $return[$index][$returnId][$condId]['ORIGIN_VALUE'] = array($return[$index][$returnId][$condId]['ORIGIN_VALUE']);

                            $return[$index][$returnId][$condId]['ORIGIN_VALUE'][] = is_array($Cond[$condId]['ORIGIN_VALUE']) ? current($Cond[$condId]['ORIGIN_VALUE']) : $Cond[$condId]['ORIGIN_VALUE'];
                        }
                    }
                }
                else
                {
                    if(!$exist)
                        $return[$index][] = $Cond;
                    else
                    {
                        $key = array_keys($Cond['PRICE']);
                        $typeOfPrice = $key[0];

                        $return[$index][$returnId][$condId][$typeOfPrice]['TYPE'][] = $Cond[$condId][$typeOfPrice]['TYPE'][0];
                        $return[$index][$returnId][$condId][$typeOfPrice]['ID'][] = $Cond[$condId][$typeOfPrice]['ID'][0];
                        $return[$index][$returnId][$condId][$typeOfPrice]['VALUE'][] = $Cond[$condId][$typeOfPrice]['VALUE'][0];
                    }
                }
            }

            ///////////////////////////////
            /// Сортировка значения
            if (count($sortIdPropXML[$j]) > 0) {
                if(!empty($sortIdPropXML[$j]) && is_array(reset($sortIdPropXML[$j]))) {
                    usort(
                        $sortIdPropXML[$j], function ($val1, $val2)
                        {
                            return ($val1['sort'] - $val2['sort']);
                        }
                    );


                    $sortIdPropXML[$j] = array_map(
                        function ($data_sort)
                        {
                            return $data_sort['code'];
                        }, $sortIdPropXML[$j]
                    );
                } else {
                    $sortIdPropXML[$j] = !empty($sortIdPropXML[$j]['code']) ? array($sortIdPropXML[$j]['code']) : '';
                }

                $return[$index][$returnId][$condId]['BITRIX'][1] = $sortIdPropXML[$j];
            }

            if (count($sortIdPropCODE[$j]) > 0) {
                if(!empty($sortIdPropCODE[$j]) && is_array(reset($sortIdPropCODE[$j]))) {
                    usort(
                        $sortIdPropCODE[$j], function ($val1, $val2) {
                        return ($val1['sort'] - $val2['sort']);
                    }
                    );

                    $sortIdPropCODE[$j] = array_map(
                        function ($data_sort) {
                            return $data_sort['code'];
                        }, $sortIdPropCODE[$j]
                    );
                } else {
                    $sortIdPropCODE[$j] = !empty($sortIdPropCODE[$j]['code']) ? array($sortIdPropCODE[$j]['code']) : '';
                }
                $return[$index][$returnId][$condId]['MISSSHOP'][1] = $sortIdPropCODE[$j];
            }
            ///////////////////////////////

            $index++;
        }

        return $return;
    }

    private function GetListOfPropsByIblock($IdIblock)
    {
        $rsProps = CIBlockProperty::GetList( array(
            "SORT" => "ASC",
            'ID' => 'ASC'
        ), array(
            "IBLOCK_ID" => $IdIblock,
            "ACTIVE" => "Y"
        ) );
        while ( $arProp = $rsProps->Fetch() )
        {
            self::$AllPropsForSortByIblock[$IdIblock][] = $arProp;
        }
    }

    private function GetPropVal($ConditionValue, $IdProperty, $IdIblock, $Logic, $ConditionSections, $PriceType = 0, $PriceCode = '')
    {
        $return = array();

        // All Props need for sort
        if (!isset( self::$AllPropsForSortByIblock[$IdIblock] ) && $IdIblock > 0)
        {
            self::GetListOfPropsByIblock( $IdIblock );
        }

        // Get values from conditions
        if ($Logic == 'Equal')
        {
            if ($IdProperty > 0 && $IdIblock > 0)
            {
                if ($PriceType === 'Min' || $PriceType === 'Max' )
                {
                    $return["FILTER"][$IdProperty]['TYPE'][] = strtoupper($PriceType);
                    $return["FILTER"][$IdProperty]['ID'][] = $IdProperty;
                    $return["FILTER"][$IdProperty]['VALUE'][] = $ConditionValue;

                    self::$AllPropsForSortByIblock["FILTER"]['filter'.$IdProperty][] = $return["FILTER"][$IdProperty];
                }
                else
                {
                    $resProperty = CIBlockProperty::GetByID( $IdProperty, $IdIblock, false );

                    if ($arProperty = $resProperty->GetNext())
                    {
                        if ($arProperty['PROPERTY_TYPE'] == 'L') // list
                        {
                            $return = self::GetValueOfListProp( $ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $arProperty['CODE'] );
                        }
                        elseif ($arProperty['PROPERTY_TYPE'] == 'S' && $arProperty['USER_TYPE'] == 'directory')
                        {
                            $return = self::GetValueOfHLProp( $ConditionValue, $IdIblock, $IdProperty, $ConditionSections, (!empty($arProperty['CODE']))?$arProperty['CODE']: $arProperty['ID'] );
                        }
                        elseif ($arProperty['PROPERTY_TYPE'] == 'E') // link
                        {
                            $return = self::GetValueOfLinkProp( $ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $arProperty['CODE'], $arProperty['LINK_IBLOCK_ID'] );
                        }
                        else
                        {
                            $return = self::GetValueOfTextProp( $ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $arProperty['CODE'] );
                        }
                    }
                }
            }
            elseif (($PriceType == 'Min' || $PriceType == 'Max') && $PriceCode != "")
            {
                $res = CCatalogGroup::GetListEx( array(), array(
                    '=NAME' => $PriceCode
                ), false, false, array(
                    'ID'
                ) );
                if ($group = $res->Fetch())
                {
                    $priceID = $group['ID'];
                    if (!is_null( $ConditionValue ))
                    {
                        if ($PriceType == 'Min')
                        {
                            $return["PRICE"][$PriceCode]['TYPE'][] = "MIN";
                        }
                        elseif ($PriceType == 'Max')
                        {
                            $return["PRICE"][$PriceCode]['TYPE'][] = "MAX";
                        }
                        $return["PRICE"][$PriceCode]['ID'][] = $priceID;
                        $return["PRICE"][$PriceCode]['VALUE'][] = $ConditionValue;
                        self::$AllPropsForSortByIblock["PRICES"][$PriceCode][] = $return["PRICE"][$PriceCode];
                    }
                }
            }
        }
        return $return;
    }

    public function GetArray($array)
    {
        $return = array();
        foreach($array as $el)
        {
            $return[]=array($el);
        }
        return $return;
    }

    private function GetValueOfListProp($ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $PropertyCode)
    {
        $return = array();
        if($ConditionValue == "")
        {
            $ListVals = array();
            $ListVals = self::GetValuesIfEmptyValue( $IdIblock, $IdProperty, $ConditionSections );
            if (count( $ListVals['MISSSHOP'][1] ) == count( $ListVals['MISSSHOP'][0] ) && count( $ListVals['MISSSHOP'][1] ) == count( $ListVals['BITRIX'][0] ) && count( $ListVals['MISSSHOP'][1] ) == count( $ListVals['BITRIX'][1] ))
            {
                // Get all values of property - need for xml_id
                $AllProps = array();
                $property_enums = CIBlockPropertyEnum::GetList( Array(
                    "SORT" => "ASC"
                ), Array(
                    "IBLOCK_ID" => $IdIblock,
                    "PROPERTY_ID" => $IdProperty
                ) );
                while ( $enum_fields = $property_enums->GetNext() )
                {
                    $AllProps[$enum_fields['ID']] = $enum_fields['XML_ID'];
                }

                //
                $arAllProps['MISSSHOP'][1] = array();
                foreach ( $ListVals as $ListVal )
                {
                    if(!self::$isGenerateChpu) {
                        //if (!in_array( \CUtil::translit( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'], 'ru', array("replace_space" => "-", "replace_other" => "-")), $arAllProps['MISSSHOP'][1] ) && !is_null( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'] ))
                        if (!in_array( \CUtil::translit( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'], 'ru'), $arAllProps['MISSSHOP'][1] ) && !is_null( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'] ))
                        {
                            //$arAllProps['MISSSHOP'][1][] = \CUtil::translit( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'], 'ru', array("replace_space" => "-", "replace_other" => "-"));
                            $arAllProps['MISSSHOP'][1][] = \CUtil::translit( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'], 'ru');
                            $arAllProps['MISSSHOP'][0][] = "";
                            $arAllProps['BITRIX'][0][] = abs( crc32( htmlspecialcharsbx( $ListVal['PROPERTY_' . $IdProperty . '_ENUM_ID'] ) ) );
                            $arAllProps['BITRIX'][1][] = $AllProps[$ListVal['PROPERTY_' . $IdProperty . '_ENUM_ID']];
                        }
                    } else {
                        //if (!in_array( \CUtil::translit( $ListVal['VALUE'], 'ru', array("replace_space" => "-", "replace_other" => "-")), $arAllProps['MISSSHOP'][1] ) && !is_null( $ListVal['VALUE'] ))
                        if (!in_array( \CUtil::translit( $ListVal['VALUE'], 'ru'), $arAllProps['MISSSHOP'][1] ) && !is_null( $ListVal['VALUE'] ))
                        {
                            //$arAllProps['MISSSHOP'][1][] = \CUtil::translit( $ListVal['VALUE'], 'ru', array("replace_space" => "-", "replace_other" => "-"));
                            $arAllProps['MISSSHOP'][1][] = \CUtil::translit( $ListVal['VALUE'], 'ru');
                            $arAllProps['MISSSHOP'][0][] = "";
                            $arAllProps['BITRIX'][0][] = abs( crc32( htmlspecialcharsbx( $ListVal['ID'] ) ) );
                            $arAllProps['BITRIX'][1][] = $AllProps[$ListVal['ID']];
                        }
                    }
                }
                // All possible values
                if(!self::$isGenerateChpu)
                {
                    $AllVals['MISSSHOP'][1] = parent::AllCombinationsOfArrayElements( $arAllProps['MISSSHOP'][1] );
                    $AllVals['MISSSHOP'][0] = parent::AllCombinationsOfArrayElements( $arAllProps['MISSSHOP'][0] );
                    $AllVals['BITRIX'][0] = parent::AllCombinationsOfArrayElements( $arAllProps['BITRIX'][0] );
                    $AllVals['BITRIX'][1] = parent::AllCombinationsOfArrayElements( $arAllProps['BITRIX'][1] );
                }
                else
                {
                    $AllVals['MISSSHOP'][1] = self::GetArray( $arAllProps['MISSSHOP'][1] );
                    $AllVals['MISSSHOP'][0] = self::GetArray( $arAllProps['MISSSHOP'][0] );
                    $AllVals['BITRIX'][0] = self::GetArray( $arAllProps['BITRIX'][0] );
                    $AllVals['BITRIX'][1] = self::GetArray( $arAllProps['BITRIX'][1] );
                }
                if (count( $AllVals['MISSSHOP'][1] ) > 0)
                    foreach ( $AllVals['MISSSHOP'][1] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['MISSSHOP'][1][] = $AllVal;
                    }
                if (count( $AllVals['MISSSHOP'][0] ) > 0)
                    foreach ( $AllVals['MISSSHOP'][0] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['MISSSHOP'][0][] = $AllVal;
                    }
                if (count( $AllVals['BITRIX'][0] ) > 0)
                    foreach ( $AllVals['BITRIX'][0] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['BITRIX'][0][] = $AllVal;
                    }
                if (count( $AllVals['BITRIX'][1] ) > 0)
                    foreach ( $AllVals['BITRIX'][1] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['BITRIX'][1][] = $AllVal;
                    }
            }
        }
        else
        {
            $property_enums = CIBlockPropertyEnum::GetList(Array(
                "SORT" => "ASC"
            ), Array(
                "IBLOCK_ID" => $IdIblock,
                "PROPERTY_ID" => $IdProperty,
                "ID" => $ConditionValue
            ));

            if ($enum_fields = $property_enums->GetNext()) {
                $code_prop = \CUtil::translit($enum_fields['VALUE'], 'ru', [ "replace_space" => "-", "replace_other" => "-" ]);
                $xml_prop = str_replace(' ', '%20', toLower($enum_fields['XML_ID']));

                $return[$IdProperty]['MISSSHOP'][1][] = $code_prop;
                //$return[$IdProperty]['MISSSHOP'][1][] = \CUtil::translit( $enum_fields['VALUE'], 'ru');
                $return[$IdProperty]['MISSSHOP'][0][] = "";
                $return[$IdProperty]['BITRIX'][0][] = abs(crc32(htmlspecialcharsbx($enum_fields['ID'])));
                $return[$IdProperty]['BITRIX'][1][] = $xml_prop;

                $return[$IdProperty]['SORT_XML'] = [ "code" => $xml_prop, "sort" => $enum_fields['SORT'] ];
                $return[$IdProperty]['SORT_CODE'] = [ "code" => $code_prop, "sort" => $enum_fields['SORT'] ];
                $return[$IdProperty]['ORIGIN_VALUE'] = $enum_fields['VALUE'];
            }
            else
                return false;
        }

        $return[$IdProperty]['IBLOCK_ID'] = $IdIblock;
        $return[$IdProperty]['CODE'] = $PropertyCode;
        if(!isset($return[$IdProperty]['VALUE']))
            $return[$IdProperty]['VALUE'] = $ConditionValue;
        return $return;
    }

    private function GetValueOfLinkProp($ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $PropertyCode, $LinkIblock)
    {
        $return = array();

        if(isset($ConditionValue) && !empty($ConditionValue))
        {
            $res = CIBlockElement::GetByID($ConditionValue);
            if($ar_res = $res->GetNext())
            {
                $return[$IdProperty]['MISSSHOP'][1][] = \CUtil::translit( $ar_res['NAME'], 'ru');
                $return[$IdProperty]['MISSSHOP'][0][] = "";
                $return[$IdProperty]['BITRIX'][0][] = abs( crc32($ar_res['ID']));//CUtil::translit( $ar_res['NAME'], 'ru' );
                $return[$IdProperty]['BITRIX'][1][] = $ar_res['CODE'];
                $return[$IdProperty]['ORIGIN_VALUE'] = $ar_res['NAME'];
                //$return[$IdProperty]['BITRIX'][1][] = urlencode(str_replace(' ', '+',strtolower($GLOBALS['APPLICATION']->ConvertCharset($ar_res['NAME'], SITE_CHARSET, 'UTF-8'))));
            }
        }
        elseif(self::$isGenerateChpu)
        {
            $res = CIBlockElement::GetList(array(), array('IBLOCK_ID'=>$LinkIblock, 'ACTIVE'=>'Y'));
            while($ar_res = $res->GetNext())
            {
                $return[$IdProperty]['MISSSHOP'][1][] = \CUtil::translit( $ar_res['NAME'], 'ru');
                $return[$IdProperty]['MISSSHOP'][0][] = "";
                $return[$IdProperty]['BITRIX'][0][] = abs( crc32($ar_res['ID']));//CUtil::translit( $ar_res['NAME'], 'ru' );
                //$return[$IdProperty]['BITRIX'][1][] = urlencode(str_replace(' ', '+',strtolower($GLOBALS['APPLICATION']->ConvertCharset($ar_res['NAME'], SITE_CHARSET, 'UTF-8'))));
                //$return[$IdProperty]['BITRIX'][1][] = str_replace(' ', '%20', strtolower($GLOBALS['APPLICATION']->ConvertCharset($ar_res['NAME'], SITE_CHARSET, 'UTF-8')));
                $return[$IdProperty]['BITRIX'][1][] = rawurlencode(strtolower($GLOBALS['APPLICATION']->ConvertCharset($ar_res['NAME'], SITE_CHARSET, 'UTF-8')));
                $return[$IdProperty]['ORIGIN_VALUE'] = $ar_res['NAME'];
            }

            $return[$IdProperty]['MISSSHOP'][1] = self::GetArray( $return[$IdProperty]['MISSSHOP'][1] );
            $return[$IdProperty]['MISSSHOP'][0] = self::GetArray( $return[$IdProperty]['MISSSHOP'][0] );
            $return[$IdProperty]['BITRIX'][0] = self::GetArray( $return[$IdProperty]['BITRIX'][0] );
            $return[$IdProperty]['BITRIX'][1] = self::GetArray( $return[$IdProperty]['BITRIX'][1] );

            if (count( $return[$IdProperty]['MISSSHOP'][1] ) > 0){
                foreach ( $return[$IdProperty]['MISSSHOP'][1] as $AllVal )
                {
                    $return[$IdProperty]['ALL']['MISSSHOP'][1][] = $AllVal;
                }
                unset($return[$IdProperty]['MISSSHOP'][1]);
            }
            if (count( $return[$IdProperty]['MISSSHOP'][0] ) > 0){
                foreach ( $return[$IdProperty]['MISSSHOP'][0] as $AllVal )
                {
                    $return[$IdProperty]['ALL']['MISSSHOP'][0][] = $AllVal;
                }
                unset($return[$IdProperty]['MISSSHOP'][0]);
            }
            if(empty($return[$IdProperty]['MISSSHOP'][0]) && empty($return[$IdProperty]['MISSSHOP'][1])){
                unset($return[$IdProperty]['MISSSHOP']);
            }
            if (count( $return[$IdProperty]['BITRIX'][0] ) > 0){
                foreach ( $return[$IdProperty]['BITRIX'][0] as $AllVal )
                {
                    $return[$IdProperty]['ALL']['BITRIX'][0][] = $AllVal;
                }
                unset($return[$IdProperty]['BITRIX'][0]);
            }
            if (count( $return[$IdProperty]['BITRIX'][1] ) > 0){
                foreach ( $return[$IdProperty]['BITRIX'][1] as $AllVal )
                {
                    $return[$IdProperty]['ALL']['BITRIX'][1][] = $AllVal;
                }
                unset($return[$IdProperty]['BITRIX'][1]);
            }
            if(empty($return[$IdProperty]['BITRIX'][0]) && empty($return[$IdProperty]['BITRIX'][1])){
                unset($return[$IdProperty]['BITRIX']);
            }
        }

        $return[$IdProperty]['IBLOCK_ID'] = $IdIblock;
        $return[$IdProperty]['CODE'] = $PropertyCode;
        if(!isset($return[$IdProperty]['VALUE']))
            $return[$IdProperty]['VALUE']=$ConditionValue;

        return $return;
    }

    private function GetValueOfHLProp($ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $PropertyCode)
    {
        $return = array();

        if ($ConditionValue == "")
        {
            $ListVals = array();
            $ListVals = self::GetValuesIfEmptyValue($IdIblock, $IdProperty, $ConditionSections);
            $arAllProps['MISSSHOP'][1] = array();
            $res = CIBlockProperty::GetByID($IdProperty, $IdIblock, false);
            if ($ar_res = $res->GetNext())
            {
                $highBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array(
                    "filter" => array(
                        'TABLE_NAME' => $ar_res['USER_TYPE_SETTINGS']['TABLE_NAME']
                    )
                ));
                while($HLBlock = $highBlock->Fetch())
                {
                    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $HLBlock );
                    $main_query = new Entity\Query( $entity );
                    $main_query->setSelect( array(
                        "*"
                    ) );
                    $main_query->setFilter( array(
                        //'=UF_XML_ID' => $ListVals
                    ) );
                    $result = $main_query->exec();
                    $result = new CDBResult( $result );
                    while ( $row = $result->Fetch() )
                    {
                        $arAllProps['MISSSHOP'][1][] = \CUtil::translit( $row['UF_NAME'], 'ru');
                        $arAllProps['MISSSHOP'][0][] = "";
                        $arAllProps['BITRIX'][0][] = abs( crc32( htmlspecialcharsbx( $row['UF_XML_ID'] ) ) );
                        $arAllProps['BITRIX'][1][] = $row['UF_XML_ID'];
                        $arAllProps['ORIGIN_VALUE'] = $row['UF_XML_ID'];
                    }
                }
            }
            if (count( $arAllProps['MISSSHOP'][1] ) == count( $arAllProps['MISSSHOP'][0] ) && count( $arAllProps['MISSSHOP'][1] ) == count( $arAllProps['BITRIX'][0] ) && count( $arAllProps['MISSSHOP'][1] ) == count( $arAllProps['BITRIX'][1] ))
            {
                if(self::$isGenerateChpu) {
                    $AllVals['MISSSHOP'][1] = self::GetArray( $arAllProps['MISSSHOP'][1] );
                    $AllVals['MISSSHOP'][0] = self::GetArray( $arAllProps['MISSSHOP'][0] );
                    $AllVals['BITRIX'][0] = self::GetArray( $arAllProps['BITRIX'][0] );
                    $AllVals['BITRIX'][1] = self::GetArray( $arAllProps['BITRIX'][1] );
                } else {
                    $AllVals['MISSSHOP'][1] = parent::AllCombinationsOfArrayElements( $arAllProps['MISSSHOP'][1] );
                    $AllVals['MISSSHOP'][0] = parent::AllCombinationsOfArrayElements( $arAllProps['MISSSHOP'][0] );
                    $AllVals['BITRIX'][0] = parent::AllCombinationsOfArrayElements( $arAllProps['BITRIX'][0] );
                    $AllVals['BITRIX'][1] = parent::AllCombinationsOfArrayElements( $arAllProps['BITRIX'][1] );
                }

                if (count( $AllVals['MISSSHOP'][1] ) > 0)
                    foreach ( $AllVals['MISSSHOP'][1] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['MISSSHOP'][1][] = $AllVal;
                    }
                if (count( $AllVals['MISSSHOP'][0] ) > 0)
                    foreach ( $AllVals['MISSSHOP'][0] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['MISSSHOP'][0][] = $AllVal;
                    }
                if (count( $AllVals['BITRIX'][0] ) > 0)
                    foreach ( $AllVals['BITRIX'][0] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['BITRIX'][0][] = $AllVal;
                    }
                if (count( $AllVals['BITRIX'][1] ) > 0)
                    foreach ( $AllVals['BITRIX'][1] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['BITRIX'][1][] = $AllVal;
                    }
            }
        }
        else
        {
            $res = CIBlockProperty::GetByID($IdProperty, $IdIblock, false);
            if ($ar_res = $res->GetNext())
            {
                $highBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array(
                    "filter" => array(
                        'TABLE_NAME' => $ar_res['USER_TYPE_SETTINGS']['TABLE_NAME']
                    )
                ));
                while ( $HLBlock = $highBlock->Fetch() )
                {
                    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $HLBlock );
                    $main_query = new Entity\Query( $entity );
                    $main_query->setSelect( array(
                        "*"
                    ) );
                    //    $filter =  array(
                    //        //                        array("LOGIC" => "OR"),
                    //    );
                    //    if(is_numeric($ConditionValue))
                    //        $filter["ID"] = $ConditionValue;
                    //    else
                    //        $filter["=UF_NAME"] = $ConditionValue;

                    $filter= array(
                        'LOGIC' => 'OR',
                        array('ID' => $ConditionValue),
                        array('=UF_XML_ID' => $ConditionValue),
                        array('=UF_NAME' => $ConditionValue),
                    );

                    $main_query->setFilter($filter);

                    $result = $main_query->exec();
                    $result = new CDBResult( $result );

                    while ( $row = $result->Fetch() )
                    {
                        $return[$IdProperty]['MISSSHOP'][1][] = \CUtil::translit($row['UF_NAME'], 'ru'); //CUtil::translit($ConditionValue, 'ru');
                        $return[$IdProperty]['MISSSHOP'][0][] = "";
                        $return[$IdProperty]['BITRIX'][0][] = abs( crc32( htmlspecialcharsbx( $row['UF_XML_ID'] ) ) );
                        $return[$IdProperty]['BITRIX'][1][] = toLower($row['UF_XML_ID']);
                        $return[$IdProperty]['ORIGIN_VALUE'][] = $row['UF_XML_ID'];
                    }
                }
            }
        }

        $return[$IdProperty]['IBLOCK_ID'] = $IdIblock;
        $return[$IdProperty]['CODE'] = $PropertyCode;
        if(!isset($return[$IdProperty]['VALUE']))
            $return[$IdProperty]['VALUE']=$ConditionValue;

        if(!isset( $return[$IdProperty]['ORIGIN_VALUE']))
            $return[$IdProperty]['ORIGIN_VALUE'] = $ConditionValue;

        return $return;
    }

    private function GetValueOfTextProp($ConditionValue, $IdIblock, $IdProperty, $ConditionSections, $PropertyCode)
    {
        $return = array();
        if($ConditionValue == "")
        {
            $ListVals = array();
            $ListVals = self::GetValuesIfEmptyValue( $IdIblock, $IdProperty, $ConditionSections );
            $arAllProps = array();
            foreach ( $ListVals as $ListVal )
            {
                if (!is_null( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'] ))
                {
                    $arAllProps['MISSSHOP'][1][] = \CUtil::translit( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'], 'ru');
                    $arAllProps['MISSSHOP'][0][] = "";
                    $arAllProps['BITRIX'][0][] = abs( crc32( htmlspecialcharsbx( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'] ) ) );
                    $arAllProps['BITRIX'][1][] = str_replace( '%', '%25', urlencode( $ListVal['PROPERTY_' . $IdProperty . '_VALUE'] ) );
                }
            }
            $AllVals = array();
            if (count( $arAllProps['MISSSHOP'][1] ) == count( $arAllProps['MISSSHOP'][0] ) && count( $arAllProps['MISSSHOP'][1] ) == count( $arAllProps['BITRIX'][0] ) && count( $arAllProps['MISSSHOP'][1] ) == count( $arAllProps['BITRIX'][1] ))
            {
                $AllVals['MISSSHOP'][1] = parent::AllCombinationsOfArrayElements( $arAllProps['MISSSHOP'][1] );
                $AllVals['MISSSHOP'][0] = parent::AllCombinationsOfArrayElements( $arAllProps['MISSSHOP'][0] );
                $AllVals['BITRIX'][0] = parent::AllCombinationsOfArrayElements( $arAllProps['BITRIX'][0] );
                $AllVals['BITRIX'][1] = parent::AllCombinationsOfArrayElements( $arAllProps['BITRIX'][1] );
                if (count( $AllVals['MISSSHOP'][1] ) > 0)
                    foreach ( $AllVals['MISSSHOP'][1] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['MISSSHOP'][1][] = $AllVal;
                    }
                if (count( $AllVals['MISSSHOP'][0] ) > 0)
                    foreach ( $AllVals['MISSSHOP'][0] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['MISSSHOP'][0][] = $AllVal;
                    }
                if (count( $AllVals['BITRIX'][0] ) > 0)
                    foreach ( $AllVals['BITRIX'][0] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['BITRIX'][0][] = $AllVal;
                    }
                if (count( $AllVals['BITRIX'][1] ) > 0)
                    foreach ( $AllVals['BITRIX'][1] as $AllVal )
                    {
                        $return[$IdProperty]['ALL']['BITRIX'][1][] = $AllVal;
                    }
            }
        }
        else
        {
            $return[$IdProperty]['MISSSHOP'][1][] = \CUtil::translit($ConditionValue, 'ru');
            $return[$IdProperty]['MISSSHOP'][0][] = "";
            $return[$IdProperty]['BITRIX'][0][] = abs(crc32(htmlspecialcharsbx($ConditionValue)));
            //$return[$IdProperty]['BITRIX'][1][] = rawurlencode(strtolower($GLOBALS['APPLICATION']->ConvertCharset($ConditionValue, SITE_CHARSET, 'UTF-8')));

            $error = "";
            $utf = \Bitrix\Main\Text\Encoding::convertEncoding(toLower($ConditionValue), LANG_CHARSET, "utf-8", $error);
            $return[$IdProperty]['BITRIX'][1][] = rawurlencode(str_replace("/", "-", $utf));
        }

        $return[$IdProperty]['IBLOCK_ID'] = $IdIblock;
        $return[$IdProperty]['CODE'] = $PropertyCode;
        if(!isset($return[$IdProperty]['VALUE']))
            $return[$IdProperty]['VALUE'] = $ConditionValue;

        $return[$IdProperty]['ORIGIN_VALUE'] = $ConditionValue;

        return $return;
    }
}
