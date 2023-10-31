<?php
namespace Sotbit\Seometa\Helper;

class Link
{
    private static $link = false;
    private $transliteRules = array();
    private static $arrayProps = array();

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if(!self::$link)
            self::$link = new Link();

        return self::$link;
    }

    public function getTransliteRule($IblockID = false)
    {
        if($IblockID === false)
            return false;

        if(!isset($this->transliteRules[$IblockID]))
            $this->transliteRules[$IblockID] = \CIBlock::GetFields($IblockID);

        return $this->transliteRules[$IblockID];
    }

    public function Generate($id = false, \Sotbit\Seometa\Link\AbstractWriter $writer, $sectionList = false)
    {
        if($id === false)
            return;

        $arCondition = \Sotbit\Seometa\Orm\ConditionTable::getById($id)->fetch();
        if($arCondition['FILTER_TYPE'] == 'default')
        {
            $arCondition['FILTER_TYPE'] = \Bitrix\Main\Config\Option::get("sotbit.seometa", "FILTER_TYPE", "bitrix_chpu");
        }

        $FilterType = $arCondition['FILTER_TYPE'];
        $Mask = new Mask();
        $newMask = new Mask();
        $newPartMask = new Mask();
        $Mask->SetTemplate($Mask->GetTemplate($arCondition['INFOBLOCK'], $arCondition['FILTER_TYPE'], false));

        $writer->SetIBlockID($arCondition['INFOBLOCK']);
        $writer->SetCondition($arCondition);

        \Bitrix\Main\Loader::includeModule('iblock');

        if (is_array($sectionList) && count($sectionList) > 0){
            $ConditionSections = $sectionList;
        } else {
            $ConditionSections = unserialize($arCondition['SECTIONS']);
        }

        if(!is_array($ConditionSections) || count($ConditionSections) < 1) // If dont check sections
        {
            $ConditionSections = array();
            $rsSections = \CIBlockSection::GetList(
                array(
                    'SORT' => 'ASC'
                ),
                array(
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => $arCondition['INFOBLOCK']
                ),
                false,
                array(
                    'ID'
                )
            );
            while($arSection = $rsSections->GetNext())
            {
                $ConditionSections[] = $arSection['ID'];
            }
        }

        $Rule = unserialize( $arCondition['RULE'] );
        $template = unserialize( $arCondition['META'] );
        $template = $template['TEMPLATE_NEW_URL'];
        preg_match( "/{(.*?)}/", $template, $template_prop );
        $template = str_replace( $template_prop[0], '#PROPERTIES#', $template );
        $template1 = $template;
        $template_prop = trim( $template_prop[0], '{' );
        $template_prop = trim( $template_prop, '}' );
        $template_prop = explode( ':', $template_prop );

        switch($FilterType)
        {
            case 'combox_chpu':
            case 'bitrix_chpu':
                $newPartMask->SetDelimiter($template_prop[2]);
                break;
            default:
                $newPartMask->SetDelimiter($template_prop[1]);
        }

        $newMask->SetTemplate($template);
        $newPartMask->SetTemplate($template_prop[0]);
        \CSeoMetaSitemap::$isGenerateChpu = true;
        $Pages = \CSeoMetaSitemap::ParseArray($Rule, $ConditionSections);
        foreach($Pages['CHILDREN'] as $i => &$condition)
        {
            $condition = \CSeoMetaSitemap::PrepareConditions(
                array(
                    'DATA' => array(
                        'All' => 'AND',
                        'True' => True
                    ),
                    'CHILDREN' => $condition
                ),
                $ConditionSections
            );
            if(in_array(false, $condition['CHILDREN']))
                unset($Pages['CHILDREN'][$i]);
        }

        // Get sections path
        $rsSections = \CIBlockSection::GetList(
            array(
                'ID' => 'ASC'
            ),
            array(
                'ACTIVE' => 'Y',
                'ID' => $ConditionSections,
                'IBLOCK_ID' => $arCondition['INFOBLOCK']
            ),
            false,
            array(
                'ID'
            ),
            false
        );
        while($arSection = $rsSections->Fetch())
        {
            $CondVals['SECTION'][] = $arSection;
        }
        \CSeoMetaSitemap::SetListOfProps($FilterType);
        $CondVals['PAGES'] = \CSeoMetaSitemap::SortPagesCodes($Pages);

        $Generator = \Sotbit\Seometa\Generater\Common::Create($FilterType);
        \CSeoMetaSitemap::$isGenerateChpu = false;
        foreach($CondVals['SECTION'] as $Section)
        {
            $Mask->SetTemplate($Mask->GetTemplate($arCondition['INFOBLOCK'], $arCondition['FILTER_TYPE'], false));

            if($Mask->HasSectionPlaceholders())
            {
                $ISection = \CIBlockSection::GetById($Section['ID'])->Fetch();
                $SectionUrl = $ISection['CODE'];
                $SectionUrlPath = \CIBlockSection::getSectionCodePath($Section['ID']);
                $section = \CIBlockSection::getById($Section['ID'])->fetch();
                $sectname = $section['NAME'];
                $template = $template1;
                $template = str_replace(
                    array(
                        '#ID#',
                        '#SECTION_ID#',
                        '#SECTION_CODE_PATH#',
                        '#SECTION_CODE#'
                    ),
                    array(
                        '#SECTION_ID#' => $section['ID'],
                        '#ID#' => $section['ID'],
                        '#SECTION_CODE_PATH#' => $SectionUrlPath,
                        '#SECTION_CODE#' => $SectionUrl
                    ),
                    $template
                );
            }

            if(empty($CondVals['PAGES']))
                continue;

            foreach($CondVals['PAGES'] as $Page)
            {
                $obFilter = new Filter();
                $obFilter->SetItem(
                    array(
                        'ACTIVE' => 'Y',
                        'INCLUDE_SUBSECTIONS' => 'Y',
                        'IBLOCK_ID' => $arCondition['INFOBLOCK'],
                        'SECTION_ID' => $section['ID']
                    )
                );

                $name = $sectname;
                $props_templ = array();
                $prop_string_values = array();
                $cond_properties = array();
                $prices = array();
                $filter = array();
                $arFilterParams = array();

                foreach($Page as $CondKey => $CondValProps)
                {
                    $Mask->SetTemplate();
                    $newMask->SetTemplate();
                    $newPartMask->SetTemplate();
                    $keys = array_keys($CondValProps);
                    $CondKey = $keys[0];
                    $CondValProps = current($CondValProps);

                    if(isset($CondValProps['CODE']) && !is_null($CondValProps['CODE']))
                    {
                        if(isset(self::$arrayProps[$CondValProps['IBLOCK_ID']][$CondValProps['CODE']]) &&
                            !empty(self::$arrayProps[$CondValProps['IBLOCK_ID']][$CondValProps['CODE']]))
                            $prop = self::$arrayProps[$CondValProps['IBLOCK_ID']][$CondValProps['CODE']];
                        else
                            self::$arrayProps[$CondValProps['IBLOCK_ID']][$CondValProps['CODE']] =
                                $prop = \CIBlockProperty::GetList(
                                    array(
                                        "SORT" => "ASC",
                                        'ID' => 'ASC'
                                    ),
                                    array(
                                        "IBLOCK_ID" => $CondValProps['IBLOCK_ID'],
                                        "CODE" => $CondValProps['CODE'],
                                        "ACTIVE" => "Y"
                                    )
                                )->fetch();
                        if(isset($CondValProps['VALUE']) && !is_null($CondValProps['VALUE']))
                        {
                            if($prop_string_values[$prop['ID']] == NULL)
                                $prop_string_values[$prop['ID']] = array();

                            if(is_array($CondValProps['VALUE']))
                                $prop_string_values[$prop['ID']] = array_merge($prop_string_values[$prop['ID']], $CondValProps['VALUE']);
                            else
                                $prop_string_values[$prop['ID']][] = $CondValProps['VALUE'];
                        }
                        $CondValProps['PROPERTY_ID'] = $prop['ID'];
                        $name .= ' ' . mb_strtolower($prop['NAME']);
                        $newPartMask->ReplaceHolders(
                            array(
                                '#PROPERTY_CODE#' => $prop['CODE'],
                                '#PROPERTY_ID#' => $prop['ID']
                            )
                        );
                    }

                    /* filter type PRICE */
                    $resultGenerator = $Generator->Generate($CondKey, $CondValProps);
                    $key = ($CondKey == 'PRICE' || $CondKey == 'FILTER') ? $CondKey : 'PROPERTY';

                    // $cond_properties
                    $obFilter->AddItemByProperty($arCondition, $resultGenerator[$key]['SEARCH_PROPERTIES'], $prop_string_values);
                    $arFilterParams[] = $resultGenerator[$key]['PARAMS'];
                    $newPartMask->ReplaceHolders($resultGenerator[$key]['VALUES']);

                    switch($key)
                    {
                        case 'PRICE':
                            $prices[] = $resultGenerator[$key]['PRICES'];
                            break;
                        case 'FILTER':
                            $filter[] = $resultGenerator[$key]['FILTER'];
                            break;
                        default:
                            $props_templ[] = $newPartMask->GetMask();
                    }
                    $cond_properties[] = $resultGenerator[$key]['COND_PROPERTIES'];
                }

                if($Mask->HasSectionPlaceholders())
                {
                    $Mask->ReplaceHolders(
                        array(
                            '#ID#' => $section['ID'],
                            '#SECTION_ID#' => $section['ID'],
                            '#SECTION_CODE_PATH#' => $SectionUrlPath,
                            '#SECTION_CODE#' => $SectionUrl,
                        )
                    );
                }

                if($newMask->HasSectionPlaceholders())
                {
                    $newMask->ReplaceHolders(
                        array(
                            '#ID#' => $section['ID'],
                            '#SECTION_ID#' => $section['ID'],
                            '#SECTION_CODE_PATH#' => $SectionUrlPath,
                            '#SECTION_CODE#' => $SectionUrl,
                        )
                    );
                }

                if($Mask->HasIblockPlaceholders())
                    $Mask->ReplaceIblockHolders($arCondition['INFOBLOCK']);

                if($newMask->HasIblockPlaceholders())
                    $newMask->ReplaceIblockHolders($arCondition['INFOBLOCK']);

                $Mask->ReplaceHolders(
                    array(
                        '#FILTER_PARAMS#' => implode(Mask::GetLinkGlue($Generator), $arFilterParams)
                    )
                );

                $LOC = $SiteUrl . $Mask->GetMask();

                if(mb_substr($LOC, 0, 4) != 'http')
                {
                    $LOC = $SiteUrl . $LOC;
                }

                $prop_url = implode($template_prop[1], $props_templ);

                $newMask->ReplaceHolders(
                    array(
                        '#PROPERTIES#' => $prop_url,
                        '#PRICES#' => implode('', $prices),
                        '#FILTER#' => implode('', $filter)
                    )
                );

                $writerType = get_class($writer);
                $count = $obFilter->ProductCount($writerType);

//				if($count < 1)
//                    continue;

                $res1['properties'] = array();
                foreach($cond_properties as $cond_property)
                    foreach($cond_property as $key => $cond_p)
                    {
                        $res1['properties'][$key] = is_array($cond_p) ? $cond_p : array($cond_p);
                        if(count($cond_properties) == 1 && count($cond_property[$key]) == 1)
                            if(!is_array($cond_property[$key]))
                                $name .= ' ' . $cond_p;
                            else
                                $name .= ' ' . current($cond_p);
                    }

                $res1['real_url'] = $LOC;
                $res1['active'] = \Bitrix\Main\Config\Option::get("sotbit.seometa", "IS_SET_ACTIVE");
                $res1['new_url'] = mb_strtolower($newMask->GetMask());
                $res1['section_id'] = $section['ID'];
                $res1['name'] = $name;
                $res1['product_count'] = $count;
                $res1['condition_id'] = $id;
                $res1['iblock_id'] = $arCondition['INFOBLOCK'];
                $res1['strict_relinking'] = $arCondition['STRICT_RELINKING'];
                $writer->Write($res1);
            }

        }
    }

    public function getSectionList($id = false){
        if($id === false)
            return false;

        $arCondition = \Sotbit\Seometa\ConditionTable::getById($id)->fetch();

        $ConditionSections = unserialize($arCondition['SECTIONS']);

        if(!is_array($ConditionSections) || count($ConditionSections) < 1) // If dont check sections
        {
            $ConditionSections = array();
            $rsSections = \CIBlockSection::GetList(
                array(
                    'SORT' => 'ASC'
                ),
                array(
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => $arCondition['INFOBLOCK']
                ),
                false,
                array(
                    'ID'
                )
            );
            while($arSection = $rsSections->GetNext())
            {
                $ConditionSections[] = $arSection['ID'];
            }
        }

        return $ConditionSections;

    }
}
?>