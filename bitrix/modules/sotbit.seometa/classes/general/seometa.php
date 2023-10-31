<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Sotbit\Seometa\Orm\ConditionTable;
use Bitrix\Main\Text\Encoding;

Loader::includeModule('iblock');

class CSeoMeta extends Bitrix\Iblock\Template\Functions\FunctionBase {
    const MODULE_ID = 'sotbit.seometa';
    const CACHE_TIME_TOOLS = 1800;
    protected static $FilterResult = [];
    private static $Checker = [];
    private static $CheckerRule = [];
    private static $UserFields = [];
    private static $filterChecked = false;
    private static $isStrictCompliance = false;

    public static function GetMask(
        $IblockId = 0,
        $ChpuType = '',
        $fullMask = true
    ) {
        $sectionMask = self::GetSectionMask($IblockId, $fullMask);
        switch($ChpuType) {
            case 'bitrix_chpu':
                $MASK = $sectionMask."/filter/#FILTER_PARAMS#apply/";
                break;
            case 'bitrix_not_chpu':
                $MASK = $sectionMask."/?set_filter=y#FILTER_PARAMS#";
                break;
            case 'misshop_chpu':
                $MASK = $sectionMask."/filter/#FILTER_PARAMS#apply/";
                break;
            case 'combox_chpu':
                $MASK = $sectionMask."/filter/#FILTER_PARAMS#";
                break;
            case 'combox_not_chpu':
                $MASK = $sectionMask."/?#FILTER_PARAMS#";
                break;
            default:
                $MASK = $sectionMask;
        }

        return $MASK;
    }

    private static function setStrictCompliance(
        $val = 'N'
    ) {
        self::$isStrictCompliance = $val != 'N' && !empty($val);
    }

    public static function isFilterChecked(
    ) {
        return self::$filterChecked;
    }

    public static function GetSectionMask(
        $IblockId = 0,
        $fullMask = true
    ) {
        if ($IblockId == 0) {
            return '';
        }

        $iblock = \CIBlock::GetById($IblockId)->Fetch();

        return $fullMask
            ? trim($iblock['SECTION_PAGE_URL'], '/')
            : '/'.trim(str_replace('#SITE_DIR#', '', $iblock['SECTION_PAGE_URL']), '/');
    }

    public static function SetFilterResult(
        $FilterResult,
        $Section
    ) {
        self::$FilterResult = [];
        if(!empty($FilterResult['ITEMS'])) {
            foreach ($FilterResult['ITEMS'] as &$item) {
                if ($item['PROPERTY_TYPE'] == 'S' && $item['USER_TYPE'] == 'directory') {
                    foreach ($item['VALUES'] as $key_property => &$property) {
                        $property['PROPERTY_ID'] = $property['VALUE_ID'];
                        $property['VALUE_ID'] = $key_property;
                    }
                }
            }
            self::$FilterResult = $FilterResult;
        }

        self::$FilterResult['PARAMS_SECTION']['ID'] = $Section;
    }

    public static function excludeFilterParams(
        &$filter = []
    ) {
        $ar_exceptions = explode(";",COption::GetOptionString( self::MODULE_ID,'FILTER_EXCEPTION_SETTINGS', '', SITE_ID));
        if (is_array($ar_exceptions)) {
            foreach ($ar_exceptions as $except) {
                if (is_array($filter) && array_key_exists(trim($except), $filter)) {
                    unset($filter[trim($except)]);
                }
            }
        }
    }

