<?

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Link\ChpuWriter;

class CSeoMetaAutogenerator
{
    public function getSites(
    ) {
        $arrSitesList = [];
        $sitesList = SiteTable::getList();
        while ($site = $sitesList->Fetch()) {
            $arrSitesList[] = $site['LID'];
        }

        return $arrSitesList;
    }

    public function getIBlockTypes(
    ) {
        $arrIBlockTypes = [];
        if (!Loader::includeModule('iblock')) {
            return $arrIBlockTypes;
        }

        $iblockTypes = CIBlockParameters::GetIBlockTypes();
        foreach ($iblockTypes as $id => $name) {
            $arrIBlockTypes["REFERENCE"][] = $name;
            $arrIBlockTypes["REFERENCE_ID"][] = $id;
        }

        return $arrIBlockTypes;
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

    public function getAllProps($filter = [], $typeInfoBlock = null) {
        if(empty($typeInfoBlock)){
            return false;
        }
        if(Loader::includeModule('iblock')) {
            if (isset($filter['ID']) && Loader::includeModule('catalog') && class_exists('CCatalogSku')) {
                $arOffer = CCatalogSku::GetInfoByOfferIBlock($filter['ID']);
                $arCatalog = CCatalogSku::GetInfoByProductIBlock($filter['ID']);
                if (is_array($arOffer)) {
                    $filter['ID'] = [
                        $arOffer['IBLOCK_ID'],
                        $arOffer['PRODUCT_IBLOCK_ID']
                    ];
                } elseif (is_array($arCatalog)) {
                    $filter['ID'] = [
                        $arCatalog['IBLOCK_ID'],
                        $arCatalog['PRODUCT_IBLOCK_ID']
                    ];
                }
            }

            $resIblocks = Bitrix\Iblock\IblockTable::getList([
                'filter' => $filter,
                'select' => ['ID']
            ]);
            $arIBlockList = [];
            while ($arIblock = $resIblocks->fetch()) {
                $arIBlockList[$arIblock['ID']] = true;
            }

            if (!empty($arIBlockList)) {
                $groups = [];
                $arIBlockList = array_keys($arIBlockList);
                sort($arIBlockList);
                foreach ($arIBlockList as $intIBlockID) {
                    $iblockName = CIBlock::GetArrayByID($intIBlockID, 'NAME');
                    $arrProps = [];
                    $rsProps = CIBlockProperty::GetList(
                        [
                            "sort" => "asc",
                            "name" => "asc"
                        ],
                        [
                            "ACTIVE" => "Y",
                            "IBLOCK_ID" => $intIBlockID
                        ]);

                    while ($arProp = $rsProps->Fetch()) {
                        $arrProps['CondIBProp:' . $intIBlockID . ':' . $arProp['ID']] = $arProp["NAME"] . " [$arProp[ID]]";
                    }

                    $groups[$iblockName] = $arrProps;
                }

                return $groups;
            }
        }

        return false;
    }

    public function startGeneration($condition) {
        $allCombs = self::getAllCombinations($condition['RULE'], $condition['LOGIC']);
        if ($condition['NAME_TEMPLATE']) {
            $conditionName = $condition['NAME_TEMPLATE'];
            $sections = unserialize($condition['SECTIONS']);
            foreach ($sections as $section) {
                $result = CIBlockSection::GetByID($section);
                if ($sect = $result->Fetch()) {
                    $arrSect[$section] = $sect['NAME'];
                }
            }

            $strSectIds = implode(" ", $sections);
            $strSectNames = implode(" ", $arrSect);
            preg_match_all('/\#(SECTION_ID|SECTION_NAME)\#/', $conditionName, $matches);
            if ($matches[0]) {
                $conditionName = str_replace("#SECTION_ID#", $strSectIds, $conditionName);
                $conditionName = str_replace("#SECTION_NAME#", $strSectNames, $conditionName);
            }

            preg_match_all('/\#(PROPERTY_ID|PROPERTY_NAME)\#/', $conditionName, $matches);
            if ($matches[0]) {
                $propFlag = true;
            }
        } else {
            $conditionName = $condition['NAME'] . '_';
            $defaultName = true;
        }

        $data = [];
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
        $data['GENERATE_AJAX'] = 'N';
        $metaOriginal = unserialize($condition['META']);
        foreach ($allCombs as $id => $combination) {
            if ($defaultName) {
                $data['NAME'] = $conditionName . ++$id;
            } elseif ($propFlag) {
                $strPropIds = implode(" ", array_keys($combination['COMBINATION']['ONLY_NAMES']));
                $strPropNames = implode(" ", $combination['COMBINATION']['ONLY_NAMES']);
                $nameForEachCond = str_replace("#PROPERTY_ID#", $strPropIds, $conditionName);
                $nameForEachCond = str_replace("#PROPERTY_NAME#", $strPropNames, $nameForEachCond);
                $data['NAME'] = $nameForEachCond;
            } else {
                $data['NAME'] = $conditionName;
            }

            $meta = $metaOriginal;
            $meta['TAGS'] = $condition['TAGS'];
            foreach ($meta as &$item) {
                if (preg_match_all("/{#PROPERTY_NAME#%\d+%(default|lower|upper)}/", $item, $matches)) {
                    foreach ($matches[0] as $pattern) {
                        $divide = explode("%", $pattern);
                        if (!$combination['COMBINATION']['DETAILS'][$divide[1]]) {
                            continue;
                        }

                        $handler = mb_substr($divide[2], 0, -1);
                        if ($handler == "default") {
                            $replacement = $combination['COMBINATION']['DETAILS'][$divide[1]]['NAME'];
                        } elseif ($handler == "lower") {
                            $replacement = mb_strtolower($combination['COMBINATION']['DETAILS'][$divide[1]]['NAME']);
                        } else {
                            $replacement = mb_strtoupper($combination['COMBINATION']['DETAILS'][$divide[1]]['NAME']);
                        }

                        $item = str_replace($pattern, $replacement, $item);
                    }
                }

                if (preg_match_all("/{#PROPERTY_VALUE#%\d+%(concat[,\/]|lower|upper)}/", $item, $matches)) {
                    foreach ($matches[0] as $pattern) {
                        $divide = explode("%", $pattern);
                        if (!$combination['COMBINATION']['DETAILS'][$divide[1]]) {
                            continue;
                        }

                        $type = $combination['COMBINATION']['DETAILS'][$divide[1]]['TYPE'];
                        $code = $combination['COMBINATION']['DETAILS'][$divide[1]]['CODE'];
                        $handler = mb_substr($divide[2], 0, -1);
                        $delimiter = '';
                        $find = mb_strpos($handler, "concat");
                        if ($find !== false) {
                            $delimiter = ' "' . mb_substr($handler, -1) . ' "';
                            $handler = mb_substr($handler, 0, -1);
                        }

                        $replacement = '{=' . $handler . ' {=' . $type . ' "' . $code . '" }' . $delimiter . '}';
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
            $resultAdd = ConditionTable::add($data);
            if ($resultAdd->isSuccess()) {
                if ($condition['GENERATE_CHPU'] == "Y") {
                    $id = $resultAdd->getId();
                    $writer = ChpuWriter::getWriterForAutogenerator($id);
                    $link = Linker::getLinkerForAutogenerator();
                    $link->Generate($writer, $id);
                }
            }
        }
    }

    private function getAllCombinations(
        $rule,
        $logic
    ) {
        $allCombs = [];
        $arrCombs = [[]];
        foreach ($rule as $key => $values) {
            $append = [];
            foreach ($arrCombs as $product) {
                foreach ($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }
            $arrCombs = $append;
        }
        $propsInfo = self::getPropsInfo($arrCombs);
        foreach ($arrCombs as $index => $vals) {
            $vals = array_values($vals);
            $arrChildren = array();
            foreach ($vals as $k => $v) {
                $arrChildren[$k]['CLASS_ID'] = $v;
                $arrChildren[$k]['DATA']['logic'] = 'Equal';
                $arrChildren[$k]['DATA']['value'] = '';
            }

            $allCombs[$index] = [
                'CLASS_ID' => 'CondGroup',
                'DATA' => [
                    'All' => $logic,
                    'True' => 'True'
                ],
                'CHILDREN' => $arrChildren,
                'COMBINATION' => [
                    'ONLY_NAMES' => $propsInfo['ONLY_NAMES'][$index],
                    'DETAILS' => $propsInfo['DETAILS'][$index]
                ]
            ];
        }

        return $allCombs;
    }

    private function getPropsInfo(
        $arrCombs
    ) {
        $rsProps = CIBlockProperty::GetList(
            ["id" => "asc"],
            ["ACTIVE" => "Y"]
        );
        while ($arProp = $rsProps->Fetch()) {
            $type = "ProductProperty";
            if (Loader::includeModule('catalog') && class_exists('CCatalogSku')) {
                $arCatalog = CCatalogSku::GetInfoByOfferIBlock($arProp["IBLOCK_ID"]);
                if (is_array($arCatalog)) {
                    $type = "OfferProperty";
                }
            }

            $arrProps[$arProp['ID']] = [
                "NAME" => $arProp["NAME"],
                "CODE" => $arProp["CODE"],
                "TYPE" => $type
            ];
        }

        foreach ($arrCombs as $index => $vals) {
            foreach ($vals as $key => $value) {
                $divide = explode(":", $value);
                $result["ONLY_NAMES"][$index][$divide[2]] = $arrProps[$divide[2]]["NAME"];
                $i = str_replace("BLOCK_WITH_PROPS_", "", $key);
                $result["DETAILS"][$index][$i] = [
                    "ID" => $divide[2],
                    "NAME" => $arrProps[$divide[2]]["NAME"],
                    "CODE" => $arrProps[$divide[2]]["CODE"],
                    "TYPE" => $arrProps[$divide[2]]["TYPE"]
                ];
            }
        }

        return $result;
    }

    public function getItemsForMetaMenu(
        $iblock_id,
        $numberOfBlocks,
        $action_function,
        $menuID,
        $inputID
    ) {
        $result = [];
        if ($numberOfBlocks > 0) {
            $result["combination"] = [
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION"),
                "MENU" => [],
            ];
            for ($i = 1; $i <= $numberOfBlocks; $i++) {
                $result["combination"]["MENU"][] = [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION_BLOCK") . $i,
                    "MENU" => [
                        [
                            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION_PROP_NAME"),
                            "ONCLICK" => "$action_function('{#PROPERTY_NAME#%" . $i . "%default}', '$menuID', '$inputID')",
                        ],
                        [
                            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_COMBINATION_PROP_VALUE"),
                            "ONCLICK" => "$action_function('{#PROPERTY_VALUE#%" . $i . "%concat,}', '$menuID', '$inputID')",
                        ],
                    ],
                ];
            }
        }

        $result["this"] = [
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION"),
            "MENU" => [
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_NAME"),
                    "ONCLICK" => "$action_function('{=this.Name}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_LOWER_NAME"),
                    "ONCLICK" => "$action_function('{=lower this.Name}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_CODE"),
                    "ONCLICK" => "$action_function('{=this.Code}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTION_PREVIEW_TEXT"),
                    "ONCLICK" => "$action_function('{=this.PreviewText}', '$menuID', '$inputID')",
                ],
            ],
        ];
        $result["parent"] = [
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT"),
            "MENU" => [
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT_NAME"),
                    "ONCLICK" => "$action_function('{=parent.Name}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT_CODE"),
                    "ONCLICK" => "$action_function('{=parent.Code}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PARENT_TEXT"),
                    "ONCLICK" => "$action_function('{=parent.PreviewText}', '$menuID', '$inputID')",
                ],
            ],
        ];
        $result["iblock"] = [
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK"),
            "MENU" => [
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK_NAME"),
                    "ONCLICK" => "$action_function('{=iblock.Name}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK_CODE"),
                    "ONCLICK" => "$action_function('{=iblock.Code}', '$menuID', '$inputID')",
                ],
                [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_IBLOCK_TEXT"),
                    "ONCLICK" => "$action_function('{=iblock.PreviewText}', '$menuID', '$inputID')",
                ],
            ],
        ];

        if ($iblock_id > 0) {
            $result["properties"] = [
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PROPERTIES"),
                "MENU" => [],
            ];
            $props = [];
            $rsProperty = CIBlockProperty::GetList(
                ["name" => "asc"],
                ["IBLOCK_ID" => $iblock_id]
            );
            while ($prop = $rsProperty->fetch()) {
                $props[] = $prop;
            }

            foreach ($props as $property) {
                if ($property["PROPERTY_TYPE"] != "F") {
                    $result["properties"]["MENU"][] = [
                        "TEXT" => $property["NAME"],
                        "ONCLICK" => "$action_function('{=concat {=ProductProperty \"" . ($property["CODE"] != "" ? $property["CODE"] : $property["ID"]) . "\" } \", \"}', '$menuID', '$inputID')",
                    ];
                }
            }
        }

        if (Loader::includeModule('catalog')) {
            if ($iblock_id > 0 && class_exists('CCatalogSku')) {
                $arCatalog = CCatalogSku::GetInfoByIBlock($iblock_id);
            }

            if (is_array($arCatalog)) {
                $result["sku_properties"] = [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SKU_PROPERTIES"),
                    "MENU" => [],
                ];
                $rsProperty = CIBlockProperty::GetList(
                    ["name" => "asc"],
                    ["IBLOCK_ID" => $arCatalog["IBLOCK_ID"]]
                );
                while ($property = $rsProperty->fetch()) {
                    if ($property["PROPERTY_TYPE"] != "F") {
                        $result["sku_properties"]["MENU"][] = [
                            "TEXT" => $property["NAME"],
                            "ONCLICK" => "$action_function('{=concat {=OfferProperty \"" . ($property["CODE"] != "" ? $property["CODE"] : $property["ID"]) . "\" } \", \"}', '$menuID', '$inputID')",
                        ];
                    }
                }

                $result["price"] = [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES"),
                    "MENU" => [],
                ];
                $priceTypes = CCatalogGroup::GetListArray();
                foreach ($priceTypes as $price) {
                    $result["price"]["MENU"][] = [
                        "TEXT" => $price["NAME_LANG"] . ' [' . $price["NAME"] . ']',
                        "MENU" => [
                            [
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MIN"),
                                "ONCLICK" => "$action_function('{=Price \"MIN\" \"" . $price['NAME'] . "\"}', '$menuID', '$inputID')",
                            ],
                            [
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MAX"),
                                "ONCLICK" => "$action_function('{=Price \"MAX\" \"" . $price['NAME'] . "\"}', '$menuID', '$inputID')",
                            ],
                            [
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MIN_FILTER"),
                                "ONCLICK" => "$action_function('{=Price \"MIN_FILTER\" \"" . $price['NAME'] . "\"}', '$menuID', '$inputID')",
                            ],
                            [
                                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_PRICES_MAX_FILTER"),
                                "ONCLICK" => "$action_function('{=Price \"MAX_FILTER\" \"" . $price['NAME'] . "\"}', '$menuID', '$inputID')",
                            ],
                        ],
                    ];
                }

                $result["store"] = [
                    "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_STORE"),
                    "MENU" => [],
                ];
                $params = [
                    'select' => [
                        'ID',
                        'TITLE',
                        'ADDRESS',
                        'SORT'
                    ],
                    'order' => ['SORT' => 'ASC']
                ];
                $stores = [];
                $storeIterator = StoreTable::getList($params);
                while ($store = $storeIterator->fetch()) {
                    $stores[] = $store;
                }

                foreach ($stores as $store) {
                    $result["store"]["MENU"][] = [
                        "TEXT" => $store["TITLE"]?: $store["ADDRESS"],
                        "ONCLICK" => "$action_function('{=catalog.store." . $store["ID"] . ".name}', '$menuID', '$inputID')",
                    ];
                }
            }
        }
        $result["misc"] = [
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_MISC"),
            "MENU" => [],
        ];
        $result["misc"]["MENU"][] = [
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_SECTIONS_PATH"),
            "ONCLICK" => "$action_function('{=concat this.sections.name \" / \"}', '$menuID', '$inputID')",
        ];
        if (Loader::includeModule('catalog')) {
            $result["misc"]["MENU"][] = [
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_STORE_LIST"),
                "ONCLICK" => "$action_function('{=concat catalog.store \", \"}', '$menuID', '$inputID')",
            ];
        }
        $result["user_fields"] = [
            "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_USER_FIELDS"),
            "MENU" => [],
        ];
        $rsUserFields = CUserTypeEntity::GetList(
            ["name" => "asc"],
            []
        );
        while ($userField = $rsUserFields->fetch()) {
            $result["user_fields"]["MENU"][] = [
                "TEXT" => "[" . $userField["ID"] . "] [" . $userField["ENTITY_ID"] . "] " . $userField["FIELD_NAME"],
                "ONCLICK" => "$action_function('#" . $userField["FIELD_NAME"] . "#', '$menuID', '$inputID')",
            ];
        }

        if (Loader::includeModule('sotbit.regions') && !\SotbitRegions::isDemoEnd()) {
            $result["sotbit_regions"] = [
                "TEXT" => Loc::getMessage("MENU_META_TEMPLATE_POPUP_REGIONS"),
                "MENU" => [],
            ];
            $tags = SotbitRegions::getTags();
            foreach ($tags as $tag) {
                $result["sotbit_regions"]["MENU"][] = [
                    "TEXT" => $tag["NAME"],
                    "ONCLICK" => "$action_function('" . SotbitRegions::genCodeVariable($tag['CODE']) . "', '$menuID', '$inputID')",
                ];
            }
        }

        $res = [];
        foreach ($result as $category) {
            if (!empty($category) && !empty($category["MENU"])) {
                $res[] = $category;
            }
        }

        return $res;
    }
}
