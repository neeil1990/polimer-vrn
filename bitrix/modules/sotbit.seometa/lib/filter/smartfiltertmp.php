<?
namespace Sotbit\Seometa\Filter;

use Bitrix\Main\Loader;

class SmartFilterTmp extends \CBitrixComponent {
    public $IBLOCK_ID = 0;
    public $SKU_IBLOCK_ID = 0;
    public $SKU_PROPERTY_ID = 0;
    public $SECTION_ID = 0;
    public $FILTER_NAME = "";
    public $SAFE_FILTER_NAME = "";
    public $convertCurrencyId = "";

    protected $currencyTagList = array();
    protected $currencyCache = array();
    protected $cache = array();
    protected static $catalogIncluded = null;
    protected static $iblockIncluded = null;
    /** @var \Bitrix\Iblock\PropertyIndex\Facet * */
    protected $facet = null;
    public $__bInited = true;
    public function onPrepareComponentParams($arParams)
    {
        $arParams = [
            'IBLOCK_TYPE' => 'sotbit_origami_catalog',
            'IBLOCK_ID' => 8,
            'SECTION_ID' => 27,
            'FILTER_NAME' => 'arrFilter',
            'PRICE_CODE' => [
                'BASE',
                'OPT',
                'SMALL_OPT'
            ],
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => 36000000,
            'CACHE_GROUPS' => 1,
            'SAVE_IN_SESSION' => false,
            'FILTER_VIEW_MODE' => 'VERTICAL',
            'XML_EXPORT' => 'N',
            'SHOW_ALL_WO_SECTION' => false,
            'SECTION_TITLE' => 'NAME',
            'SECTION_DESCRIPTION' => 'DESCRIPTION',
            'HIDE_NOT_AVAILABLE' => 'L',
            'TEMPLATE_THEME' => 'site',
            'CONVERT_CURRENCY' => false,
            'CURRENCY_ID' => false,
            'SEF_MODE' => 'Y',
            'SEF_RULE' => '/catalog/#SECTION_CODE_PATH#/filter/#SMART_FILTER_PATH#/apply/',
            'SMART_FILTER_PATH' => '',
            'PAGER_PARAMS_NAME' => 'arrPager',
            'INSTANT_RELOAD' => false,
            'DISPLAY_ELEMENT_COUNT' => 'Y',
            'FILTER_MODE' => 'AJAX_MODE',
            'PREFILTER_NAME' => 'smartPreFilter',
        ];


        $arParams["CACHE_TIME"] = isset($arParams["CACHE_TIME"]) ? $arParams["CACHE_TIME"]: 36000000;
        $arParams["IBLOCK_ID"] = (int)$arParams["IBLOCK_ID"];
        $arParams["SECTION_ID"] = (int)$arParams["SECTION_ID"];
        if ($arParams["SECTION_ID"] <= 0 && Loader::includeModule('iblock'))
        {
            $arParams["SECTION_ID"] = \CIBlockFindTools::GetSectionID(
                $arParams["SECTION_ID"],
                $arParams["SECTION_CODE"],
                array(
                    "GLOBAL_ACTIVE" => "Y",
                    "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                )
            );
            if (!$arParams["SECTION_ID"] && mb_strlen($arParams["SECTION_CODE_PATH"]) > 0)
            {
                $arParams["SECTION_ID"] = \CIBlockFindTools::GetSectionIDByCodePath(
                    $arParams["IBLOCK_ID"],
                    $arParams["SECTION_CODE_PATH"]
                );
            }
        }

        $arParams["PRICE_CODE"] = is_array($arParams["PRICE_CODE"])? $arParams["PRICE_CODE"]: array();
        foreach ($this->arParams["PRICE_CODE"] as $k=>$v)
        {
            if ($v===null || $v==='' || $v===false)
                unset($arParams["PRICE_CODE"][$k]);
        }

        $arParams["SAVE_IN_SESSION"] = $arParams["SAVE_IN_SESSION"] == "Y";
        $arParams["CACHE_GROUPS"] = $arParams["CACHE_GROUPS"] !== "N";
        $arParams["INSTANT_RELOAD"] = $arParams["INSTANT_RELOAD"] === "Y";
        $arParams["SECTION_TITLE"] = trim($arParams["SECTION_TITLE"]);
        $arParams["SECTION_DESCRIPTION"] = trim($arParams["SECTION_DESCRIPTION"]);

        $arParams["FILTER_NAME"] = (isset($arParams["FILTER_NAME"]) ? (string)$arParams["FILTER_NAME"] : '');
        if(
            $arParams["FILTER_NAME"] == ''
            || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])
        )
        {
            $arParams["FILTER_NAME"] = "arrFilter";
        }
        $arParams["PREFILTER_NAME"] = (isset($arParams["PREFILTER_NAME"]) ? (string)$arParams["PREFILTER_NAME"] : '');
        if(
            $arParams["PREFILTER_NAME"] == ''
            || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PREFILTER_NAME"])
        )
        {
            $arParams["PREFILTER_NAME"] = "smartPreFilter";
        }

        $arParams["CONVERT_CURRENCY"] = $arParams["CONVERT_CURRENCY"] === "Y";
        $arParams["CURRENCY_ID"] = trim($arParams["CURRENCY_ID"]);
        if ($arParams["CURRENCY_ID"] == "")
        {
            $arParams["CONVERT_CURRENCY"] = false;
        }
        elseif (!$arParams["CONVERT_CURRENCY"])
        {
            $arParams["CURRENCY_ID"] = "";
        }