    public static function AddAdditionalFilterResults(
        $FilterAdditionalResult,
        $kombox = false
    ) {
        if($kombox != 'Y') {
            $propPattern = '/^(=|<|>){0,2}PROPERTY_/';
            foreach($FilterAdditionalResult as $key => $value) {
                if(preg_match($propPattern, $key, $match) === 1) {
                    $property_key = $key;
                    $Xvalue = is_array($value) ? $value : [$value];
                    $key = preg_replace($propPattern, '', $key);
                    $Filter = is_numeric($key) ? ['ID' => $key] : ['CODE' => $key];

                    $property_enums = CIBlockProperty::GetList([
                        "ID" => "ASC",
                        "SORT" => "ASC"
                    ], $Filter);

                    while($enum_fields = $property_enums->GetNext()) {
                        $bool = true;
                        if(!empty(self::$FilterResult['ITEMS'][$enum_fields['ID']]['VALUES'])) {
                            foreach (self::$FilterResult['ITEMS'][$enum_fields['ID']]['VALUES'] as $value) {
                                if (!self::$filterChecked && $value['CHECKED'] == true) {
                                    self::$filterChecked = true;
                                }

                                if (isset($value["FACET_VALUE"]) && ($value["FACET_VALUE"] == $Xvalue[0])) {
                                    $bool = false;
                                }
                            }
                        }

                        $ListValues = self::GetValuesForEnum($enum_fields, $Xvalue);
                        if($bool) {
                            foreach($Xvalue as $idx => $XXvalue) {
                                $symbs = explode('PROPERTY_', $property_key);
                                $symb = $symbs[0];
                                $symbKey = $XXvalue;

                                if(!empty($symb) && strcmp($symb, '><') == 0) {
                                    $symbKey = $idx == 0 ? 'MIN' : 'MAX';
                                }elseif (!empty($symb) && strcmp($symb, '<=') == 0){
                                    $symbKey = 'MAX';
                                }elseif (!empty($symb) && strcmp($symb, '>=') == 0){
                                    $symbKey = 'MIN';
                                }

                                $symbKey = htmlspecialchars($symbKey);
                                $itemLink = &self::$FilterResult['ITEMS'][$enum_fields['ID']]['VALUES'][$symbKey];
                                if(
                                    !self::$filterChecked &&
                                    !isset($itemLink['CHECKED'])
                                ) {
                                    self::$filterChecked = true;
                                }

                                if(!isset($itemLink['CHECKED'])) {
                                    $itemLink['CHECKED'] = 1;
                                }

                                if(!isset($itemLink['VALUE'])) {
                                    $itemLink['VALUE'] = $XXvalue;
                                }

                                if(!isset(self::$FilterResult['ITEMS'][$enum_fields['ID']]['CODE'])) {
                                    self::$FilterResult['ITEMS'][$enum_fields['ID']]['CODE'] = $enum_fields['CODE'];
                                }

                                if(isset($ListValues[$XXvalue])) {
                                    self::$FilterResult['ITEMS'][$enum_fields['ID']]['VALUES'][$XXvalue]['LIST_VALUE'] = $ListValues[$XXvalue];
                                    if(isset($ListValues[$ListValues[$XXvalue]])) {
                                        self::$FilterResult['ITEMS'][$enum_fields['ID']]['VALUES'][$XXvalue]['LIST_VALUE_NAME'] = $ListValues[$ListValues[$XXvalue]];
                                    }

                                    unset($ListValues[$XXvalue]);
                                }
                            }
                        }
                    }
                }

                if(($length = mb_stripos($key, 'CATALOG_PRICE_')) !== false) {
                    $operator = mb_substr($key, 0, $length);
                    $priceCode = 'BASE';
                    if($operator == '><' && is_array($value)) {
                        self::$FilterResult["ITEMS"][$priceCode]['VALUES']['MIN']['VALUE'] = $value[0];
                        self::$FilterResult["ITEMS"][$priceCode]['VALUES']['MAX']['VALUE'] = $value[1];
                    } else if( $operator == '<=' ) {
                        self::$FilterResult["ITEMS"][$priceCode]['VALUES']['MAX']['VALUE'] = $value;
                    }  else if( $operator == '>=' ) {
                        self::$FilterResult["ITEMS"][$priceCode]['VALUES']['MIN']['VALUE'] = $value;
                    }
                } else if(is_array($value)) {
                    self::AddAdditionalFilterResults($value, $kombox);
                }
            }
        } else {
            foreach(self::$FilterResult["ITEMS"] as $key => $prop) {
                $Xvalue = [];
                $props = $prop['VALUES'];
                unset(self::$FilterResult["ITEMS"][$key]['VALUES']);

                foreach($props as $id => $val) {
                    if($val['CHECKED'] && !empty($val['VALUE'])) {
                        self::$FilterResult['ITEMS'][$key]['VALUES'][] = [
                            'CHECKED' => 1,
                            'HTML_VALUE' => $val['HTML_VALUE'],
                            'VALUE' => $val['VALUE'],
                        ];
                    } else if($val['CHECKED'] && !empty($val['VALUE_ID'])) {
                        $Xvalue[] = $prop['ID'];
                        self::$FilterResult['ITEMS'][$key]['VALUES'][$val['VALUE_ID']] = [
                            'CHECKED' => 1,
                            'VALUE' => $val['VALUE_ID'],
                            'LIST_VALUE' => $val['VALUE'],
                        ];
                    } elseif (
                        !isset($val['CHECKED'])
                        && isset($val['VALUE'])
                        && !empty($val['HTML_VALUE'])
                        && $val['HTML_VALUE'] != $val['VALUE']
                        && isset($val['RANGE_VALUE'])
                    ) {
                        $Xvalue[] = $id;
                        self::$FilterResult['ITEMS'][$key]['VALUES'][$id] = [
                            'CHECKED' => 1,
                            'HTML_VALUE' => $val['HTML_VALUE'],
                            'VALUE' => $val['VALUE'],
                        ];
                    }

                    if (
                        !self::$filterChecked && $val['CHECKED'] ||
                        (
                            isset(self::$FilterResult['ITEMS'][$key]['VALUES'][$id]) &&
                            self::$FilterResult['ITEMS'][$key]['VALUES'][$id]['CHECKED'] == 1
                        )
                    ) {
                        self::$filterChecked = true;
                    }
                }
            }
        }
    }

