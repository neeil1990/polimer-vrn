<?
 
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class CSeoMetaAutogenerator
{
    /* ��������� ���� ������ */
    public function getSites()
    {
        $arrSitesList = array();
        $sitesList = \Bitrix\Main\SiteTable::getList();
        while($site = $sitesList->Fetch())
        {
            $arrSitesList[] = $site['LID'];
        }

        return $arrSitesList;
    }

    /* ��������� ���� ����� ���������� */
    public function getIBlockTypes()
    {
        if(!Loader::includeModule('iblock'))
            return array();

        $arrIBlockTypes = array();
        $iblockTypes = CIBlockParameters::GetIBlockTypes();
        foreach($iblockTypes as $id => $name)
        {
            $arrIBlockTypes["REFERENCE"][] = $name;
            $arrIBlockTypes["REFERENCE_ID"][] = $id;
        }

        return $arrIBlockTypes;
    }

    /* ��������� ���� ���������� �� ���� */
    public function getIBlocks($iblockType)
    {
        if(!Loader::includeModule('iblock'))
            return array();

        $arrIBlockList = array();
        $iblockList = CIBlock::GetList(array(
            "id" => "asc"
        ), array(
                "ACTIVE" => "Y",
                "TYPE" => $iblockType
            ));
        while($iblock = $iblockList->Fetch())
        {
            $arrIBlockList["REFERENCE"][] = "[".$iblock["ID"]."] ".$iblock["NAME"];
            $arrIBlockList["REFERENCE_ID"][] = $iblock["ID"];
        }

        return $arrIBlockList;
    }

    /* ��������� ���� �������� ��������� */
    public function getSections($iblockId)
    {
        $arrSectionsList = array();
        $sectionsList = CIBlockSection::GetList(array(
            "left_margin" => "asc"
        ), array(
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
                'IBLOCK_ID' => $iblockId
            ), false, array(
                'ID',
                'NAME',
                'DEPTH_LEVEL'
            ));
        while($section = $sectionsList->GetNext())
        {
            $arrSectionsList["REFERENCE"][] = "[".$section["ID"]."] ".str_repeat(" . ", $section["DEPTH_LEVEL"]).$section["NAME"];
            $arrSectionsList["REFERENCE_ID"][] = $section["ID"];
        }

        return $arrSectionsList;
    }

    /* ��������� ���� ������� */
    public function getAllProps($filter = array())
    {
        Loader::includeModule('iblock');
        if(isset($filter['ID']) && Loader::includeModule('catalog'))
        {
            $arOffer = CCatalogSku::GetInfoByOfferIBlock($filter['ID']);
            $arCatalog = CCatalogSku::GetInfoByProductIBlock($filter['ID']);

            if(is_array($arOffer))
            {
                $filter['ID'] = array(
                    $arOffer['IBLOCK_ID'],
                    $arOffer['PRODUCT_IBLOCK_ID']
                );
            }
            else if(is_array($arCatalog))
            {
                $filter['ID'] = array(
                    $arCatalog['IBLOCK_ID'],
                    $arCatalog['PRODUCT_IBLOCK_ID']
                );
            }
        }
        $resIblocks = Bitrix\Iblock\IblockTable::getList(array(
            'filter' => $filter,
            'select' => array('ID')
        ));
        $arIBlockList = array();

        while($arIblock = $resIblocks->fetch())
        {
            $arIBlockList[$arIblock['ID']] = true;
        }

        if(!empty($arIBlockList))
        {
            $arIBlockList = array_keys($arIBlockList);
            sort($arIBlockList);

            foreach($arIBlockList as $intIBlockID)
            {
                $iblockName = CIBlock::GetArrayByID($intIBlockID, 'NAME');
                $arrProps = array();
                $rsProps = CIBlockProperty::GetList(array(
                    "sort" => "asc",
                    "name" => "asc"
                ), array(
                    "ACTIVE" => "Y",
                    "IBLOCK_ID" => $intIBlockID
                ));

                while($arProp = $rsProps->Fetch())
                {
                    $arrProps['CondIBProp:'.$intIBlockID.':'.$arProp['ID']] = $arProp["NAME"]." [$arProp[ID]]";
                }
                $groups[$iblockName] = $arrProps;
            }

            return $groups;
        }

        return false;
    }

    /* ������ ��������� */
    public function startGeneration($condition)
    {
        $allCombs = self::getAllCombinations($condition['RULE'], $condition['LOGIC']);
        //printr($allCombs);

        if($condition['NAME_TEMPLATE'])
        {
            $conditionName = $condition['NAME_TEMPLATE'];
            $sections = unserialize($condition['SECTIONS']);
            // �������� ��������
            foreach($sections as $section)
            {
                $result = CIBlockSection::GetByID($section);
                if($sect = $result->Fetch())
                    $arrSect[$section] = $sect['NAME'];
            }
            $strSectIds = implode(" ", $sections);
            $strSectNames = implode(" ", $arrSect);
            preg_match_all('/\#(SECTION_ID|SECTION_NAME)\#/', $conditionName, $matches);
            if($matches[0])
            {
                $conditionName = str_replace("#SECTION_ID#", $strSectIds, $conditionName);
                $conditionName = str_replace("#SECTION_NAME#", $strSectNames, $conditionName);
            }
            preg_match_all('/\#(PROPERTY_ID|PROPERTY_NAME)\#/', $conditionName, $matches);
            if($matches[0])
            {
                $propFlag = true;
            }
        }
        else
        {
            $conditionName = $condition['NAME'].'_'; // �������� �� ���������
            $defaultName = true;
        }

        $data = array();
        $data['SITES'] = $condition['SITES'];
        $data['TYPE_OF_INFOBLOCK'] = $condition['TYPE_OF_INFOBLOCK'];
        $data['INFOBLOCK'] = $condition['INFOBLOCK'];
        $data['SECTIONS'] = $condition['SECTIONS'];
        $data['FILTER_TYPE'] = $condition['FILTER_TYPE'];
        $data['ACTIVE'] = $condition['ACTIVE'];
        $data['SEARCH'] = $condition['SEARCH'];
        $data['NO_INDEX'] = $condition['NO_INDEX'];
        $data['STRONG'] = $condition['STRICT'];
        $data['SORT'] = '100';
        $data['CATEGORY_ID'] = $condition['CATEGORY'];
        $metaOriginal = unserialize($condition['META']);
        $link = \Sotbit\Seometa\Helper\Link::getInstance();
        foreach($allCombs as $id => $combination)
        {
            //printr($combination);
            if($defaultName)
            {
                $data['NAME'] = $conditionName.++$id;
            }
            else
            {
                if($propFlag)
                {
                    $strPropIds = implode(" ", array_keys($combination['COMBINATION']['ONLY_NAMES']));
                    $strPropNames = implode(" ", $combination['COMBINATION']['ONLY_NAMES']);
                    $nameForEachCond = str_replace("#PROPERTY_ID#", $strPropIds, $conditionName);
                    $nameForEachCond = str_replace("#PROPERTY_NAME#", $strPropNames, $nameForEachCond);
                    $data['NAME'] = $nameForEachCond;
                }
                else
                {
                    $data['NAME'] = $conditionName;
                }
            }
            $meta = $metaOriginal;
            $meta['TAGS'] = $condition['TAGS'];
            foreach($meta as &$item)
            {
                if(preg_match_all("/{#PROPERTY_NAME#%\d+%(default|lower|upper)}/", $item, $matches))
                {
                    foreach($matches[0] as $pattern)
                    {
                        $divide = explode("%", $pattern);
                        if(!$combination['COMBINATION']['DETAILS'][$divide[1]])
                            continue;
                        $handler = substr($divide[2], 0, -1);
                        if($handler == "default")
                            $replacement = $combination['COMBINATION']['DETAILS'][$divide[1]]['NAME'];
                        else if($handler == "lower")
                            $replacement = strtolower($combination['COMBINATION']['DETAILS'][$divide[1]]['NAME']);
                        else
                            $replacement = strtoupper($combination['COMBINATION']['DETAILS'][$divide[1]]['NAME']);
                        $item = str_replace($pattern, $replacement, $item);
                    }
                }
                if(preg_match_all("/{#PROPERTY_VALUE#%\d+%(concat[,\/]|lower|upper)}/", $item, $matches))
                {
                    foreach($matches[0] as $pattern)
                    {
                        $divide = explode("%", $pattern);
                        if(!$combination['COMBINATION']['DETAILS'][$divide[1]])
                            continue;
                        $type = $combination['COMBINATION']['DETAILS'][$divide[1]]['TYPE'];
                        $code = $combination['COMBINATION']['DETAILS'][$divide[1]]['CODE'];
                        $handler = substr($divide[2], 0, -1);
                        $delimiter = '';
                        $find = strpos($handler, "concat");
                        if($find !== false)
                        {
                            $delimiter = ' "'.substr($handler, -1).' "';
                            $handler = substr($handler, 0, -1);
                        }
                        $replacement = '{='.$handler.' {='.$type.' "'.$code.'" }'.$delimiter.'}';
                        $item = str_replace($pattern, $replacement, $item);
                    }
                }
            }
            $data['TAG'] = $meta['TAGS'];
            unset($meta['TAGS']);
            $meta['TEMPLATE_NEW_URL'] = $condition['NEW_URL_TEMPLATE'];
            $data['META'] = serialize($meta);
            unset($combination['COMBINATION']);
            $data['RULE'] = serialize($combination);
            $data['DATE_CHANGE'] = new \Bitrix\Main\Type\DateTime();
            $resultAdd = \Sotbit\Seometa\ConditionTable::add($data);
            if($resultAdd->isSuccess())
            {
                if($condition['GENERATE_CHPU'] == "Y")
                {
                    $id = $resultAdd->getId();
                    $writer = \Sotbit\Seometa\Link\ChpuWriter::getWriterForAutogenerator($id);
                    $link->Generate($id, $writer);
                }
            }
        }
    }

    private function getAllCombinations($rule, $logic)
    {
        //printr($rule);
        $arrCombs = array(array());
        foreach($rule as $key => $values)
        {
            $append = array();
            foreach($arrCombs as $product)
            {
                foreach($values as $item)
                {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }
            $arrCombs = $append;
        }
        //printr($arrCombs);
        $propsInfo = self::getPropsInfo($arrCombs);
        foreach($arrCombs as $index => $vals)
        {
            $vals = array_values($vals);
            $arrChildren = array();
            foreach($vals as $k => $v)
            {
                $arrChildren[$k]['CLASS_ID'] = $v;
                $arrChildren[$k]['DATA']['logic'] = 'Equal';
                $arrChildren[$k]['DATA']['value'] = '';
            }
            $allCombs[$index] = array(
                'CLASS_ID' => 'CondGroup',
                'DATA' => array(
                    'All' => $logic,
                    'True' => 'True'
                ),
                'CHILDREN' => $arrChildren,
                'COMBINATION' => array(
                    'ONLY_NAMES' => $propsInfo['ONLY_NAMES'][$index],
                    'DETAILS' => $propsInfo['DETAILS'][$index]
                )
            );
        }

        return $allCombs;
    }

    private function getPropsInfo($arrCombs)
    {
        $rsProps = CIBlockProperty::GetList(array("id" => "asc"), array("ACTIVE" => "Y"));
        while($arProp = $rsProps->Fetch())
        {
            $type = "ProductProperty";
            if(Loader::includeModule('catalog'))
            {
                $arCatalog = CCatalogSku::GetInfoByOfferIBlock($arProp["IBLOCK_ID"]);
                if(is_array($arCatalog))
                {
                    $type = "OfferProperty";
                }
            }
            $arrProps[$arProp['ID']] = array(
                "NAME" => $arProp["NAME"],
                "CODE" => $arProp["CODE"],
                "TYPE" => $type
            );
        }
        foreach($arrCombs as $index => $vals)
        {
            foreach($vals as $key => $value)
            {
                $divide = explode(":", $value);
                $result["ONLY_NAMES"][$index][$divide[2]] = $arrProps[$divide[2]]["NAME"];
                $i = str_replace("BLOCK_WITH_PROPS_", "", $key);
                $result["DETAILS"][$index][$i] = array(
                    "ID" => $divide[2],
                    "NAME" => $arrProps[$divide[2]]["NAME"],
                    "CODE" => $arrProps[$divide[2]]["CODE"],
                    "TYPE" => $arrProps[$divide[2]]["TYPE"]
                );
            }
        }

        return $result;
    }

    // ������������ ���� ��� ������������� ����������
    public function getItemsForMetaMenu($iblock_id, $numberOfBlocks, $action_function, $menuID, $inputID)
    {
        $result = array();
        if($numberOfBlocks > 0)
        {
            $result["combination"] = array(
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION"),
                "MENU" => array(),
            );
            for($i = 1; $i <= $numberOfBlocks; $i++)
            {
                $result["combination"]["MENU"][] = array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION_BLOCK").$i,
                    "MENU" => array(
                        array(
                            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION_PROP_NAME"),
                            "ONCLICK" => "$action_function('{#PROPERTY_NAME#%".$i."%default}', '$menuID', '$inputID')",
                        ),
                        array(
                            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION_PROP_VALUE"),
                            "ONCLICK" => "$action_function('{#PROPERTY_VALUE#%".$i."%concat,}', '$menuID', '$inputID')",
                        ),
                    ),
                );
            }
        }
        $result["this"] = array(
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION"),
            "MENU" => array(
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_NAME"),
                    "ONCLICK" => "$action_function('{=this.Name}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_LOWER_NAME"),
                    "ONCLICK" => "$action_function('{=lower this.Name}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_CODE"),
                    "ONCLICK" => "$action_function('{=this.Code}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_PREVIEW_TEXT"),
                    "ONCLICK" => "$action_function('{=this.PreviewText}', '$menuID', '$inputID')",
                ),
            ),
        );
        $result["parent"] = array(
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT"),
            "MENU" => array(
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT_NAME"),
                    "ONCLICK" => "$action_function('{=parent.Name}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT_CODE"),
                    "ONCLICK" => "$action_function('{=parent.Code}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT_TEXT"),
                    "ONCLICK" => "$action_function('{=parent.PreviewText}', '$menuID', '$inputID')",
                ),
            ),
        );
        $result["iblock"] = array(
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK"),
            "MENU" => array(
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK_NAME"),
                    "ONCLICK" => "$action_function('{=iblock.Name}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK_CODE"),
                    "ONCLICK" => "$action_function('{=iblock.Code}', '$menuID', '$inputID')",
                ),
                array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK_TEXT"),
                    "ONCLICK" => "$action_function('{=iblock.PreviewText}', '$menuID', '$inputID')",
                ),
            ),
        );
        if($iblock_id > 0)
        {
            $result["properties"] = array(
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PROPERTIES"),
                "MENU" => array(),
            );
            $props = array();
            $rsProperty = CIBlockProperty::GetList(array("name" => "asc"), array("IBLOCK_ID" => $iblock_id));
            while($prop = $rsProperty->fetch())
            {
                $props[] = $prop;
            }
            foreach($props as $property)
            {
                if($property["PROPERTY_TYPE"] != "F")
                {
                    $result["properties"]["MENU"][] = array(
                        "TEXT" => $property["NAME"],
                        "ONCLICK" => "$action_function('{=concat {=ProductProperty \"".($property["CODE"] != "" ? $property["CODE"] : $property["ID"])."\" } \", \"}', '$menuID', '$inputID')",
                    );
                }
            }
        }
        if(Loader::includeModule('catalog'))
        {
            if($iblock_id > 0)
                $arCatalog = CCatalogSku::GetInfoByIBlock($iblock_id);
            if(is_array($arCatalog))
            {
                $result["sku_properties"] = array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SKU_PROPERTIES"),
                    "MENU" => array(),
                );
                $rsProperty = CIBlockProperty::GetList(array("name" => "asc"), array("IBLOCK_ID" => $arCatalog["IBLOCK_ID"]));
                while($property = $rsProperty->fetch())
                {
                    if($property["PROPERTY_TYPE"] != "F")
                    {
                        $result["sku_properties"]["MENU"][] = array(
                            "TEXT" => $property["NAME"],
                            "ONCLICK" => "$action_function('{=concat {=OfferProperty \"".($property["CODE"] != "" ? $property["CODE"] : $property["ID"])."\" } \", \"}', '$menuID', '$inputID')",
                        );
                    }
                }
                $result["price"] = array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES"),
                    "MENU" => array(),
                );
                $priceTypes = CCatalogGroup::GetListArray();
                foreach($priceTypes as $price)
                {
                    $result["price"]["MENU"][] = array(
                        "TEXT" => $price["NAME_LANG"].' ['.$price["NAME"].']',
                        "MENU" => array(
                            array(
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MIN"),
                                "ONCLICK" => "$action_function('{=Price \"MIN\" \"".$price['NAME']."\"}', '$menuID', '$inputID')",
                            ),
                            array(
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MAX"),
                                "ONCLICK" => "$action_function('{=Price \"MAX\" \"".$price['NAME']."\"}', '$menuID', '$inputID')",
                            ),
                            array(
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MIN_FILTER"),
                                "ONCLICK" => "$action_function('{=Price \"MIN_FILTER\" \"".$price['NAME']."\"}', '$menuID', '$inputID')",
                            ),
                            array(
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MAX_FILTER"),
                                "ONCLICK" => "$action_function('{=Price \"MAX_FILTER\" \"".$price['NAME']."\"}', '$menuID', '$inputID')",
                            ),
                        ),
                    );
                }
                $result["store"] = array(
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_STORE"),
                    "MENU" => array(),
                );
                $params = array(
                    'select' => array(
                        'ID',
                        'TITLE',
                        'ADDRESS',
                        'SORT'
                    ),
                    'order' => array('SORT' => 'ASC')
                );
                $stores = array();
                $storeIterator = \Bitrix\Catalog\StoreTable::getList($params);
                while($store = $storeIterator->fetch())
                {
                    $stores[] = $store;
                }
                foreach($stores as $store)
                {
                    $result["store"]["MENU"][] = array(
                        "TEXT" => ($store["TITLE"] != '' ? $store["TITLE"] : $store["ADDRESS"]),
                        "ONCLICK" => "$action_function('{=catalog.store.".$store["ID"].".name}', '$menuID', '$inputID')",
                    );
                }
            }
        }
        $result["misc"] = array(
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_MISC"),
            "MENU" => array(),
        );
        $result["misc"]["MENU"][] = array(
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTIONS_PATH"),
            "ONCLICK" => "$action_function('{=concat this.sections.name \" / \"}', '$menuID', '$inputID')",
        );
        if(Loader::includeModule('catalog'))
        {
            $result["misc"]["MENU"][] = array(
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_STORE_LIST"),
                "ONCLICK" => "$action_function('{=concat catalog.store \", \"}', '$menuID', '$inputID')",
            );
        }
        $result["user_fields"] = array(
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_USER_FIELDS"),
            "MENU" => array(),
        );
        $rsUserFields = CUserTypeEntity::GetList(array("name" => "asc"), array());
        while($userField = $rsUserFields->fetch())
        {
            $result["user_fields"]["MENU"][] = array(
                "TEXT" => "[".$userField["ID"]."] [".$userField["ENTITY_ID"]."] ".$userField["FIELD_NAME"],
                "ONCLICK" => "$action_function('#".$userField["FIELD_NAME"]."#', '$menuID', '$inputID')",
            );
        }
        if(Loader::includeModule('sotbit.regions') && !\SotbitRegions::isDemoEnd())
        {
            $result["sotbit_regions"] = array(
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_REGIONS"),
                "MENU" => array(),
            );
            $tags = SotbitRegions::getTags();
            foreach($tags as $tag)
            {
                $result["sotbit_regions"]["MENU"][] = array(
                    "TEXT" => $tag["NAME"],
                    "ONCLICK" => "$action_function('".SotbitRegions::genCodeVariable($tag['CODE'])."', '$menuID', '$inputID')",
                );
            }
        }
        $res = array();
        foreach($result as $category)
        {
            if(!empty($category) && !empty($category["MENU"]))
            {
                $res[] = $category;
            }
        }

        return $res;
    }
}