        return $arParams;
    }

    public function executeComponent()
    {
        $this->__bInited = true;
        $this->IBLOCK_ID = $this->arParams["IBLOCK_ID"];
        $this->SECTION_ID = $this->arParams["SECTION_ID"];
        $this->FILTER_NAME = $this->arParams["FILTER_NAME"];
        $this->SAFE_FILTER_NAME = htmlspecialcharsbx($this->FILTER_NAME);

        if (
            $this->arParams["CONVERT_CURRENCY"]
            && $this->arParams["CURRENCY_ID"] != ""
            && Loader::includeModule('currency')
        )
        {
            $currencyList = \Bitrix\Currency\CurrencyTable::getList(array(
                'select' => array('CURRENCY'),
                'filter' => array('=CURRENCY' => $this->arParams['CURRENCY_ID'])
            ));
            if ($currency = $currencyList->fetch())
                $this->convertCurrencyId = $currency['CURRENCY'];
            unset($currency);
            unset($currencyList);
        }

        if (self::$iblockIncluded === null)
            self::$iblockIncluded = Loader::includeModule('iblock');
        if (!self::$iblockIncluded)
            return '';

        if (self::$catalogIncluded === null)
            self::$catalogIncluded = Loader::includeModule('catalog');
        if (self::$catalogIncluded && class_exists('CCatalogSku'))
        {
            $arCatalog = \CCatalogSKU::GetInfoByProductIBlock($this->IBLOCK_ID);
            if (!empty($arCatalog))
            {
                $this->SKU_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
                $this->SKU_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
            }
        }

        $this->facet = new \Bitrix\Iblock\PropertyIndex\Facet($this->IBLOCK_ID);

        return $this->execute();
    }
    
    public function execute() {
        global $USER, $APPLICATION;
        $FILTER_NAME = (string)$this->arParams["FILTER_NAME"];
        $PREFILTER_NAME = (string)$this->arParams["PREFILTER_NAME"];

        global ${$PREFILTER_NAME};
        $preFilter = ${$PREFILTER_NAME};
        if (!is_array($preFilter))
            $preFilter = array();

        if(true || $this->StartResultCache(false, array('v10', $preFilter, ($this->arParams["CACHE_GROUPS"]? $USER->GetGroups(): false))))
        {
            $this->arResult["FACET_FILTER"] = false;
            $this->arResult["COMBO"] = array();
            $this->arResult["PRICES"] = \CIBlockPriceTools::GetCatalogPrices($this->arParams["IBLOCK_ID"], $this->arParams["PRICE_CODE"]);
            $this->arResult["ITEMS"] = $this->getResultItems();
            $this->arResult["CURRENCIES"] = array();

            $propertyEmptyValuesCombination = array();
            foreach($this->arResult["ITEMS"] as $PID => $arItem)
                $propertyEmptyValuesCombination[$arItem["ID"]] = array();

            if(!empty($this->arResult["ITEMS"]))
            {
                if ($this->facet->isValid())
                {
                    $this->facet->setPrices($this->arResult["PRICES"]);
                    $this->facet->setSectionId($this->SECTION_ID);
                    $this->arResult["FACET_FILTER"] = array(
                        "ACTIVE_DATE" => "Y",
                        "CHECK_PERMISSIONS" => "Y",
                    );
                    if ($this->arParams['HIDE_NOT_AVAILABLE'] == 'Y')
                        $this->arResult["FACET_FILTER"]['AVAILABLE'] = 'Y';
                    if (!empty($preFilter))
                        $this->arResult["FACET_FILTER"] = array_merge($preFilter, $this->arResult["FACET_FILTER"]);

                    $cntProperty = 0;
                    $tmpProperty = array();
                    $dictionaryID = array();
                    $elementDictionary = array();
                    $sectionDictionary = array();
                    $directoryPredict = array();

                    $res = $this->facet->query($this->arResult["FACET_FILTER"]);
                    \CTimeZone::Disable();
                    while ($rowData = $res->fetch())
                    {
                        $facetId = $rowData["FACET_ID"];
                        if (\Bitrix\Iblock\PropertyIndex\Storage::isPropertyId($facetId))
                        {
                            $PID = \Bitrix\Iblock\PropertyIndex\Storage::facetIdToPropertyId($facetId);
                            if (!array_key_exists($PID, $this->arResult["ITEMS"]))
                                continue;
                            ++$cntProperty;

                            $rowData['PID'] = $PID;
                            $tmpProperty[] = $rowData;
                            $item = $this->arResult["ITEMS"][$PID];
                            $arUserType = \CIBlockProperty::GetUserType($item['USER_TYPE']);

                            if ($item["PROPERTY_TYPE"] == "S")
                            {
                                $dictionaryID[] = $rowData["VALUE"];
                            }

                            if ($item["PROPERTY_TYPE"] == "E" && $item['USER_TYPE'] == '')
                            {
                                $elementDictionary[] = $rowData['VALUE'];
                            }

                            if ($item["PROPERTY_TYPE"] == "G" && $item['USER_TYPE'] == '')
                            {
                                $sectionDictionary[] = $rowData['VALUE'];
                            }

                            if ($item['USER_TYPE'] == 'directory' && isset($arUserType['GetExtendedValue']))
                            {
                                $tableName = $item['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                $directoryPredict[$tableName]['PROPERTY'] = array(
                                    'PID' => $item['ID'],
                                    'USER_TYPE_SETTINGS' => $item['USER_TYPE_SETTINGS'],
                                    'GetExtendedValue' => $arUserType['GetExtendedValue'],
                                );
                                $directoryPredict[$tableName]['VALUE'][] = $rowData["VALUE"];
                            }
                        }
                        else
                        {
                            $priceId = \Bitrix\Iblock\PropertyIndex\Storage::facetIdToPriceId($facetId);
                            foreach($this->arResult["PRICES"] as $NAME => $arPrice)
                            {
                                if ($arPrice["ID"] == $priceId && isset($this->arResult["ITEMS"][$NAME]))
                                {
                                    $this->fillItemPrices($this->arResult["ITEMS"][$NAME], $rowData);

                                    if (isset($this->arResult["ITEMS"][$NAME]["~CURRENCIES"]))
                                    {
                                        $this->arResult["CURRENCIES"] += $this->arResult["ITEMS"][$NAME]["~CURRENCIES"];
                                    }

                                    if ($rowData["VALUE_FRAC_LEN"] > 0)
                                    {
                                        $this->arResult["ITEMS"][$PID]["DECIMALS"] = $rowData["VALUE_FRAC_LEN"];
                                    }
                                }
                            }
                        }

                        if ($cntProperty > 200)
                        {
                            $this->predictIBElementFetch($elementDictionary);
                            $this->predictIBSectionFetch($sectionDictionary);
                            $this->processProperties($this->arResult, $tmpProperty, $dictionaryID, $directoryPredict);
                            $cntProperty = 0;
                            $tmpProperty = array();
                            $dictionaryID = array();
                            $lookupDictionary = array();
                            $directoryPredict = array();
                            $elementDictionary = array();
                            $sectionDictionary = array();
                        }
                    }

                    $this->predictIBElementFetch($elementDictionary);
                    $this->predictIBSectionFetch($sectionDictionary);
                    $this->processProperties($this->arResult, $tmpProperty, $dictionaryID, $directoryPredict);
                    \CTimeZone::Enable();
                }
                else
                {
                    $arElementFilter = array(
                        "IBLOCK_ID" => $this->IBLOCK_ID,
                        "SUBSECTION" => $this->SECTION_ID,
                        "SECTION_SCOPE" => "IBLOCK",
                        "ACTIVE_DATE" => "Y",
                        "ACTIVE" => "Y",
                        "CHECK_PERMISSIONS" => "Y",
                    );
                    if ('Y' == $this->arParams['HIDE_NOT_AVAILABLE'])
                        $arElementFilter['AVAILABLE'] = 'Y';
                    if (!empty($preFilter))
                        $arElementFilter = array_merge($preFilter, $arElementFilter);

                    $arElements = array();

                    if (!empty($this->arResult["PROPERTY_ID_LIST"]))
                    {
                        $rsElements = \CIBlockElement::GetPropertyValues($this->IBLOCK_ID, $arElementFilter, false, array('ID' => $this->arResult["PROPERTY_ID_LIST"]));
                        while($arElement = $rsElements->Fetch())
                            $arElements[$arElement["IBLOCK_ELEMENT_ID"]] = $arElement;
                    }
                    else
                    {
                        $rsElements = \CIBlockElement::GetList(array('ID' => 'ASC'), $arElementFilter, false, false, array('ID', 'IBLOCK_ID'));
                        while($arElement = $rsElements->Fetch())
                            $arElements[$arElement["ID"]] = array();
                    }

                    if (!empty($arElements) && $this->SKU_IBLOCK_ID && $this->arResult["SKU_PROPERTY_COUNT"] > 0)
                    {
                        $arSkuFilter = array(
                            "IBLOCK_ID" => $this->SKU_IBLOCK_ID,
                            "ACTIVE_DATE" => "Y",
                            "ACTIVE" => "Y",
                            "CHECK_PERMISSIONS" => "Y",
                            "=PROPERTY_".$this->SKU_PROPERTY_ID => array_keys($arElements),
                        );
                        if ($this->arParams['HIDE_NOT_AVAILABLE'] == 'Y')
                            $arSkuFilter['AVAILABLE'] = 'Y';

                        $rsElements = \CIBlockElement::GetPropertyValues($this->SKU_IBLOCK_ID, $arSkuFilter, false, array('ID' => $this->arResult["SKU_PROPERTY_ID_LIST"]));
                        while($arSku = $rsElements->Fetch())
                        {
                            foreach($this->arResult["ITEMS"] as $PID => $arItem)
                            {
                                if (isset($arSku[$PID]) && $arSku[$this->SKU_PROPERTY_ID] > 0)
                                {
                                    if (is_array($arSku[$PID]))
                                    {
                                        foreach($arSku[$PID] as $value)
                                            $arElements[$arSku[$this->SKU_PROPERTY_ID]][$PID][] = $value;
                                    }
                                    else
                                    {
                                        $arElements[$arSku[$this->SKU_PROPERTY_ID]][$PID][] = $arSku[$PID];
                                    }
                                }
                            }
                        }
                    }

                    \CTimeZone::Disable();
                    $uniqTest = array();
                    foreach($arElements as $arElement)
                    {
                        $propertyValues = $propertyEmptyValuesCombination;
                        $uniqStr = '';
                        foreach($this->arResult["ITEMS"] as $PID => $arItem)
                        {
                            if (is_array($arElement[$PID]))
                            {
                                foreach($arElement[$PID] as $value)
                                {
                                    $key = $this->fillItemValues($this->arResult["ITEMS"][$PID], $value);
                                    $propertyValues[$PID][$key] = $this->arResult["ITEMS"][$PID]["VALUES"][$key]["VALUE"];
                                    $uniqStr .= '|'.$key.'|'.$propertyValues[$PID][$key];
                                }
                            }
                            elseif ($arElement[$PID] !== false)
                            {
                                $key = $this->fillItemValues($this->arResult["ITEMS"][$PID], $arElement[$PID]);
                                $propertyValues[$PID][$key] = $this->arResult["ITEMS"][$PID]["VALUES"][$key]["VALUE"];
                                $uniqStr .= '|'.$key.'|'.$propertyValues[$PID][$key];
                            }
                        }

                        $uniqCheck = md5($uniqStr);
                        if (isset($uniqTest[$uniqCheck]))
                            continue;
                        $uniqTest[$uniqCheck] = true;

                        $this->ArrayMultiply($this->arResult["COMBO"], $propertyValues);
                    }
                    \CTimeZone::Enable();

                    $arSelect = array("ID", "IBLOCK_ID");
                    foreach($this->arResult["PRICES"] as &$value)
                    {
                        if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
                            continue;
                        $arSelect = array_merge($arSelect, $value["SELECT_EXTENDED"]);
                        $arElementFilter["DEFAULT_PRICE_FILTER_".$value["ID"]] = 1;
                        if (isset($arSkuFilter))
                            $arSkuFilter["DEFAULT_PRICE_FILTER_".$value["ID"]] = 1;
                    }
                    unset($value);

                    $rsElements = \CIBlockElement::GetList(array(), $arElementFilter, false, false, $arSelect);
                    while($arElement = $rsElements->Fetch())
                    {
                        foreach($this->arResult["PRICES"] as $NAME => $arPrice)
                            if(isset($this->arResult["ITEMS"][$NAME]))
                                $this->fillItemPrices($this->arResult["ITEMS"][$NAME], $arElement);
                    }

                    if (isset($arSkuFilter))
                    {
                        $rsElements = \CIBlockElement::GetList(array(), $arSkuFilter, false, false, $arSelect);
                        while($arSku = $rsElements->Fetch())
                        {
                            foreach($this->arResult["PRICES"] as $NAME => $arPrice)
                                if(isset($this->arResult["ITEMS"][$NAME]))
                                    $this->fillItemPrices($this->arResult["ITEMS"][$NAME], $arSku);
                        }
                    }
                }

                foreach($this->arResult["ITEMS"] as $PID => $arItem)
                    uasort($this->arResult["ITEMS"][$PID]["VALUES"], array($this, "_sort"));
            }

            if ($this->arParams["XML_EXPORT"] === "Y")
            {
                $this->arResult["SECTION_TITLE"] = "";
                $this->arResult["SECTION_DESCRIPTION"] = "";

                if ($this->SECTION_ID > 0)
                {
                    $arSelect = array("ID", "IBLOCK_ID", "LEFT_MARGIN", "RIGHT_MARGIN");
                    if ($this->arParams["SECTION_TITLE"] !== "")
                        $arSelect[] = $this->arParams["SECTION_TITLE"];
                    if ($this->arParams["SECTION_DESCRIPTION"] !== "")
                        $arSelect[] = $this->arParams["SECTION_DESCRIPTION"];

                    $sectionList = \CIBlockSection::GetList(array(), array(
                        "=ID" => $this->SECTION_ID,
                        "IBLOCK_ID" => $this->IBLOCK_ID,
                    ), false, $arSelect);
                    $this->arResult["SECTION"] = $sectionList->GetNext();

                    if ($this->arResult["SECTION"])
                    {
                        $this->arResult["SECTION_TITLE"] = $this->arResult["SECTION"][$this->arParams["SECTION_TITLE"]];
                        if ($this->arParams["SECTION_DESCRIPTION"] !== "")
                        {
                            $obParser = new CTextParser;
                            $this->arResult["SECTION_DESCRIPTION"] = $obParser->html_cut($this->arResult["SECTION"][$this->arParams["SECTION_DESCRIPTION"]], 200);
                        }
                    }
                }
            }
            $this->setCurrencyTag();
            $this->setIblockTag();

            $this->EndResultCache();
        }
        else
        {
            $this->facet->setPrices($this->arResult["PRICES"] ? $this->arResult["PRICES"] : []);
            $this->facet->setSectionId($this->SECTION_ID);
        }

        /*Handle checked for checkboxes and html control value for numbers*/
        if(isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] === "y")
            $_CHECK = &$_REQUEST;
        elseif(isset($_REQUEST["del_filter"]))
            $_CHECK = array();
        elseif(isset($_GET["set_filter"]))
            $_CHECK = &$_GET;
        elseif($this->arParams["SMART_FILTER_PATH"])
            $_CHECK = $this->convertUrlToCheck($this->arParams["~SMART_FILTER_PATH"]);
        elseif($this->arParams["SAVE_IN_SESSION"] && isset($_SESSION[$FILTER_NAME][$this->SECTION_ID]))
            $_CHECK = $_SESSION[$FILTER_NAME][$this->SECTION_ID];
        else
            $_CHECK = array();

        /*Set state of the html controls depending on filter values*/
        $allCHECKED = array();
        /*Faceted filter*/
        $facetIndex = array();
        foreach($this->arResult["ITEMS"] as $PID => $arItem)
        {
            foreach($arItem["VALUES"] as $key => $ar)
            {
                if ($this->arResult["FACET_FILTER"] && isset($ar["FACET_VALUE"]))
                {
                    $facetIndex[$PID][$ar["FACET_VALUE"]] = &$this->arResult["ITEMS"][$PID]["VALUES"][$key];
                }

                if(
                    isset($_CHECK[$ar["CONTROL_NAME"]])
                    || (
                        isset($_CHECK[$ar["CONTROL_NAME_ALT"]])
                        && $_CHECK[$ar["CONTROL_NAME_ALT"]] == $ar["HTML_VALUE_ALT"]
                    )
                )
                {
                    if($arItem["PROPERTY_TYPE"] == "N")
                    {
                        $this->arResult["ITEMS"][$PID]["VALUES"][$key]["HTML_VALUE"] = htmlspecialcharsbx($_CHECK[$ar["CONTROL_NAME"]]);
                        $this->arResult["ITEMS"][$PID]["DISPLAY_EXPANDED"] = "Y";
                        if ($this->arResult["FACET_FILTER"] && mb_strlen($_CHECK[$ar["CONTROL_NAME"]]) > 0)
                        {
                            if ($key == "MIN")
                                $this->facet->addNumericPropertyFilter($PID, ">=", $_CHECK[$ar["CONTROL_NAME"]]);
                            elseif ($key == "MAX")
                                $this->facet->addNumericPropertyFilter($PID, "<=", $_CHECK[$ar["CONTROL_NAME"]]);
                        }
                    }
                    elseif(isset($arItem["PRICE"]))
                    {
                        $this->arResult["ITEMS"][$PID]["VALUES"][$key]["HTML_VALUE"] = htmlspecialcharsbx($_CHECK[$ar["CONTROL_NAME"]]);
                        $this->arResult["ITEMS"][$PID]["DISPLAY_EXPANDED"] = "Y";
                        if ($this->arResult["FACET_FILTER"] && mb_strlen($_CHECK[$ar["CONTROL_NAME"]]) > 0)
                        {
                            if ($key == "MIN")
                                $this->facet->addPriceFilter($this->arResult["PRICES"][$PID]["ID"], ">=", $_CHECK[$ar["CONTROL_NAME"]]);
                            elseif ($key == "MAX")
                                $this->facet->addPriceFilter($this->arResult["PRICES"][$PID]["ID"], "<=", $_CHECK[$ar["CONTROL_NAME"]]);
                        }
                    }
                    elseif($arItem["DISPLAY_TYPE"] == "U")
                    {
                        $this->arResult["ITEMS"][$PID]["VALUES"][$key]["HTML_VALUE"] = htmlspecialcharsbx($_CHECK[$ar["CONTROL_NAME"]]);
                        $this->arResult["ITEMS"][$PID]["DISPLAY_EXPANDED"] = "Y";
                        if ($this->arResult["FACET_FILTER"] && mb_strlen($_CHECK[$ar["CONTROL_NAME"]]) > 0)
                        {
                            if ($key == "MIN")
                                $this->facet->addDatetimePropertyFilter($PID, ">=", MakeTimeStamp($_CHECK[$ar["CONTROL_NAME"]], FORMAT_DATE));
                            elseif ($key == "MAX")
                                $this->facet->addDatetimePropertyFilter($PID, "<=", MakeTimeStamp($_CHECK[$ar["CONTROL_NAME"]], FORMAT_DATE) + 23*3600+59*60+59);
                        }
                    }
                    elseif($_CHECK[$ar["CONTROL_NAME"]] == $ar["HTML_VALUE"])
                    {
                        $this->arResult["ITEMS"][$PID]["VALUES"][$key]["CHECKED"] = true;
                        $this->arResult["ITEMS"][$PID]["DISPLAY_EXPANDED"] = "Y";
                        $allCHECKED[$PID][$ar["VALUE"]] = true;
                        if ($this->arResult["FACET_FILTER"])
                        {
                            if ($arItem["USER_TYPE"] === "DateTime")
                                $this->facet->addDatetimePropertyFilter($PID, "=", MakeTimeStamp($ar["VALUE"], FORMAT_DATE));
                            else
                                $this->facet->addDictionaryPropertyFilter($PID, "=", $ar["FACET_VALUE"]);
                        }
                    }
                    elseif($_CHECK[$ar["CONTROL_NAME_ALT"]] == $ar["HTML_VALUE_ALT"])
                    {
                        $this->arResult["ITEMS"][$PID]["VALUES"][$key]["CHECKED"] = true;
                        $this->arResult["ITEMS"][$PID]["DISPLAY_EXPANDED"] = "Y";
                        $allCHECKED[$PID][$ar["VALUE"]] = true;
                        if ($this->arResult["FACET_FILTER"])
                        {
                            $this->facet->addDictionaryPropertyFilter($PID, "=", $ar["FACET_VALUE"]);
                        }
                    }
                }
            }
        }

        if ($_CHECK)
        {
            /*Disable composite mode when filter checked*/
            $this->setFrameMode(false);

            if ($this->arResult["FACET_FILTER"])
            {
                if (!$this->facet->isEmptyWhere())
                {
                    foreach ($this->arResult["ITEMS"] as $PID => &$arItem)
                    {
                        if ($arItem["PROPERTY_TYPE"] != "N" && !isset($arItem["PRICE"]))
                        {
                            foreach ($arItem["VALUES"] as $key => &$arValue)
                            {
                                $arValue["DISABLED"] = true;
                                $arValue["ELEMENT_COUNT"] = 0;
                            }
                            unset($arValue);
                        }
                    }
                    unset($arItem);

                    if ($this->arResult["CURRENCIES"])
                        $this->facet->enableCurrencyConversion($this->convertCurrencyId, array_keys($this->arResult["CURRENCIES"]));

                    $res = $this->facet->query($this->arResult["FACET_FILTER"]);
                    while ($row = $res->fetch())
                    {
                        $facetId = $row["FACET_ID"];
                        if (\Bitrix\Iblock\PropertyIndex\Storage::isPropertyId($facetId))
                        {
                            $pp = \Bitrix\Iblock\PropertyIndex\Storage::facetIdToPropertyId($facetId);
                            if ($this->arResult["ITEMS"][$pp]["PROPERTY_TYPE"] == "N")
                            {
                                if (is_array($this->arResult["ITEMS"][$pp]["VALUES"]))
                                {
                                    $this->arResult["ITEMS"][$pp]["VALUES"]["MIN"]["FILTERED_VALUE"] = $row["MIN_VALUE_NUM"];
                                    $this->arResult["ITEMS"][$pp]["VALUES"]["MAX"]["FILTERED_VALUE"] = $row["MAX_VALUE_NUM"];
                                }
                            }
                            else
                            {
                                if (isset($facetIndex[$pp][$row["VALUE"]]))
                                {
                                    unset($facetIndex[$pp][$row["VALUE"]]["DISABLED"]);
                                    $facetIndex[$pp][$row["VALUE"]]["ELEMENT_COUNT"] = $row["ELEMENT_COUNT"];
                                }
                            }
                        }
                        else
                        {
                            $priceId = \Bitrix\Iblock\PropertyIndex\Storage::facetIdToPriceId($facetId);
                            foreach($this->arResult["PRICES"] as $NAME => $arPrice)
                            {
                                if (
                                    $arPrice["ID"] == $priceId
                                    && isset($this->arResult["ITEMS"][$NAME])
                                    && is_array($this->arResult["ITEMS"][$NAME]["VALUES"])
                                )
                                {
                                    $currency = $row["VALUE"];
                                    $existCurrency = mb_strlen($currency) > 0;
                                    if ($existCurrency)
                                        $currency = $this->facet->lookupDictionaryValue($currency);

                                    $priceValue = $this->convertPrice($row["MIN_VALUE_NUM"], $currency);
                                    if (
                                        !isset($this->arResult["ITEMS"][$NAME]["VALUES"]["MIN"]["FILTERED_VALUE"])
                                        || $this->arResult["ITEMS"][$NAME]["VALUES"]["MIN"]["FILTERED_VALUE"] > $priceValue
                                    )
                                    {
                                        $this->arResult["ITEMS"][$NAME]["VALUES"]["MIN"]["FILTERED_VALUE"] = $priceValue;
                                    }

                                    $priceValue = $this->convertPrice($row["MAX_VALUE_NUM"], $currency);
                                    if (
                                        !isset($this->arResult["ITEMS"][$NAME]["VALUES"]["MAX"]["FILTERED_VALUE"])
                                        || $this->arResult["ITEMS"][$NAME]["VALUES"]["MAX"]["FILTERED_VALUE"] > $priceValue
                                    )
                                    {
                                        $this->arResult["ITEMS"][$NAME]["VALUES"]["MAX"]["FILTERED_VALUE"] = $priceValue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            else
            {
                $index = array();
                foreach ($this->arResult["COMBO"] as $id => $combination)
                {
                    foreach ($combination as $PID => $value)
                    {
                        $index[$PID][$value][] = &$this->arResult["COMBO"][$id];
                    }
                }

                /*Handle disabled for checkboxes (TODO: handle number type)*/
                foreach ($this->arResult["ITEMS"] as $PID => &$arItem)
                {
                    if ($arItem["PROPERTY_TYPE"] != "N" && !isset($arItem["PRICE"]))
                    {
                        //All except current one
                        $checked = $allCHECKED;
                        unset($checked[$PID]);

                        foreach ($arItem["VALUES"] as $key => &$arValue)
                        {
                            $found = false;
                            if (isset($index[$PID][$arValue["VALUE"]]))
                            {
                                //Check if there are any combinations exists
                                foreach ($index[$PID][$arValue["VALUE"]] as $id => $combination)
                                {
                                    //Check if combination fits into the filter
                                    $isOk = true;
                                    foreach ($checked as $cPID => $values)
                                    {
                                        if (!isset($values[$combination[$cPID]]))
                                        {
                                            $isOk = false;
                                            break;
                                        }
                                    }

                                    if ($isOk)
                                    {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if (!$found)
                                $arValue["DISABLED"] = true;
                        }
                        unset($arValue);
                    }
                }
                unset($arItem);
            }
        }

        /*Make iblock filter*/
        global ${$FILTER_NAME};
        if(!is_array(${$FILTER_NAME}))
            ${$FILTER_NAME} = array();

        foreach($this->arResult["ITEMS"] as $PID => $arItem)
        {
            if(isset($arItem["PRICE"]))
            {
                if(mb_strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) && mb_strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]))
                    ${$FILTER_NAME}["><CATALOG_PRICE_".$arItem["ID"]] = array($arItem["VALUES"]["MIN"]["HTML_VALUE"], $arItem["VALUES"]["MAX"]["HTML_VALUE"]);
                elseif(mb_strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]))
                    ${$FILTER_NAME}[">=CATALOG_PRICE_".$arItem["ID"]] = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
                elseif(mb_strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]))
                    ${$FILTER_NAME}["<=CATALOG_PRICE_".$arItem["ID"]] = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
            }
            elseif($arItem["PROPERTY_TYPE"] == "N")
            {
                $existMinValue = (mb_strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0);
                $existMaxValue = (mb_strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0);
                if ($existMinValue || $existMaxValue)
                {
                    $filterKey = '';
                    $filterValue = '';
                    if ($existMinValue && $existMaxValue)
                    {
                        $filterKey = "><PROPERTY_".$PID;
                        $filterValue = array($arItem["VALUES"]["MIN"]["HTML_VALUE"], $arItem["VALUES"]["MAX"]["HTML_VALUE"]);
                    }
                    elseif($existMinValue)
                    {
                        $filterKey = ">=PROPERTY_".$PID;
                        $filterValue = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
                    }
                    elseif($existMaxValue)
                    {
                        $filterKey = "<=PROPERTY_".$PID;
                        $filterValue = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
                    }

                    if ($arItem["IBLOCK_ID"] == $this->SKU_IBLOCK_ID)
                    {
                        if (!isset(${$FILTER_NAME}["OFFERS"]))
                        {
                            ${$FILTER_NAME}["OFFERS"] = array();
                        }
                        ${$FILTER_NAME}["OFFERS"][$filterKey] = $filterValue;
                    }
                    else
                    {
                        ${$FILTER_NAME}[$filterKey] = $filterValue;
                    }
                }
            }
            elseif($arItem["DISPLAY_TYPE"] == "U")
            {
                $existMinValue = (mb_strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0);
                $existMaxValue = (mb_strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0);
                if ($existMinValue || $existMaxValue)
                {
                    $filterKey = '';
                    $filterValue = '';
                    if ($existMinValue && $existMaxValue)
                    {
                        $filterKey = "><PROPERTY_".$PID;
                        $timestamp1 = MakeTimeStamp($arItem["VALUES"]["MIN"]["HTML_VALUE"], FORMAT_DATE);
                        $timestamp2 = MakeTimeStamp($arItem["VALUES"]["MAX"]["HTML_VALUE"], FORMAT_DATE);
                        if ($timestamp1 && $timestamp2)
                            $filterValue = array(FormatDate("Y-m-d H:i:s", $timestamp1), FormatDate("Y-m-d H:i:s", $timestamp2 + 23*3600+59*60+59));
                    }
                    elseif($existMinValue)
                    {
                        $filterKey = ">=PROPERTY_".$PID;
                        $timestamp1 = MakeTimeStamp($arItem["VALUES"]["MIN"]["HTML_VALUE"], FORMAT_DATE);
                        if ($timestamp1)
                            $filterValue = FormatDate("Y-m-d H:i:s", $timestamp1);
                    }
                    elseif($existMaxValue)
                    {
                        $filterKey = "<=PROPERTY_".$PID;
                        $timestamp2 = MakeTimeStamp($arItem["VALUES"]["MAX"]["HTML_VALUE"], FORMAT_DATE);
                        if ($timestamp2)
                            $filterValue = FormatDate("Y-m-d H:i:s", $timestamp2 + 23*3600+59*60+59);
                    }

                    if ($arItem["IBLOCK_ID"] == $this->SKU_IBLOCK_ID)
                    {
                        if (!isset(${$FILTER_NAME}["OFFERS"]))
                        {
                            ${$FILTER_NAME}["OFFERS"] = array();
                        }
                        ${$FILTER_NAME}["OFFERS"][$filterKey] = $filterValue;
                    }
                    else
                    {
                        ${$FILTER_NAME}[$filterKey] = $filterValue;
                    }
                }
            }
            elseif($arItem["USER_TYPE"] == "DateTime")
            {
                $datetimeFilters = array();
                foreach($arItem["VALUES"] as $key => $ar)
                {
                    if ($ar["CHECKED"])
                    {
                        $filterKey = "><PROPERTY_".$PID;
                        $timestamp = MakeTimeStamp($ar["VALUE"], FORMAT_DATE);
                        $filterValue = array(
                            FormatDate("Y-m-d H:i:s", $timestamp),
                            FormatDate("Y-m-d H:i:s", $timestamp + 23 * 3600 + 59 * 60 + 59)
                        );
                        $datetimeFilters[] = array($filterKey => $filterValue);
                    }
                }

                if ($datetimeFilters)
                {
                    $datetimeFilters["LOGIC"] = "OR";
                    if ($arItem["IBLOCK_ID"] == $this->SKU_IBLOCK_ID)
                    {
                        if (!isset(${$FILTER_NAME}["OFFERS"]))
                        {
                            ${$FILTER_NAME}["OFFERS"] = array();
                        }
                        ${$FILTER_NAME}["OFFERS"][] = $datetimeFilters;
                    }
                    else
                    {
                        ${$FILTER_NAME}[] = $datetimeFilters;
                    }
                }
            }
            else
            {
                foreach($arItem["VALUES"] as $key => $ar)
                {
                    if($ar["CHECKED"])
                    {
                        $filterKey = "=PROPERTY_".$PID;
                        $backKey = htmlspecialcharsback($key);
                        if ($arItem["IBLOCK_ID"] == $this->SKU_IBLOCK_ID)
                        {
                            if (!isset(${$FILTER_NAME}["OFFERS"]))
                            {
                                ${$FILTER_NAME}["OFFERS"] = array();
                            }
                            if (!isset(${$FILTER_NAME}["OFFERS"][$filterKey]))
                                ${$FILTER_NAME}["OFFERS"][$filterKey] = array($backKey);
                            elseif (!is_array(${$FILTER_NAME}["OFFERS"][$filterKey]))
                                ${$FILTER_NAME}["OFFERS"][$filterKey] = array($filter[$filterKey], $backKey);
                            elseif (!in_array($backKey, ${$FILTER_NAME}["OFFERS"][$filterKey]))
                                ${$FILTER_NAME}["OFFERS"][$filterKey][] = $backKey;
                        }
                        else
                        {
                            if (!isset(${$FILTER_NAME}[$filterKey]))
                                ${$FILTER_NAME}[$filterKey] = array($backKey);
                            elseif (!is_array(${$FILTER_NAME}[$filterKey]))
                                ${$FILTER_NAME}[$filterKey] = array($filter[$filterKey], $backKey);
                            elseif (!in_array($backKey, ${$FILTER_NAME}[$filterKey]))
                                ${$FILTER_NAME}[$filterKey][] = $backKey;
                        }
                    }
                }
            }
        }

        if ($this->arResult["FACET_FILTER"] && $this->arResult["CURRENCIES"])
        {
            ${$FILTER_NAME}["FACET_OPTIONS"]["PRICE_FILTER"] = true;
            ${$FILTER_NAME}["FACET_OPTIONS"]["CURRENCY_CONVERSION"] = array(
                "FROM" => array_keys($this->arResult["CURRENCIES"]),
                "TO" => $this->convertCurrencyId,
            );
        }

        /*Save to session if needed*/
        if($this->arParams["SAVE_IN_SESSION"])
        {
            $_SESSION[$FILTER_NAME][$this->SECTION_ID] = $_CHECK;
        }

        $this->arResult["JS_FILTER_PARAMS"] = array();
        if ($this->arParams["SEF_MODE"] == "Y")
        {
            $section = false;
            if ($this->SECTION_ID > 0)
            {
                $sectionList = \CIBlockSection::GetList(array(), array(
                    "=ID" => $this->SECTION_ID,
                    "IBLOCK_ID" => $this->IBLOCK_ID,
                ), false, array("ID", "IBLOCK_ID", "SECTION_PAGE_URL"));
                $sectionList->SetUrlTemplates($this->arParams["SEF_RULE"]);
                $section = $sectionList->GetNext();
            }

            if ($section)
            {
                $url = $section["DETAIL_PAGE_URL"];
            }
            else
            {
                $url = \CIBlock::ReplaceSectionUrl($this->arParams["SEF_RULE"], array());
            }

            $this->arResult["JS_FILTER_PARAMS"]["SEF_SET_FILTER_URL"] = $this->makeSmartUrl($url, true);
            $this->arResult["JS_FILTER_PARAMS"]["SEF_DEL_FILTER_URL"] = $this->makeSmartUrl($url, false);
        }

        $uri = new \Bitrix\Main\Web\Uri($this->request->getRequestUri());
        $uri->deleteParams(\Bitrix\Main\HttpRequest::getSystemParameters());
        $pageURL = $uri->GetUri();
        $paramsToDelete = array("set_filter", "del_filter", "ajax", "bxajaxid", "AJAX_CALL", "mode");
        foreach($this->arResult["ITEMS"] as $PID => $arItem)
        {
            foreach($arItem["VALUES"] as $key => $ar)
            {
                $paramsToDelete[] = $ar["CONTROL_NAME"];
                $paramsToDelete[] = $ar["CONTROL_NAME_ALT"];
            }
        }

        $clearURL = \CHTTP::urlDeleteParams($pageURL, $paramsToDelete, array("delete_system_params" => true));

        if ($this->arResult["JS_FILTER_PARAMS"]["SEF_SET_FILTER_URL"])
        {
            $this->arResult["FILTER_URL"] = $this->arResult["JS_FILTER_PARAMS"]["SEF_SET_FILTER_URL"];
            $this->arResult["FILTER_AJAX_URL"] = htmlspecialcharsbx(\CHTTP::urlAddParams($this->arResult["FILTER_URL"], array(
                "bxajaxid" => $_GET["bxajaxid"],
            ), array(
                "skip_empty" => true,
                "encode" => true,
            )));
            $this->arResult["SEF_SET_FILTER_URL"] = $this->arResult["JS_FILTER_PARAMS"]["SEF_SET_FILTER_URL"];
            $this->arResult["SEF_DEL_FILTER_URL"] = $this->arResult["JS_FILTER_PARAMS"]["SEF_DEL_FILTER_URL"];
        }
        else
        {
            $paramsToAdd = array(
                "set_filter" => "y",
            );
            foreach($this->arResult["ITEMS"] as $PID => $arItem)
            {
                foreach($arItem["VALUES"] as $key => $ar)
                {
                    if(isset($_CHECK[$ar["CONTROL_NAME"]]))
                    {
                        if($arItem["PROPERTY_TYPE"] == "N" || isset($arItem["PRICE"]))
                            $paramsToAdd[$ar["CONTROL_NAME"]] = $_CHECK[$ar["CONTROL_NAME"]];
                        elseif($_CHECK[$ar["CONTROL_NAME"]] == $ar["HTML_VALUE"])
                            $paramsToAdd[$ar["CONTROL_NAME"]] = $_CHECK[$ar["CONTROL_NAME"]];
                    }
                    elseif(isset($_CHECK[$ar["CONTROL_NAME_ALT"]]))
                    {
                        if ($_CHECK[$ar["CONTROL_NAME_ALT"]] == $ar["HTML_VALUE_ALT"])
                            $paramsToAdd[$ar["CONTROL_NAME_ALT"]] = $_CHECK[$ar["CONTROL_NAME_ALT"]];
                    }
                }
            }

            $this->arResult["FILTER_URL"] = htmlspecialcharsbx(\CHTTP::urlAddParams($clearURL, $paramsToAdd, array(
                "skip_empty" => true,
                "encode" => true,
            )));

            $this->arResult["FILTER_AJAX_URL"] = htmlspecialcharsbx(\CHTTP::urlAddParams($clearURL, $paramsToAdd + array(
                    "bxajaxid" => $_GET["bxajaxid"],
                ), array(
                "skip_empty" => true,
                "encode" => true,
            )));
        }

        if(isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] === "y")
        {
            $arFilter = $this->makeFilter($FILTER_NAME);
            if (!empty($preFilter))
                $arFilter = array_merge($preFilter, $arFilter);
            $this->arResult["ELEMENT_COUNT"] = \CIBlockElement::GetList(array(), $arFilter, array(), false);

            if (isset($_GET["bxajaxid"]))
            {
                $this->arResult["COMPONENT_CONTAINER_ID"] = htmlspecialcharsbx("comp_".$_GET["bxajaxid"]);
                if ($this->arParams["INSTANT_RELOAD"])
                    $this->arResult["INSTANT_RELOAD"] = true;
            }
        }

        if (
            !empty($this->arParams["PAGER_PARAMS_NAME"])
            && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $this->arParams["PAGER_PARAMS_NAME"])
        )
        {
            if (!is_array($GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]]))
                $GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]] = array();

            if ($this->arResult["JS_FILTER_PARAMS"]["SEF_SET_FILTER_URL"])
            {
                $GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]]["BASE_LINK"] = $this->arResult["JS_FILTER_PARAMS"]["SEF_SET_FILTER_URL"];
            }
            elseif (count($paramsToAdd) > 1)
            {
                $GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]] = array_merge($GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]], $paramsToAdd);
            }
        }

        $arInputNames = array();
        foreach($this->arResult["ITEMS"] as $PID => $arItem)
        {
            foreach($arItem["VALUES"] as $key => $ar)
            {
                $arInputNames[$ar["CONTROL_NAME"]] = true;
                $arInputNames[$ar["CONTROL_NAME_ALT"]] = true;
            }
        }
        $arInputNames["set_filter"]=true;
        $arInputNames["del_filter"]=true;

        $arSkip = array(
            "AUTH_FORM" => true,
            "TYPE" => true,
            "USER_LOGIN" => true,
            "USER_CHECKWORD" => true,
            "USER_PASSWORD" => true,
            "USER_CONFIRM_PASSWORD" => true,
            "USER_EMAIL" => true,
            "captcha_word" => true,
            "captcha_sid" => true,
            "login" => true,
            "Login" => true,
            "backurl" => true,
            "ajax" => true,
            "mode" => true,
            "bxajaxid" => true,
            "AJAX_CALL" => true,
        );

        $this->arResult["FORM_ACTION"] = $clearURL;
        $this->arResult["HIDDEN"] = array();
        foreach(array_merge($_GET, $_POST) as $key => $value)
        {
            if(
                !isset($arInputNames[$key])
                && !isset($arSkip[$key])
                && !is_array($value)
            )
            {
                $this->arResult["HIDDEN"][] = array(
                    "CONTROL_ID" => htmlspecialcharsbx($key),
                    "CONTROL_NAME" => htmlspecialcharsbx($key),
                    "HTML_VALUE" => htmlspecialcharsbx($value),
                );
            }
        }

        if (
            $this->arParams["XML_EXPORT"] === "Y"
            && $this->arResult["SECTION"]
            && ($this->arResult["SECTION"]["RIGHT_MARGIN"] - $this->arResult["SECTION"]["LEFT_MARGIN"]) === 1
        )
        {
            $exportUrl = \CHTTP::urlAddParams($clearURL, array("mode" => "xml"));
            $APPLICATION->AddHeadString('<meta property="ya:interaction" content="XML_FORM" />');
            $APPLICATION->AddHeadString('<meta property="ya:interaction:url" content="'.\CHTTP::urn2uri($exportUrl).'" />');
        }

        if ($this->arParams["XML_EXPORT"] === "Y" && $_REQUEST["mode"] === "xml")
        {
            $this->setFrameMode(false);
            ob_start();
            $this->IncludeComponentTemplate("xml");
            $xml = ob_get_contents();
            $APPLICATION->RestartBuffer();
            while(ob_end_clean());
            header("Content-Type: text/xml; charset=utf-8");
            $error = "";
            echo \Bitrix\Main\Text\Encoding::convertEncoding($xml, LANG_CHARSET, "utf-8", $error);
            \CMain::FinalActions();
            die();
        }
        elseif(isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] === "y")
        {
            $this->setFrameMode(false);
            define("BX_COMPRESSION_DISABLED", true);
            ob_start();
            $this->IncludeComponentTemplate("ajax");
            $json = ob_get_contents();
            $APPLICATION->RestartBuffer();
            while(ob_end_clean());
            header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
            \CMain::FinalActions();
            echo $json;
            die();
        }
        else
        {
            $this->IncludeComponentTemplate();
        }

    }

    public function getIBlockItems($IBLOCK_ID)
    {
        $items = array();

        foreach (\CIBlockSectionPropertyLink::GetArray($IBLOCK_ID, $this->SECTION_ID) as $PID => $arLink) {
            if ($arLink["SMART_FILTER"] !== "Y")
                continue;

            if ($arLink["ACTIVE"] === "N")
                continue;

            if ($arLink['FILTER_HINT'] <> '') {
                $arLink['FILTER_HINT'] = \CTextParser::closeTags($arLink['FILTER_HINT']);
            }

            $rsProperty = \CIBlockProperty::GetByID($PID);
            $arProperty = $rsProperty->Fetch();
            if ($arProperty) {
                $items[$arProperty["ID"]] = array(
                    "ID" => $arProperty["ID"],
                    "IBLOCK_ID" => $arProperty["IBLOCK_ID"],
                    "CODE" => $arProperty["CODE"],
                    "~NAME" => $arProperty["NAME"],
                    "NAME" => htmlspecialcharsEx($arProperty["NAME"]),
                    "PROPERTY_TYPE" => $arProperty["PROPERTY_TYPE"],
                    "USER_TYPE" => $arProperty["USER_TYPE"],
                    "USER_TYPE_SETTINGS" => $arProperty["USER_TYPE_SETTINGS"],
                    "DISPLAY_TYPE" => $arLink["DISPLAY_TYPE"],
                    "DISPLAY_EXPANDED" => $arLink["DISPLAY_EXPANDED"],
                    "FILTER_HINT" => $arLink["FILTER_HINT"],
                    "VALUES" => array(),
                );

                if (
                    $arProperty["PROPERTY_TYPE"] == "N"
                    || $arLink["DISPLAY_TYPE"] == "U"
                ) {
                    $minID = $this->SAFE_FILTER_NAME . '_' . $arProperty['ID'] . '_MIN';
                    $maxID = $this->SAFE_FILTER_NAME . '_' . $arProperty['ID'] . '_MAX';
                    $items[$arProperty["ID"]]["VALUES"] = array(
                        "MIN" => array(
                            "CONTROL_ID" => $minID,
                            "CONTROL_NAME" => $minID,
                        ),
                        "MAX" => array(
                            "CONTROL_ID" => $maxID,
                            "CONTROL_NAME" => $maxID,
                        ),
                    );
                }
            }
        }
        return $items;
    }

    public function getPriceItems()
    {
        $items = array();
        if (!empty($this->arParams["PRICE_CODE"])) {
            if (self::$catalogIncluded === null)
                self::$catalogIncluded = Loader::includeModule('catalog');
            if (self::$catalogIncluded) {
                $rsPrice = \CCatalogGroup::GetList(
                    array('SORT' => 'ASC', 'ID' => 'ASC'),
                    array('=NAME' => $this->arParams["PRICE_CODE"]),
                    false,
                    false,
                    array('ID', 'NAME', 'NAME_LANG', 'CAN_ACCESS', 'CAN_BUY')
                );
                while ($arPrice = $rsPrice->Fetch()) {
                    if ($arPrice["CAN_ACCESS"] == "Y" || $arPrice["CAN_BUY"] == "Y") {
                        $arPrice["NAME_LANG"] = (string)$arPrice["NAME_LANG"];
                        if ($arPrice["NAME_LANG"] === '')
                            $arPrice["NAME_LANG"] = $arPrice["NAME"];
                        $minID = $this->SAFE_FILTER_NAME . '_P' . $arPrice['ID'] . '_MIN';
                        $maxID = $this->SAFE_FILTER_NAME . '_P' . $arPrice['ID'] . '_MAX';
                        $error = "";
                        $utf_id = \Bitrix\Main\Text\Encoding::convertEncoding(toLower($arPrice["NAME"]), LANG_CHARSET, "utf-8", $error);
                        $items[$arPrice["NAME"]] = array(
                            "ID" => $arPrice["ID"],
                            "CODE" => $arPrice["NAME"],
                            "URL_ID" => rawurlencode(str_replace("/", "-", $utf_id)),
                            "~NAME" => $arPrice["NAME_LANG"],
                            "NAME" => htmlspecialcharsbx($arPrice["NAME_LANG"]),
                            "PRICE" => true,
                            "VALUES" => array(
                                "MIN" => array(
                                    "CONTROL_ID" => $minID,
                                    "CONTROL_NAME" => $minID,
                                ),
                                "MAX" => array(
                                    "CONTROL_ID" => $maxID,
                                    "CONTROL_NAME" => $maxID,
                                ),
                            ),
                        );
                    }
                }
            }
        }
        return $items;
    }

    public function getResultItems()
    {
        $items = $this->getIBlockItems($this->IBLOCK_ID);
        $this->arResult["PROPERTY_COUNT"] = count($items);
        $this->arResult["PROPERTY_ID_LIST"] = array_keys($items);

        if ($this->SKU_IBLOCK_ID) {
            $this->arResult["SKU_PROPERTY_ID_LIST"] = array($this->SKU_PROPERTY_ID);
            foreach ($this->getIBlockItems($this->SKU_IBLOCK_ID) as $PID => $arItem) {
                $items[$PID] = $arItem;
                $this->arResult["SKU_PROPERTY_COUNT"]++;
                $this->arResult["SKU_PROPERTY_ID_LIST"][] = $PID;
            }
        }

        if (!empty($this->arParams["PRICE_CODE"])) {
            foreach ($this->getPriceItems() as $PID => $arItem) {
                $arItem["ENCODED_ID"] = md5($arItem["ID"]);
                $items[$PID] = $arItem;
            }
        }

        return $items;
    }

    public function processProperties(array &$resultItem, array $elements, array $dictionaryID, array $directoryPredict = [])
    {
        $lookupDictionary = [];
        if (!empty($dictionaryID)) {
            $lookupDictionary = $this->facet->getDictionary()->getStringByIds($dictionaryID);
        }

        if (!empty($directoryPredict)) {
            foreach ($directoryPredict as $directory) {
                if (empty($directory['VALUE']) || !is_array($directory['VALUE']))
                    continue;
                $values = [];
                foreach ($directory['VALUE'] as $item) {
                    if (isset($lookupDictionary[$item]))
                        $values[] = $lookupDictionary[$item];
                }
                if (!empty($values))
                    $this->predictHlFetch($directory['PROPERTY'], $values);
                unset($values);
            }
            unset($directory);
        }

        foreach ($elements as $row) {
            $PID = $row['PID'];
            if ($resultItem["ITEMS"][$PID]["PROPERTY_TYPE"] == "N") {
                $this->fillItemValues($resultItem["ITEMS"][$PID], $row["MIN_VALUE_NUM"]);
                $this->fillItemValues($resultItem["ITEMS"][$PID], $row["MAX_VALUE_NUM"]);
                if ($row["VALUE_FRAC_LEN"] > 0)
                    $resultItem["ITEMS"][$PID]["DECIMALS"] = $row["VALUE_FRAC_LEN"];
            } elseif ($resultItem["ITEMS"][$PID]["DISPLAY_TYPE"] == "U") {
                $this->fillItemValues($resultItem["ITEMS"][$PID], FormatDate("Y-m-d", $row["MIN_VALUE_NUM"]));
                $this->fillItemValues($resultItem["ITEMS"][$PID], FormatDate("Y-m-d", $row["MAX_VALUE_NUM"]));
            } elseif ($resultItem["ITEMS"][$PID]["PROPERTY_TYPE"] == "S") {
                $addedKey = $this->fillItemValues($resultItem["ITEMS"][$PID], $lookupDictionary[$row["VALUE"]], true);
                if (mb_strlen($addedKey) > 0) {
                    $resultItem["ITEMS"][$PID]["VALUES"][$addedKey]["FACET_VALUE"] = $row["VALUE"];
                    $resultItem["ITEMS"][$PID]["VALUES"][$addedKey]["ELEMENT_COUNT"] = $row["ELEMENT_COUNT"];
                }
            } else {
                $addedKey = $this->fillItemValues($resultItem["ITEMS"][$PID], $row["VALUE"], true);
                if (mb_strlen($addedKey) > 0) {
                    $resultItem["ITEMS"][$PID]["VALUES"][$addedKey]["FACET_VALUE"] = $row["VALUE"];
                    $resultItem["ITEMS"][$PID]["VALUES"][$addedKey]["ELEMENT_COUNT"] = $row["ELEMENT_COUNT"];
                }
            }
        }
    }

    public function predictIBSectionFetch($id = array())
    {
        if (!is_array($id) || empty($id)) {
            return;
        }

        $arLinkFilter = array(
            "ID" => $id,
            "GLOBAL_ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
        );

        $link = \CIBlockSection::GetList(array(), $arLinkFilter, false, array("ID", "IBLOCK_ID", "NAME", "LEFT_MARGIN", "DEPTH_LEVEL", "CODE"));
        while ($sec = $link->Fetch()) {
            $this->cache['G'][$sec['ID']] = $sec;
            $this->cache['G'][$sec['ID']]['DEPTH_NAME'] = str_repeat(".", $sec["DEPTH_LEVEL"]) . $sec["NAME"];
        }
        unset($sec);
        unset($link);
    }

    public function predictIBElementFetch($id = array())
    {
        if (!is_array($id) || empty($id)) {
            return;
        }

        $linkFilter = array(
            "ID" => $id,
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
        );

        $link = \CIBlockElement::GetList(array(), $linkFilter, false, false, array("ID", "IBLOCK_ID", "NAME", "SORT", "CODE"));
        while ($el = $link->Fetch()) {
            $this->cache['E'][$el['ID']] = $el;
        }
        unset($el);
        unset($link);
    }

    public function predictHlFetch($userType, $valueIDs)
    {
        $values = call_user_func_array(
            $userType['GetExtendedValue'],
            array(
                $userType,
                array("VALUE" => $valueIDs),
            )
        );

        foreach ($values as $key => $value) {
            $this->cache[$userType['PID']][$key] = $value;
        }
    }

    public function fillItemPrices(&$resultItem, $arElement)
    {
        if (isset($arElement["MIN_VALUE_NUM"]) && isset($arElement["MAX_VALUE_NUM"])) {
            $currency = (string)$arElement["VALUE"];
            $existCurrency = $currency !== '';
            if ($existCurrency)
                $currency = $this->facet->lookupDictionaryValue($currency);

            $priceValue = $this->convertPrice($arElement["MIN_VALUE_NUM"], $currency);
            if (
                !isset($resultItem["VALUES"]["MIN"]["VALUE"])
                || $resultItem["VALUES"]["MIN"]["VALUE"] > $priceValue
            ) {
                $resultItem["VALUES"]["MIN"]["VALUE"] = $priceValue;
                if ($existCurrency) {
                    if ($this->convertCurrencyId)
                        $resultItem["VALUES"]["MIN"]["CURRENCY"] = $this->convertCurrencyId;
                    else
                        $resultItem["VALUES"]["MIN"]["CURRENCY"] = $currency;
                }
            }

            $priceValue = $this->convertPrice($arElement["MAX_VALUE_NUM"], $currency);
            if (
                !isset($resultItem["VALUES"]["MAX"]["VALUE"])
                || $resultItem["VALUES"]["MAX"]["VALUE"] < $priceValue
            ) {
                $resultItem["VALUES"]["MAX"]["VALUE"] = $priceValue;
                if ($existCurrency) {
                    if ($this->convertCurrencyId)
                        $resultItem["VALUES"]["MAX"]["CURRENCY"] = $this->convertCurrencyId;
                    else
                        $resultItem["VALUES"]["MAX"]["CURRENCY"] = $currency;
                }
            }
        } else {
            $newFormat = array_key_exists("PRICE_" . $resultItem["ID"], $arElement);
            if ($newFormat) {
                $currency = (string)$arElement["CURRENCY_" . $resultItem["ID"]];
                $price = (string)$arElement["PRICE_" . $resultItem["ID"]];
            } else {
                $currency = (string)$arElement["CATALOG_CURRENCY_" . $resultItem["ID"]];
                $price = (string)$arElement["CATALOG_PRICE_" . $resultItem["ID"]];
            }
            $existCurrency = $currency !== '';
            if ($price !== '') {
                if ($this->convertCurrencyId && $existCurrency) {
                    $convertPrice = CCurrencyRates::ConvertCurrency($price, $currency, $this->convertCurrencyId);
                    $this->currencyTagList[$currency] = $currency;
                } else {
                    $convertPrice = (float)$price;
                }

                if (
                    !isset($resultItem["VALUES"]["MIN"])
                    || !array_key_exists("VALUE", $resultItem["VALUES"]["MIN"])
                    || (float)$resultItem["VALUES"]["MIN"]["VALUE"] > $convertPrice
                ) {
                    $resultItem["VALUES"]["MIN"]["VALUE"] = $price;
                    if ($existCurrency) {
                        if ($this->convertCurrencyId)
                            $resultItem["VALUES"]["MIN"]["CURRENCY"] = $this->convertCurrencyId;
                        else
                            $resultItem["VALUES"]["MIN"]["CURRENCY"] = $currency;
                    }
                }

                if (
                    !isset($resultItem["VALUES"]["MAX"])
                    || !array_key_exists("VALUE", $resultItem["VALUES"]["MAX"])
                    || (float)$resultItem["VALUES"]["MAX"]["VALUE"] < $convertPrice
                ) {
                    $resultItem["VALUES"]["MAX"]["VALUE"] = $price;
                    if ($existCurrency) {
                        if ($this->convertCurrencyId)
                            $resultItem["VALUES"]["MAX"]["CURRENCY"] = $this->convertCurrencyId;
                        else
                            $resultItem["VALUES"]["MAX"]["CURRENCY"] = $currency;
                    }
                }
            }
        }

        if ($existCurrency) {
            if ($this->convertCurrencyId) {
                $resultItem["CURRENCIES"][$this->convertCurrencyId] = (
                isset($this->currencyCache[$this->convertCurrencyId])
                    ? $this->currencyCache[$this->convertCurrencyId]
                    : $this->getCurrencyFullName($this->convertCurrencyId)
                );
                $resultItem["~CURRENCIES"][$currency] = (
                isset($this->currencyCache[$currency])
                    ? $this->currencyCache[$currency]
                    : $this->getCurrencyFullName($currency)
                );
            } else {
                $resultItem["CURRENCIES"][$currency] = (
                isset($this->currencyCache[$currency])
                    ? $this->currencyCache[$currency]
                    : $this->getCurrencyFullName($currency)
                );
            }
        }
    }

    public function convertPrice($price, $currency)
    {
        if ($this->convertCurrencyId && $currency) {
            $priceValue = CCurrencyRates::ConvertCurrency($price, $currency, $this->convertCurrencyId);
            $this->currencyTagList[$currency] = $currency;
        } else {
            $priceValue = $price;
        }
        return $priceValue;
    }

    public function fillItemValues(&$resultItem, $arProperty, $flag = null)
    {
        if (is_array($arProperty)) {
            if (isset($arProperty["PRICE"])) {
                return null;
            }
            $key = $arProperty["VALUE"];
            $PROPERTY_TYPE = $arProperty["PROPERTY_TYPE"];
            $PROPERTY_USER_TYPE = $arProperty["USER_TYPE"];
            $PROPERTY_ID = $arProperty["ID"];
        } else {
            $key = $arProperty;
            $PROPERTY_TYPE = $resultItem["PROPERTY_TYPE"];
            $PROPERTY_USER_TYPE = $resultItem["USER_TYPE"];
            $PROPERTY_ID = $resultItem["ID"];
            $arProperty = $resultItem;
        }

        if ($PROPERTY_TYPE == "F") {
            return null;
        } elseif ($PROPERTY_TYPE == "N") {
            $convertKey = (float)$key;
            if (mb_strlen($key) <= 0) {
                return null;
            }

            if (
                !isset($resultItem["VALUES"]["MIN"])
                || !array_key_exists("VALUE", $resultItem["VALUES"]["MIN"])
                || doubleval($resultItem["VALUES"]["MIN"]["VALUE"]) > $convertKey
            )
                $resultItem["VALUES"]["MIN"]["VALUE"] = preg_replace("/\\.0+\$/", "", $key);

            if (
                !isset($resultItem["VALUES"]["MAX"])
                || !array_key_exists("VALUE", $resultItem["VALUES"]["MAX"])
                || doubleval($resultItem["VALUES"]["MAX"]["VALUE"]) < $convertKey
            )
                $resultItem["VALUES"]["MAX"]["VALUE"] = preg_replace("/\\.0+\$/", "", $key);

            return null;
        } elseif ($arProperty["DISPLAY_TYPE"] == "U") {
            $date = mb_substr($key, 0, 10);
            if (!$date) {
                return null;
            }
            $timestamp = MakeTimeStamp($date, "YYYY-MM-DD");
            if (!$timestamp) {
                return null;
            }

            if (
                !isset($resultItem["VALUES"]["MIN"])
                || !array_key_exists("VALUE", $resultItem["VALUES"]["MIN"])
                || $resultItem["VALUES"]["MIN"]["VALUE"] > $timestamp
            )
                $resultItem["VALUES"]["MIN"]["VALUE"] = $timestamp;

            if (
                !isset($resultItem["VALUES"]["MAX"])
                || !array_key_exists("VALUE", $resultItem["VALUES"]["MAX"])
                || $resultItem["VALUES"]["MAX"]["VALUE"] < $timestamp
            )
                $resultItem["VALUES"]["MAX"]["VALUE"] = $timestamp;

            return null;
        } elseif ($PROPERTY_TYPE == "E" && $key <= 0) {
            return null;
        } elseif ($PROPERTY_TYPE == "G" && $key <= 0) {
            return null;
        } elseif (mb_strlen($key) <= 0) {
            return null;
        }

        $arUserType = array();
        if ($PROPERTY_USER_TYPE != "") {
            $arUserType = \CIBlockProperty::GetUserType($PROPERTY_USER_TYPE);
            if (isset($arUserType["GetExtendedValue"]))
                $PROPERTY_TYPE = "Ux";
            elseif (isset($arUserType["GetPublicViewHTML"]))
                $PROPERTY_TYPE = "U";
        }

        if ($PROPERTY_USER_TYPE === "DateTime") {
            $key = call_user_func_array(
                $arUserType["GetPublicViewHTML"],
                array(
                    $arProperty,
                    array("VALUE" => $key),
                    array("MODE" => "SIMPLE_TEXT", "DATETIME_FORMAT" => "SHORT"),
                )
            );
            $PROPERTY_TYPE = "S";
        }

        $htmlKey = htmlspecialcharsbx($key);
        if (isset($resultItem["VALUES"][$htmlKey])) {
            return $htmlKey;
        }

        $file_id = null;
        $url_id = null;

        switch ($PROPERTY_TYPE) {
            case "L":
                $enum = \CIBlockPropertyEnum::GetByID($key);
                if ($enum) {
                    $value = $enum["VALUE"];
                    $sort = $enum["SORT"];
                    $url_id = toLower($enum["XML_ID"]);
                } else {
                    return null;
                }
                break;
            case "E":
                if (!isset($this->cache[$PROPERTY_TYPE][$key])) {
                    $this->predictIBElementFetch(array($key));
                }

                if (!$this->cache[$PROPERTY_TYPE][$key])
                    return null;

                $value = $this->cache[$PROPERTY_TYPE][$key]["NAME"];
                $sort = $this->cache[$PROPERTY_TYPE][$key]["SORT"];
                if ($this->cache[$PROPERTY_TYPE][$key]["CODE"])
                    $url_id = toLower($this->cache[$PROPERTY_TYPE][$key]["CODE"]);
                else
                    $url_id = toLower($value);
                break;
            case "G":
                if (!isset($this->cache[$PROPERTY_TYPE][$key])) {
                    $this->predictIBSectionFetch(array($key));
                }

                if (!$this->cache[$PROPERTY_TYPE][$key])
                    return null;

                $value = $this->cache[$PROPERTY_TYPE][$key]['DEPTH_NAME'];
                $sort = $this->cache[$PROPERTY_TYPE][$key]["LEFT_MARGIN"];
                if ($this->cache[$PROPERTY_TYPE][$key]["CODE"])
                    $url_id = toLower($this->cache[$PROPERTY_TYPE][$key]["CODE"]);
                else
                    $url_id = toLower($value);
                break;
            case "U":
                if (!isset($this->cache[$PROPERTY_ID]))
                    $this->cache[$PROPERTY_ID] = array();

                if (!isset($this->cache[$PROPERTY_ID][$key])) {
                    $this->cache[$PROPERTY_ID][$key] = call_user_func_array(
                        $arUserType["GetPublicViewHTML"],
                        array(
                            $arProperty,
                            array("VALUE" => $key),
                            array("MODE" => "SIMPLE_TEXT"),
                        )
                    );
                }

                $value = $this->cache[$PROPERTY_ID][$key];
                $sort = 0;
                $url_id = toLower($value);
                break;
            case "Ux":
                if (!isset($this->cache[$PROPERTY_ID]))
                    $this->cache[$PROPERTY_ID] = array();

                if (!isset($this->cache[$PROPERTY_ID][$key])) {
                    $this->cache[$PROPERTY_ID][$key] = call_user_func_array(
                        $arUserType["GetExtendedValue"],
                        array(
                            $arProperty,
                            array("VALUE" => $key),
                        )
                    );
                }

                if ($this->cache[$PROPERTY_ID][$key]) {
                    $value = $this->cache[$PROPERTY_ID][$key]['VALUE'];
                    $file_id = $this->cache[$PROPERTY_ID][$key]['FILE_ID'];
                    $sort = (isset($this->cache[$PROPERTY_ID][$key]['SORT']) ? $this->cache[$PROPERTY_ID][$key]['SORT'] : 0);
                    $url_id = toLower($this->cache[$PROPERTY_ID][$key]['UF_XML_ID']);
                } else {
                    return null;
                }
                break;
            default:
                $value = $key;
                $sort = 0;
                $url_id = toLower($value);
                break;
        }

        $keyCrc = abs(crc32($htmlKey));
        $safeValue = htmlspecialcharsex($value);
        $sort = (int)$sort;

        $filterPropertyID = $this->SAFE_FILTER_NAME . '_' . $PROPERTY_ID;
        $filterPropertyIDKey = $filterPropertyID . '_' . $keyCrc;
        $resultItem["VALUES"][$htmlKey] = array(
            "CONTROL_ID" => $filterPropertyIDKey,
            "CONTROL_NAME" => $filterPropertyIDKey,
            "CONTROL_NAME_ALT" => $filterPropertyID,
            "HTML_VALUE_ALT" => $keyCrc,
            "HTML_VALUE" => "Y",
            "VALUE" => $safeValue,
            "SORT" => $sort,
            "UPPER" => ToUpper($safeValue),
            "FLAG" => $flag,
        );

        if ($file_id) {
            $resultItem["VALUES"][$htmlKey]['FILE'] = \CFile::GetFileArray($file_id);
        }

        if (mb_strlen($url_id)) {
            $error = "";
            $utf_id = \Bitrix\Main\Text\Encoding::convertEncoding($url_id, LANG_CHARSET, "utf-8", $error);
            $resultItem["VALUES"][$htmlKey]['URL_ID'] = rawurlencode(str_replace("/", "-", $utf_id));
        }

        return $htmlKey;
    }

    function combineCombinations(&$arCombinations)
    {
        $result = array();
        foreach ($arCombinations as $arCombination) {
            foreach ($arCombination as $PID => $value) {
                if (!isset($result[$PID]))
                    $result[$PID] = array();
                if (mb_strlen($value))
                    $result[$PID][] = $value;
            }
        }
        return $result;
    }

    function filterCombinations(&$arCombinations, $arItems, $currentPID)
    {
        foreach ($arCombinations as $key => $arCombination) {
            if (!$this->combinationMatch($arCombination, $arItems, $currentPID))
                unset($arCombinations[$key]);
        }
    }

    function combinationMatch($combination, $arItems, $currentPID)
    {
        foreach ($arItems as $PID => $arItem) {
            if ($PID != $currentPID) {
                if ($arItem["PROPERTY_TYPE"] == "N" || isset($arItem["PRICE"])) {
                    //TODO
                } else {
                    if (!$this->matchProperty($combination[$PID], $arItem["VALUES"]))
                        return false;
                }
            }
        }
        return true;
    }

    function matchProperty($value, $arValues)
    {
        $match = true;
        foreach ($arValues as $formControl) {
            if ($formControl["CHECKED"]) {
                if ($formControl["VALUE"] == $value)
                    return true;
                else
                    $match = false;
            }
        }
        return $match;
    }

    public function _sort($v1, $v2)
    {
        if ($v1["SORT"] < $v2["SORT"])
            return -1;
        elseif ($v1["SORT"] > $v2["SORT"])
            return 1;
        else
            return strcmp($v1["UPPER"], $v2["UPPER"]);
    }

    /*
    This function takes an array (arTuple) which is mix of scalar values and arrays
    and return "rectangular" array of arrays.
    For example:
    array(1, array(1, 2), 3, arrays(4, 5))
    will be transformed as
    array(
        array(1, 1, 3, 4),
        array(1, 1, 3, 5),
        array(1, 2, 3, 4),
        array(1, 2, 3, 5),
    )
    */
    function ArrayMultiply(&$arResult, $arTuple, $arTemp = array())
    {
        if ($arTuple) {
            reset($arTuple);
            list($key, $head) = each($arTuple);
            unset($arTuple[$key]);
            $arTemp[$key] = false;
            if (is_array($head)) {
                if (empty($head)) {
                    if (empty($arTuple))
                        $this->arResult[] = $arTemp;
                    else
                        $this->ArrayMultiply($this->arResult, $arTuple, $arTemp);
                } else {
                    foreach ($head as $value) {
                        $arTemp[$key] = $value;
                        if (empty($arTuple))
                            $this->arResult[] = $arTemp;
                        else
                            $this->ArrayMultiply($this->arResult, $arTuple, $arTemp);
                    }
                }
            } else {
                $arTemp[$key] = $head;
                if (empty($arTuple))
                    $this->arResult[] = $arTemp;
                else
                    $this->ArrayMultiply($this->arResult, $arTuple, $arTemp);
            }
        } else {
            $this->arResult[] = $arTemp;
        }
    }

    function makeFilter($FILTER_NAME)
    {
        $bOffersIBlockExist = false;
        if (self::$catalogIncluded === null)
            self::$catalogIncluded = Loader::includeModule('catalog');
        if (self::$catalogIncluded && class_exists('CCatalogSku')) {
            $arCatalog = \CCatalogSKU::GetInfoByProductIBlock($this->IBLOCK_ID);
            if (!empty($arCatalog)) {
                $bOffersIBlockExist = true;
            }
        }

        $gFilter = $GLOBALS[$FILTER_NAME];

        $arFilter = array(
            "IBLOCK_ID" => $this->IBLOCK_ID,
            "IBLOCK_LID" => SITE_ID,
            "IBLOCK_ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            "ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
            "MIN_PERMISSION" => "R",
            "INCLUDE_SUBSECTIONS" => ($this->arParams["INCLUDE_SUBSECTIONS"] != 'N' ? 'Y' : 'N'),
        );
        if (($this->SECTION_ID > 0) || ($this->arParams["SHOW_ALL_WO_SECTION"] !== "Y")) {
            $arFilter["SECTION_ID"] = $this->SECTION_ID;
        }

        if ($this->arParams['HIDE_NOT_AVAILABLE'] == 'Y')
            $arFilter['AVAILABLE'] = 'Y';

        if (self::$catalogIncluded && $bOffersIBlockExist) {
            $arPriceFilter = array();
            foreach ($gFilter as $key => $value) {
                if (\CProductQueryBuilder::isPriceFilterField($key)) {
                    $arPriceFilter[$key] = $value;
                    unset($gFilter[$key]);
                }
            }

            if (!empty($gFilter["OFFERS"])) {
                if (empty($arPriceFilter))
                    $arSubFilter = $gFilter["OFFERS"];
                else
                    $arSubFilter = array_merge($gFilter["OFFERS"], $arPriceFilter);

                $arSubFilter["IBLOCK_ID"] = $this->SKU_IBLOCK_ID;
                $arSubFilter["ACTIVE_DATE"] = "Y";
                $arSubFilter["ACTIVE"] = "Y";
                if ('Y' == $this->arParams['HIDE_NOT_AVAILABLE'])
                    $arSubFilter['AVAILABLE'] = 'Y';
                $arFilter["=ID"] = \CIBlockElement::SubQuery("PROPERTY_" . $this->SKU_PROPERTY_ID, $arSubFilter);
            } elseif (!empty($arPriceFilter)) {
                $arSubFilter = $arPriceFilter;

                $arSubFilter["IBLOCK_ID"] = $this->SKU_IBLOCK_ID;
                $arSubFilter["ACTIVE_DATE"] = "Y";
                $arSubFilter["ACTIVE"] = "Y";
                $arFilter[] = array(
                    "LOGIC" => "OR",
                    array($arPriceFilter),
                    "=ID" => \CIBlockElement::SubQuery("PROPERTY_" . $this->SKU_PROPERTY_ID, $arSubFilter),
                );
            }

            unset($gFilter["OFFERS"]);
        }

        return array_merge($gFilter, $arFilter);
    }

    public function getCurrencyFullName($currencyId)
    {
        if (!isset($this->currencyCache[$currencyId])) {
            $currencyInfo = \CCurrencyLang::GetById($currencyId, LANGUAGE_ID);
            if ($currencyInfo["FULL_NAME"] != "")
                $this->currencyCache[$currencyId] = $currencyInfo["FULL_NAME"];
            else
                $this->currencyCache[$currencyId] = $currencyId;
        }
        return $this->currencyCache[$currencyId];
    }

    public function searchPrice($items, $lookupValue)
    {
        $error = "";
        $searchValue = \Bitrix\Main\Text\Encoding::convertEncoding($lookupValue, LANG_CHARSET, "utf-8", $error);
        if (!$error) {
            $encodedValue = rawurlencode($searchValue);
            foreach ($items as $itemId => $arItem) {
                if ($arItem["PRICE"]) {
                    $code = toLower($arItem["CODE"]);
                    if ($lookupValue === $code || $encodedValue === $arItem["URL_ID"])
                        return $itemId;
                }
            }
        }
        return null;
    }

    public function searchProperty($items, $lookupValue)
    {
        foreach ($items as $itemId => $arItem) {
            if (!$arItem["PRICE"]) {
                $code = toLower($arItem["CODE"]);
                if ($lookupValue === $code)
                    return $itemId;
                if ($lookupValue == intval($arItem["ID"]))
                    return $itemId;
            }
        }
        return null;
    }

    public function searchValue($item, $lookupValue)
    {
        $error = "";
        $searchValue = \Bitrix\Main\Text\Encoding::convertEncoding($lookupValue, LANG_CHARSET, "utf-8", $error);
        if (!$error) {
            $encodedValue = rawurlencode($searchValue);
            foreach ($item as $itemId => $arValue) {
                if ($encodedValue === $arValue["URL_ID"])
                    return $itemId;
            }
        }
        return false;
    }

    public function convertUrlToCheck($url)
    {
        $result = array();
        $smartParts = explode("/", $url);
        foreach ($smartParts as $smartPart) {
            $item = false;
            $smartPart = preg_split("/-(from|to|is|or)-/", $smartPart, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($smartPart as $i => $smartElement) {
                if ($i == 0) {
                    if (preg_match("/^price-(.+)$/", $smartElement, $match))
                        $itemId = $this->searchPrice($this->arResult["ITEMS"], $match[1]);
                    else
                        $itemId = $this->searchProperty($this->arResult["ITEMS"], $smartElement);

                    if (isset($itemId))
                        $item = &$this->arResult["ITEMS"][$itemId];
                    else
                        break;
                } elseif ($smartElement === "from") {
                    $result[$item["VALUES"]["MIN"]["CONTROL_NAME"]] = $smartPart[$i + 1];
                } elseif ($smartElement === "to") {
                    $result[$item["VALUES"]["MAX"]["CONTROL_NAME"]] = $smartPart[$i + 1];
                } elseif ($smartElement === "is" || $smartElement === "or") {
                    $valueId = $this->searchValue($item["VALUES"], $smartPart[$i + 1]);
                    if (mb_strlen($valueId)) {
                        $result[$item["VALUES"][$valueId]["CONTROL_NAME"]] = $item["VALUES"][$valueId]["HTML_VALUE"];
                    }
                }
            }
            unset($item);
        }
        return $result;
    }

    public function makeSmartUrl($url, $apply, $checkedControlId = false)
    {
        $smartParts = array();

        if ($apply) {
            foreach ($this->arResult["ITEMS"] as $PID => $arItem) {
                $smartPart = array();
                //Prices
                if ($arItem["PRICE"]) {
                    if (mb_strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0)
                        $smartPart["from"] = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
                    if (mb_strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0)
                        $smartPart["to"] = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
                }

                if ($smartPart) {
                    array_unshift($smartPart, "price-" . $arItem["URL_ID"]);

                    $smartParts[] = $smartPart;
                }
            }

            foreach ($this->arResult["ITEMS"] as $PID => $arItem) {
                $smartPart = array();
                if ($arItem["PRICE"])
                    continue;

                //Numbers && calendar == ranges
                if (
                    $arItem["PROPERTY_TYPE"] == "N"
                    || $arItem["DISPLAY_TYPE"] == "U"
                ) {
                    if (mb_strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0)
                        $smartPart["from"] = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
                    if (mb_strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0)
                        $smartPart["to"] = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
                } else {
                    foreach ($arItem["VALUES"] as $key => $ar) {
                        if (
                            (
                                $ar["CHECKED"]
                                || $ar["CONTROL_ID"] === $checkedControlId
                            )
                            && mb_strlen($ar["URL_ID"])
                        ) {
                            $smartPart[] = $ar["URL_ID"];
                        }
                    }
                }

                if ($smartPart) {
                    if ($arItem["CODE"])
                        array_unshift($smartPart, toLower($arItem["CODE"]));
                    else
                        array_unshift($smartPart, $arItem["ID"]);

                    $smartParts[] = $smartPart;
                }
            }
        }

        if (!$smartParts)
            $smartParts[] = array("clear");

        return str_replace("#SMART_FILTER_PATH#", implode("/", $this->encodeSmartParts($smartParts)), $url);
    }

    public function encodeSmartParts($smartParts)
    {
        foreach ($smartParts as &$smartPart) {
            $urlPart = "";
            foreach ($smartPart as $i => $smartElement) {
                if (!$urlPart)
                    $urlPart .= $smartElement;
                elseif ($i == 'from' || $i == 'to')
                    $urlPart .= '-' . $i . '-' . $smartElement;
                elseif ($i == 1)
                    $urlPart .= '-is-' . $smartElement;
                else
                    $urlPart .= '-or-' . $smartElement;
            }
            $smartPart = $urlPart;
        }
        unset($smartPart);
        return $smartParts;
    }

    public function setCurrencyTag()
    {
        if (
            $this->convertCurrencyId != ''
            && !empty($this->currencyTagList)
            && defined('BX_COMP_MANAGED_CACHE')
        ) {
            $this->currencyTagList[$this->convertCurrencyId] = $this->convertCurrencyId;
            $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
            foreach ($this->currencyTagList as &$oneCurrency)
                $taggedCache->registerTag('currency_id_' . $oneCurrency);
            unset($oneCurrency);
        }
    }

    public function setIblockTag()
    {
        if (
        defined('BX_COMP_MANAGED_CACHE')
        ) {
            \CIBlock::registerWithTagCache($this->IBLOCK_ID);
            if ($this->SKU_IBLOCK_ID > 0)
                \CIBlock::registerWithTagCache($this->SKU_IBLOCK_ID);
        }
    }
}

?>