    static function GetValuesForEnum(
        $enum_fields = false,
        $Xvalue = []
    ) {
        $vars = [];
        $CacheConstantCheck = self::CacheConstantCheck();
        if($CacheConstantCheck == 'N') {
            return self::GetValuesForEnum_NoCache($enum_fields, $Xvalue);
        }

        $obCache = new CPHPCache();
        $cache_dir = '/' . self::MODULE_ID . '_GetValuesForEnum';
        $cache_id = self::MODULE_ID . '|GetValuesForEnum|' . $enum_fields['ID'] . serialize($Xvalue);
        if ($obCache->InitCache(self::CACHE_TIME_TOOLS, $cache_id, $cache_dir)) {
            $vars = $obCache->GetVars();
        } elseif ($obCache->StartDataCache()) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cache_dir);
            $vars = self::GetValuesForEnum_NoCache($enum_fields, $Xvalue);
            $CACHE_MANAGER->RegisterTag(self::MODULE_ID.'_GetValuesForEnum');
            $CACHE_MANAGER->RegisterTag(self::MODULE_ID);
            $CACHE_MANAGER->EndTagCache();
            $obCache->EndDataCache($vars);
        }

        return $vars;
    }

    static function CacheConstantCheck(
    ) {
        $cacheOn = COption::GetOptionString(self::MODULE_ID, "MANAGED_CACHE_ON", 'Y', SITE_ID);
        define("BX_COMP_MANAGED_CACHE", (bool) $cacheOn == "Y");

        return $cacheOn;
    }

    static function GetValuesForEnum_NoCache(
        $enum_fields = false,
        $Xvalue = []
    ) {
        $ListValues = [];
        if (!$enum_fields) {
            return [];
        }

        //if list
        if ($enum_fields['PROPERTY_TYPE'] == 'L') {
            $property_enums = CIBlockPropertyEnum::GetList(
                ["SORT" => "ASC"],
                [
                    'ID' => $Xvalue,
                    "PROPERTY_ID" => $enum_fields['ID']
                ]);
            while ($property_fields = $property_enums->GetNext()) {
                $ListValues[$property_fields['ID']] = $property_fields['VALUE'];
            }
        } elseif ($enum_fields["PROPERTY_TYPE"] === "E") {
            $arLinkFilter = [
                "ID" => $Xvalue,
                'IBLOCK_ID' => $enum_fields['LINK_IBLOCK_ID']
            ];
            $rsLink = CIBlockElement::GetList(
                ["SORT" => "ASC"],
                $arLinkFilter,
                false,
                false,
                [
                    "ID",
                    "NAME"
                ]
            );
            while ($elementFields = $rsLink->GetNext()) {
                $ListValues[$elementFields['ID']] = $elementFields['NAME'];
            }
        } elseif ($enum_fields["PROPERTY_TYPE"] === "S" && $enum_fields['USER_TYPE'] == 'directory') {
            if (isset($enum_fields['USER_TYPE_SETTINGS']['TABLE_NAME'])) {
                $hlblock = HighloadBlockTable::getList([
                    "filter" => [
                        'TABLE_NAME' => $enum_fields['USER_TYPE_SETTINGS']['TABLE_NAME']
                    ]
                ])->fetch();
                if (isset($hlblock['ID'])) {
                    $entity = HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();
                    $rsPropEnums = $entity_data_class::getList(array('filter' => array('UF_XML_ID' => $Xvalue)));
                    while($arEnum = $rsPropEnums->fetch()) {
                        //$ListValues[$Xvalue] = $arEnum['ID'] ;
                        foreach($Xvalue as $Xvls) {
                            if($arEnum['UF_XML_ID'] == $Xvls) {
                                $ListValues[$Xvls] = $arEnum['ID'];
                                $ListValues[$arEnum['ID']] = $arEnum['UF_NAME'];
                            }
                        }
                    }
                }
            }
        }

        return $ListValues;
    }

    public static function FilterCheck(
    ) {
        if (!empty(self::$FilterResult)) {
            $FilterResult = self::$FilterResult;
            self::$Checker = [];
            if($FilterResult['ITEMS']) {
                foreach ($FilterResult['ITEMS'] as $key => $param) {
                    if($key !== 'ASPRO_FILTER_SORT'){ //fix for aspro catalog.smart.filter
                        foreach ($param['VALUES'] as $key_val => $param_val) {
                            if (!empty($param_val['CHECKED'])) {
                                if (!empty($param['ID'])) {
                                    self::$Checker[$param['ID']][$key_val] = 1;
                                } elseif (!empty($key_val)) {
                                    self::$Checker[$key][$key_val] = 1;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function getRules(
        $arParams
    ) {
        $rows = [];
        $filter = [
            '=ACTIVE' => 'Y',
        ];
        if ($arParams['IBLOCK_ID']) {
            $filter['INFOBLOCK'] = (int)$arParams['IBLOCK_ID'];
        }
        $order = ['SORT' => 'desc'];
        $result = ConditionTable::getList([
            'select' => [
                'ID',
                'INFOBLOCK',
                'SITES',
                'SECTIONS',
                'RULE',
                'META',
                'NO_INDEX',
                'STRONG'
            ],
            'filter' => $filter,
            'order' => $order,
        ]);
        while($row = $result->fetch()) {
            $sites = unserialize($row['SITES']);
            $sections = unserialize($row['SECTIONS']);
            if((!isset($sections) || !is_array($sections) || in_array($arParams["SECTION_ID"], $sections)) && in_array(SITE_ID, $sites)) {
                unset($row['SITES']);
                unset($row['SECTIONS']);
                $rows[] =  $row;
            }
        }

        return $rows;
    }

    public static function SetMetaCondition(
        $rule,
        $SectionId,
        $IblockId
    ) {
        $return = [];
        self::$CheckerRule = self::$Checker;
        self::setStrictCompliance($rule['STRONG']);
        $result = self::ParseArray($rule['RULES']);
        //strong rule
        if($rule['STRONG'] == 'Y' && isset(self::$CheckerRule)) {
            static $countOptions = 0;
            foreach (self::$CheckerRule as $param) {
                /*if($rule['RULES']['DATA']['All'] == 'OR' && $rule['RULES']['DATA']['True'] == 'True') {
                    $countOptions += intval(in_array(0,$param));
                    if ($countOptions > 1) {
                        $result = 0;
                        break;
                    }
                }*/

                if (in_array(1,$param)) {
                    $result = 0;
                    break;
                }
            }
        }

        if($result == 1) {
            $return['ID'] = $rule['ID'];
            $return['TITLE'] = self::UserFields($rule['META']['ELEMENT_TITLE'], $SectionId, $IblockId);
            $return['KEYWORDS'] = self::UserFields($rule['META']['ELEMENT_KEYWORDS'], $SectionId, $IblockId);
            $return['DESCRIPTION'] = self::UserFields($rule['META']['ELEMENT_DESCRIPTION'], $SectionId, $IblockId);
            $return['PAGE_TITLE'] = self::UserFields($rule['META']['ELEMENT_PAGE_TITLE'], $SectionId, $IblockId);
            $return['BREADCRUMB_TITLE'] = self::UserFields($rule['META']['ELEMENT_BREADCRUMB_TITLE'], $SectionId, $IblockId);
            $return['ELEMENT_BOTTOM_DESC'] = self::UserFields($rule['META']['ELEMENT_BOTTOM_DESC'], $SectionId, $IblockId);
            $return['ELEMENT_TOP_DESC'] = self::UserFields($rule['META']['ELEMENT_TOP_DESC'], $SectionId, $IblockId);
            $return['ELEMENT_ADD_DESC'] = self::UserFields($rule['META']['ELEMENT_ADD_DESC'], $SectionId, $IblockId);
            $return['ELEMENT_BOTTOM_DESC_TYPE'] = self::UserFields($rule['META']['ELEMENT_BOTTOM_DESC_TYPE'], $SectionId, $IblockId);
            $return['ELEMENT_TOP_DESC_TYPE'] = self::UserFields($rule['META']['ELEMENT_TOP_DESC_TYPE'], $SectionId, $IblockId);
            $return['ELEMENT_ADD_DESC_TYPE'] = self::UserFields($rule['META']['ELEMENT_ADD_DESC_TYPE'], $SectionId, $IblockId);
            $return['ELEMENT_FILE'] = self::UserFields($rule['META']['ELEMENT_FILE'], $SectionId, $IblockId);
            if ($rule['NO_INDEX'] == 'Y') {
                $return['NO_INDEX'] = 'Y';
            } elseif ($rule['NO_INDEX'] == 'N') {
                $return['NO_INDEX'] = 'N';
            }
        }

        return $return;
    }

    private static function ParseArray(
        $array
    ) {
        $result = self::PrepareConditions($array['CHILDREN']);
        if(isset($array['DATA']['All']) && isset($array['DATA']['True'])) {
            if ($array['DATA']['All'] == 'AND' && $array['DATA']['True'] == 'True') {
                $return = self::ANDConditions($result);
            }
            if ($array['DATA']['All'] == 'OR' && $array['DATA']['True'] == 'True') {
                $return = self::ORConditions($result);
            }
            if ($array['DATA']['All'] == 'AND' && $array['DATA']['True'] == 'False') {
                $return = self::ANDFalseConditions($result);
            }
            if ($array['DATA']['All'] == 'OR' && $array['DATA']['True'] == 'False') {
                $return = self::ORFalseConditions($result);
            }
        }

        return $return;
    }

    private static function PrepareConditions(
        $conditions
    ) {
        $MassCond = [];
        foreach($conditions as $condition) {
            $type = 0;
            if (!empty($condition['CLASS_ID']) && $condition['CLASS_ID'] == 'CondGroup') {
                array_push($MassCond, self::ParseArray($condition));
            }

            $idsSection = explode(':', $condition['CLASS_ID']);
            $idSections = $idsSection[count($idsSection) - 1];
            $idCondition = $condition['DATA']['value'];

            //Range for prices
            $Types = explode('Price', $condition['CLASS_ID']);

            // MAX_PRICE
            if ($Types[0] == 'CondIBMax') {
                $type = 'MAX:VALUE:' . $Types[1];
            } elseif ($Types[0] == 'CondIBMin') {
                $type = 'MIN:VALUE:' . $Types[1];
            }

            // MAX_PRICE
            if ($Types[0] == 'CondIBMaxFilter') {
                if (!isset(self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MAX']['HTML_VALUE'])) {
                    self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MAX']['HTML_VALUE'] = self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MAX']['VALUE'];
                }
                $type = 'MAX:HTML_VALUE:' . $Types[1];
            }
            if($Types[0] == 'CondIBMinFilter') {
                if (!isset(self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MIN']['HTML_VALUE'])) {
                    self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MIN']['HTML_VALUE'] = self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MIN']['VALUE'];
                }
                $type = 'MIN:HTML_VALUE:'.$Types[1];
            }
            // end Range for prices

            //Range for properties
            $Types = explode('Property', $condition['CLASS_ID']);
            $arTypes = explode(':', $Types[1]);
            $Types[1] = $arTypes[2];
            if ($Types[0] == 'CondIBMax') {
                $type = 'MAX:VALUE:' . $Types[1];
            } elseif ($Types[0] == 'CondIBMin') {
                $type = 'MIN:VALUE:' . $Types[1];
            } elseif ($Types[0] == 'CondIBMaxFilter') {
                if (!isset(self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MAX']['HTML_VALUE'])) {
                    self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MAX']['HTML_VALUE'] = self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MAX']['VALUE'];
                }
                $type = 'MAX:HTML_VALUE:' . $Types[1];
            } elseif ($Types[0] == 'CondIBMinFilter') {
                if (!isset(self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MIN']['HTML_VALUE'])) {
                    self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MIN']['HTML_VALUE'] = self::$FilterResult['ITEMS'][$Types[1]]['VALUES']['MIN']['VALUE'];
                }
                $type = 'MIN:HTML_VALUE:' . $Types[1];
            }
            // end Range for properties

            //if [SORT] -> [ID]
            $FilterResult = self::$FilterResult;
            $idSection = -1;
            if (!empty($FilterResult['ITEMS'])) {
                foreach ($FilterResult['ITEMS'] as $key => $val) {
                    if (isset($val['ID']) && $val['ID'] == $idSections && !isset($val['PRICE'])) {
                        $idSection = $key;
                    } elseif (!isset($val['ID'])) {
                        $idSection = $idSections;
                    }
                }
            }

            if ($idSection == -1) {
                $idSection = $idSections;
            }

            // if section
            if ($idSections == 'CondIBSection') {
                $idSection = 'SECTION_ID';
            }

            if($condition['DATA']['logic'] == 'Equal') {
                array_push($MassCond, self::CheckElementsEqual($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'Not') {
                array_push($MassCond, self::CheckElementsNot($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'Contain') {
                array_push($MassCond, self::CheckElementsContain($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'NotCont') {
                array_push($MassCond, self::CheckElementsNotCont($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'Great') {
                array_push($MassCond, self::CheckElementsGreat($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'Less') {
                array_push($MassCond, self::CheckElementsLess($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'EqGr') {
                array_push($MassCond, self::CheckElementsEqGr($idSection, $idCondition, $type));
            } else if($condition['DATA']['logic'] == 'EqLs') {
                array_push($MassCond, self::CheckElementsEqLs($idSection, $idCondition, $type));
            }
        }

        return $MassCond;
    }

    private static function CheckElementsEqual($idSection, $idCondition, $type = 0) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        if($type === 0) {
            if($idCondition == '' && isset($FilterResult['ITEMS'][$idSection]['VALUES']) && is_array($FilterResult['ITEMS'][$idSection]['VALUES'])) {
                foreach($FilterResult['ITEMS'][$idSection]['VALUES'] as $param) {
                    if(!empty(self::$CheckerRule[$idSection])) {
                        foreach (self::$CheckerRule[$idSection] as $key_check => $param_check) {
                            self::$CheckerRule[$idSection][$key_check] = 0;
                        }
                    }
                    if(!empty($param['CHECKED'])) {
                        $return = 1;
                        break;
                    }
                }
            } elseif (isset($FilterResult['ITEMS'][$idSection]['VALUES']) && is_array($FilterResult['ITEMS'][$idSection]['VALUES'])) {
                foreach($FilterResult['ITEMS'][$idSection]['VALUES'] as $key => $param) {
                    if(
                        (is_int($idCondition) && is_numeric($key) && $key == $idCondition)
                        || strcmp(htmlspecialchars_decode($param['VALUE']), $idCondition) == 0
                        || strcmp($param['LIST_VALUE'], $idCondition) == 0
                        || (strcmp($idSection, 'SECTION_ID') == 0 && strcmp($param['CONTROL_NAME_SEF'], $idCondition) == 0) /*(isset($param['FACET_VALUE']) && strcmp($param['FACET_VALUE'], $idCondition) == 0) ||*/
                        || (isset($param['DEFAULT']['ID']) && strcmp($param['DEFAULT']['ID'], $idCondition) == 0)
                    ) {
                        if ($key === 'MIN' || $key === 'MAX') {
                            continue;
                        }

                        self::$CheckerRule[$idSection][$key] = 0;
                        return (int) !empty($FilterResult['ITEMS'][$idSection]['VALUES'][$key]['CHECKED']);
                    }
                }
            }
        } else {
            $types = explode(':', $type);
            if (
                ($idCondition == '' && !empty($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]]))
                || (isset($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]])
                    && $FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]] == $idCondition)
            ) {
                $return = 1;
                unset(self::$CheckerRule[$idSection][$idCondition]);
                self::$CheckerRule[$idSection][$types[0]] = 0;
            }
        }

        return $return;
    }

    private static function CheckElementsNot(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        $Check = 0;
        if ($type === 0) {
            if ($idCondition == '') {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $param) {
                    foreach (self::$CheckerRule[$idSection] as $key_check => $param_check) {
                        self::$CheckerRule[$idSection][$key_check] = 0;
                    }

                    if (!empty($param['CHECKED'])) {
                        break;
                    } else {
                        $return = 1;
                    }
                }
            } else {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $key => $param) {
                    if (!empty($FilterResult['ITEMS'][$idSection]['VALUES'][$key]['CHECKED'])) {
                        self::$CheckerRule[$idSection][$key] = 0;
                        $Check = 1;
                    }
                }

                $return = intval($Check == 1);
            }
        } else {
            $types = explode(':', $type);
            self::$CheckerRule[$idSection][$types[0]] = 0;
            if (
                ($idCondition == '' && empty($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]]))
                || (isset($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]]) && $FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]] != $idCondition)
            ) {
                $return = 1;
            }
        }

        return $return;
    }

    private static function CheckElementsContain(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        if ($type == 0) {
            if ($idCondition == '') {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $param) {
                    foreach (self::$CheckerRule[$idSection] as $key_check => $param_check) {
                        self::$CheckerRule[$idSection][$key_check] = 0;
                    }

                    if (!empty($param['CHECKED'])) {
                        $return = 1;
                        break;
                    }
                }
            } else {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $key => $param) {
                    if (mb_stripos($param['VALUE'], $idCondition) !== false && !empty($param['CHECKED'])) {
                        self::$CheckerRule[$idSection][$key] = 0;
                        $return = 1;
                    } elseif (!empty($param['CHECKED'])) {
                        break;
                    }
                }
            }
        }

        return $return;
    }

    private static function CheckElementsNotCont(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $CheckedElements = '';
        $FilterResult = self::$FilterResult;
        if ($type == 0) {
            if ($idCondition == '') {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $param) {
                    foreach (self::$CheckerRule[$idSection] as $key_check => $param_check) {
                        self::$CheckerRule[$idSection][$key_check] = 0;
                    }

                    if (!empty($param['CHECKED'])) {
                        $return = 1;
                        break;
                    }
                }
            } else {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $key => $param) {
                    if (!empty($param['CHECKED'])) {
                        self::$CheckerRule[$idSection][$key] = 0;
                        $CheckedElements .= $param['VALUE'];
                    }
                }

                if (mb_stripos($CheckedElements, $idCondition) === false || !empty($CheckedElements)) {
                    $return = 1;
                }
            }
        }

        return $return;
    }

    private static function CheckElementsGreat(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        if (
            ($idCondition == '' && !empty($FilterResult['ITEMS'][$idSection]['MAX']['HTML_VALUE']))
            || (isset($FilterResult['ITEMS'][$idSection]['MAX']['HTML_VALUE'])
                && $FilterResult['ITEMS'][$idSection]['MAX']['HTML_VALUE'] > $idCondition)
        ) {
            $return = 1;
        } elseif (
            $type === 0
            && isset($FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE'])
            && $FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE'] > $idCondition
        ) {
            $return = 1;
        }

        return $return;
    }

    private static function CheckElementsLess(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        if (
            $type === 0
            && (
                ($idCondition == '' && !empty($FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE']))
                || (isset($FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE'])
                    && $FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE'] < $idCondition)
                || (isset($FilterResult['ITEMS'][$idSection]['MAX']['HTML_VALUE'])
                    && $FilterResult['ITEMS'][$idSection]['MAX']['HTML_VALUE'] < $idCondition)
            )
        ) {
            $return = 1;
        } elseif (
            ($idCondition == '' && !empty($FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE']))
            || (isset($FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE'])
                && $FilterResult['ITEMS'][$idSection]['MIN']['HTML_VALUE'] > $idCondition)
        ) {
            $return = 1;
        }

        return $return;
    }

    private static function CheckElementsEqGr(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        if ($type === 0) {
            if ($idCondition == '') {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $param) {
                    foreach (self::$CheckerRule[$idSection] as $key_check => $param_check) {
                        self::$CheckerRule[$idSection][$key_check] = 0;
                    }

                    if (!empty($param['CHECKED'])) {
                        $return = 1;
                        break;
                    }
                }
            } elseif (!empty($FilterResult['ITEMS'][$idSection]['VALUES'][$idCondition]['CHECKED'])) {
                self::$CheckerRule[$idSection][$idCondition] = 0;
                $return = 1;
            }
        } else {
            $types = explode(':', $type);
            if (
                ($idCondition == '' && !empty($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]]))
                || (isset($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]])
                    && $FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]] >= $idCondition)
            ) {
                $return = 1;
                unset(self::$CheckerRule[$idSection][$idCondition]);
                self::$CheckerRule[$idSection][$types[0]] = 0;
            }
        }

        return $return;
    }

    private static function CheckElementsEqLs(
        $idSection,
        $idCondition,
        $type = 0
    ) {
        $return = 0;
        $FilterResult = self::$FilterResult;
        if ($type === 0) {
            if ($idCondition == '') {
                foreach ($FilterResult['ITEMS'][$idSection]['VALUES'] as $param) {
                    foreach (self::$CheckerRule[$idSection] as $key_check => $param_check) {
                        self::$CheckerRule[$idSection][$key_check] = 0;
                    }
                    if (!empty($param['CHECKED'])) {
                        $return = 1;
                        break;
                    }
                }
            } elseif (!empty($FilterResult['ITEMS'][$idSection]['VALUES'][$idCondition]['CHECKED'])) {
                self::$CheckerRule[$idSection][$idCondition] = 0;
                $return = 1;
            }
        } else {
            $types = explode(':', $type);
            if (
                ($idCondition == '' && !empty($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]]))
                || (isset($FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]])
                    && $FilterResult['ITEMS'][$types[2]]['VALUES'][$types[0]][$types[1]] <= $idCondition)
            ) {
                $return = 1;
                unset(self::$CheckerRule[$idSection][$idCondition]);
                self::$CheckerRule[$idSection][$types[0]] = 0;
            }
        }

        return $return;
    }

    private static function ANDConditions(
        $conditions
    ) {
        return (int)!in_array(0, $conditions);
    }

    private static function ORConditions(
        $conditions
    ) {
        if(self::$isStrictCompliance) {
            $arr = array_count_values($conditions);
            return (int) (isset($arr[1]) && $arr[1] == 1);
        }

        return (int)in_array(1, $conditions);
    }

    private static function ANDFalseConditions(
        $conditions
    ) {
        $return = 0;
        foreach ($conditions as $key => $condition) {
            if (($key == 0 && $condition == 1) || $condition == 0) {
                $return = 1;
            } else {
                $return = 0;
                break;
            }
        }

        return $return;
    }

    private static function ORFalseConditions(
        $conditions
    ) {
        $return = 0;
        foreach ($conditions as $key => $condition) {
            if (($key == 0 && $condition == 1) || $condition == 0) {
                $return = 1;
                break;
            }
        }

        return $return;
    }

    public static function UserFields(
        $str,
        $SectionID,
        $IblockId
    ) {
        preg_match_all('/\#(.+?)\#/', $str, $matches);
        if(!empty($matches[0]) && is_array($matches[0])) {
            $NeedFields = [];
            foreach($matches[0] as $UserField) {
                if(!array_key_exists($UserField, self::$UserFields)) {
                    $NeedFields[] = str_replace('#', '', $UserField);
                }
            }

            if(count($NeedFields) > 0) {
                $ar_result = CIBlockSection::GetList(
                    ["SORT" => "ASC"],
                    [
                        "IBLOCK_ID" => $IblockId,
                        "ID" => $SectionID
                    ],
                    false,
                    $NeedFields);
                if($res = $ar_result->GetNext()) {
                    foreach($NeedFields as $NeedField) {
                        if(is_array($res[$NeedField])){
                            $resUF = \Bitrix\Main\UserFieldTable::getList([
                                'filter' => ['FIELD_NAME' => $NeedField],
                                'select' => ['SETTINGS']
                            ])->fetch();

                            $hl = $resUF['SETTINGS']['HLBLOCK_ID'];
                            $finalRes =  implode(', ', $res[$NeedField]);
                            if($hl){
                                $highBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array("filter" => array('ID' => $hl)))->fetch();
                                $dataClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($highBlock)->getDataClass();
                                $rows = $dataClass::getList(array(
                                    'select' => array('UF_NAME'),
                                    'filter' => array('ID' => $res[$NeedField])
                                ))->fetchAll();
                                $finalRes = implode(', ',array_column($rows, 'UF_NAME'));
                            }
                            self::$UserFields['#'.$NeedField.'#'] = $finalRes;
                        }else{
                            self::$UserFields['#'.$NeedField.'#'] = $res[$NeedField] ?: '';
                        }
                    }
                }
            }
        }

        if(count(self::$UserFields) > 0){
            $str = str_replace(array_keys(self::$UserFields), array_values(self::$UserFields), $str);
        }

        return $str;
    }

    public function getIBlocks($iblockType) {
        $arrIBlockList = [];
        if (!Loader::includeModule('iblock')) {
            return $arrIBlockList;
        }

        $iblockList = CIBlock::GetList(
            [
                "id" => "asc"
            ],
            [
                "ACTIVE" => "Y",
                "TYPE" => $iblockType
            ]);
        while ($iblock = $iblockList->Fetch()) {
            $arrIBlockList["REFERENCE"][] = "[" . $iblock["ID"] . "] " . $iblock["NAME"];
            $arrIBlockList["REFERENCE_ID"][] = $iblock["ID"];
        }

        return $arrIBlockList;
    }

    public function getSections($iblockId) {
        $arrSectionsList = [];
        if($iblockId == null){
            return $arrSectionsList;
        }
        $sectionsList = CIBlockSection::GetList(
            [
                "left_margin" => "asc"
            ],
            [
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
                'IBLOCK_ID' => $iblockId
            ],
            false,
            [
                'ID',
                'NAME',
                'DEPTH_LEVEL'
            ]);
        while ($section = $sectionsList->GetNext()) {
            $arrSectionsList["REFERENCE"][] = "[" . $section["ID"] . "] " . str_repeat(" . ", $section["DEPTH_LEVEL"]) . $section["NAME"];
            $arrSectionsList["REFERENCE_ID"][] = $section["ID"];
        }

        return $arrSectionsList;
    }

    public static function encodeRealUrl(string $str) :string
    {
        $str = Encoding::convertEncoding($str, LANG_CHARSET, "utf-8", $error);
        $str = htmlspecialchars_decode($str);
        $str = rawurlencode(rawurldecode($str));
        return str_replace('%2F', '/', $str);
    }
}
