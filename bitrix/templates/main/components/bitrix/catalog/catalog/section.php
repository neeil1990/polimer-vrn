<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

$this->setFrameMode(true);

if(empty($_REQUEST['PAGE_ELEMENT_COUNT'])){
    $_REQUEST['PAGE_ELEMENT_COUNT'] = $arParams['PAGE_ELEMENT_COUNT'];
}
?>

			<?
			if(ModuleManager::isModuleInstalled("sale"))
			{
				$arRecomData = array();
				$recomCacheID = array('IBLOCK_ID' => $arParams['IBLOCK_ID']);
				$obCache = new CPHPCache();
				if ($obCache->InitCache(36000, serialize($recomCacheID), "/sale/bestsellers"))
				{
					$arRecomData = $obCache->GetVars();
				}
				elseif ($obCache->StartDataCache())
				{
					if (Loader::includeModule("catalog"))
					{
						$arSKU = CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);
						$arRecomData['OFFER_IBLOCK_ID'] = (!empty($arSKU) ? $arSKU['IBLOCK_ID'] : 0);
					}
					$obCache->EndDataCache($arRecomData);
				}
			}


            $arFilter = array('IBLOCK_ID' => $arParams["IBLOCK_ID"],"CODE" => $arResult['VARIABLES']['SECTION_CODE']);
            $rsSect = CIBlockSection::GetList(Array("SORT"=>"ASC"),$arFilter,false,
                array(
                    'UF_BROWSER_TITLE',
                    'UF_KEYWORDS',
                    'UF_META_DESCRIPTION',
                    'UF_SMART_SEO',
                    'UF_SHOW_NEW'
                )
            );
            $arSect = $rsSect->GetNext();

            $arSelect = Array("ID");
            $arFilter = Array("IBLOCK_ID"=> $arParams['IBLOCK_ID'],"SECTION_CODE" => $arResult['VARIABLES']['SECTION_CODE'], "ACTIVE"=>"Y");
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            if($res->GetNextElement()):
                include_once('theme/new.php');
            else:
                if($arSect['UF_SHOW_NEW'])
                    include_once('theme/new.php');
                else
                    include_once('theme/old.php');
            endif;
			?>

    <?
    $GLOBALS['CATALOG_CURRENT_SECTION_ID'] = $intSectionID;
    unset($basketAction);

    if (ModuleManager::isModuleInstalled("sale"))
    {
        if (!empty($arRecomData))
        {
            if (!isset($arParams['USE_SALE_BESTSELLERS']) || $arParams['USE_SALE_BESTSELLERS'] != 'N')
            {
                ?>
                    <?$APPLICATION->IncludeComponent("bitrix:sale.bestsellers", "", array(
                        "HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
                        "PAGE_ELEMENT_COUNT" => "5",
                        "SHOW_DISCOUNT_PERCENT" => $arParams['SHOW_DISCOUNT_PERCENT'],
                        "PRODUCT_SUBSCRIPTION" => $arParams['PRODUCT_SUBSCRIPTION'],
                        "SHOW_NAME" => "Y",
                        "SHOW_IMAGE" => "Y",
                        "MESS_BTN_BUY" => $arParams['MESS_BTN_BUY'],
                        "MESS_BTN_DETAIL" => $arParams['MESS_BTN_DETAIL'],
                        "MESS_NOT_AVAILABLE" => $arParams['MESS_NOT_AVAILABLE'],
                        "MESS_BTN_SUBSCRIBE" => $arParams['MESS_BTN_SUBSCRIBE'],
                        "LINE_ELEMENT_COUNT" => 5,
                        "TEMPLATE_THEME" => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
                        "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
                        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                        "CACHE_TIME" => $arParams["CACHE_TIME"],
                        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                        "BY" => array(
                            0 => "AMOUNT",
                        ),
                        "PERIOD" => array(
                            0 => "15",
                        ),
                        "FILTER" => array(
                            0 => "CANCELED",
                            1 => "ALLOW_DELIVERY",
                            2 => "PAYED",
                            3 => "DEDUCTED",
                            4 => "N",
                            5 => "P",
                            6 => "F",
                        ),
                        "FILTER_NAME" => $arParams["FILTER_NAME"],
                        "ORDER_FILTER_NAME" => "arOrderFilter",
                        "DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
                        "SHOW_OLD_PRICE" => $arParams['SHOW_OLD_PRICE'],
                        "PRICE_CODE" => $arParams["PRICE_CODE"],
                        "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
                        "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
                        "CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
                        "CURRENCY_ID" => $arParams["CURRENCY_ID"],
                        "BASKET_URL" => $arParams["BASKET_URL"],
                        "ACTION_VARIABLE" => (!empty($arParams["ACTION_VARIABLE"]) ? $arParams["ACTION_VARIABLE"] : "action")."_slb",
                        "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
                        "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
                        "ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
                        "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
                        "PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
                        "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
                        "SHOW_PRODUCTS_".$arParams["IBLOCK_ID"] => "Y",
                        "OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
                        "ADDITIONAL_PICT_PROP_".$arParams['IBLOCK_ID'] => $arParams['ADD_PICT_PROP'],
                        "ADDITIONAL_PICT_PROP_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams['OFFER_ADD_PICT_PROP']
                    ),
                        $component,
                        array("HIDE_ICONS" => "Y")
                    );?>

                <?
            }
            if (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N')
            {
                ?>

                    <?$APPLICATION->IncludeComponent("bitrix:catalog.bigdata.products", "", array(
                        "LINE_ELEMENT_COUNT" => 5,
                        "TEMPLATE_THEME" => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
                        "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
                        "BASKET_URL" => $arParams["BASKET_URL"],
                        "ACTION_VARIABLE" => (!empty($arParams["ACTION_VARIABLE"]) ? $arParams["ACTION_VARIABLE"] : "action")."_cbdp",
                        "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
                        "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
                        "ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
                        "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
                        "PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
                        "SHOW_OLD_PRICE" => $arParams['SHOW_OLD_PRICE'],
                        "SHOW_DISCOUNT_PERCENT" => $arParams['SHOW_DISCOUNT_PERCENT'],
                        "PRICE_CODE" => $arParams["PRICE_CODE"],
                        "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
                        "PRODUCT_SUBSCRIPTION" => $arParams['PRODUCT_SUBSCRIPTION'],
                        "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
                        "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
                        "SHOW_NAME" => "Y",
                        "SHOW_IMAGE" => "Y",
                        "MESS_BTN_BUY" => $arParams['MESS_BTN_BUY'],
                        "MESS_BTN_DETAIL" => $arParams['MESS_BTN_DETAIL'],
                        "MESS_BTN_SUBSCRIBE" => $arParams['MESS_BTN_SUBSCRIBE'],
                        "MESS_NOT_AVAILABLE" => $arParams['MESS_NOT_AVAILABLE'],
                        "PAGE_ELEMENT_COUNT" => 5,
                        "SHOW_FROM_SECTION" => "Y",
                        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                        "DEPTH" => "2",
                        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                        "CACHE_TIME" => $arParams["CACHE_TIME"],
                        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                        "SHOW_PRODUCTS_".$arParams["IBLOCK_ID"] => "Y",
                        "HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
                        "CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
                        "CURRENCY_ID" => $arParams["CURRENCY_ID"],
                        "SECTION_ID" => $intSectionID,
                        "SECTION_CODE" => "",
                        "SECTION_ELEMENT_ID" => "",
                        "SECTION_ELEMENT_CODE" => "",
                        "LABEL_PROP_".$arParams["IBLOCK_ID"] => $arParams['LABEL_PROP'],
                        "PROPERTY_CODE_".$arParams["IBLOCK_ID"] => $arParams["LIST_PROPERTY_CODE"],
                        "PROPERTY_CODE_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["LIST_OFFERS_PROPERTY_CODE"],
                        "CART_PROPERTIES_".$arParams["IBLOCK_ID"] => $arParams["PRODUCT_PROPERTIES"],
                        "CART_PROPERTIES_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFERS_CART_PROPERTIES"],
                        "ADDITIONAL_PICT_PROP_".$arParams["IBLOCK_ID"] => $arParams['ADD_PICT_PROP'],
                        "ADDITIONAL_PICT_PROP_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams['OFFER_ADD_PICT_PROP'],
                        "OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
                        "RCM_TYPE" => (isset($arParams['BIG_DATA_RCM_TYPE']) ? $arParams['BIG_DATA_RCM_TYPE'] : '')
                    ),
                        $component,
                        array("HIDE_ICONS" => "Y")
                    );?>

                <?
            }
        }
    }
    ?>


<?
//Переопределение метаинформации для модуля "Сотбит: SEO умного фильтра – мета-теги, заголовки, карта сайта"
//начало

    //Переопределение заголовка Title
    global $sotbitSeoMetaTitle;
    if(!empty($sotbitSeoMetaTitle)){
        $APPLICATION->SetPageProperty("title", $sotbitSeoMetaTitle);
    }

    //Переопределение ключевых слов Keywords
    global $sotbitSeoMetaKeywords;
    if(!empty($sotbitSeoMetaKeywords)){
        $APPLICATION->SetPageProperty("keywords", $sotbitSeoMetaKeywords);
    }

    //Переопределение описания страницы Description
    global $sotbitSeoMetaDescription;
    if(!empty($sotbitSeoMetaDescription)){
        $APPLICATION->SetPageProperty("description", $sotbitSeoMetaDescription);
    }

    //Переопределение заголовка H1
    global $sotbitSeoMetaH1;
    if(!empty($sotbitSeoMetaH1)){
             $APPLICATION->SetTitle($sotbitSeoMetaH1);
    }

    //Добавление пункта хлебных крошек Breadcrumb
    global $sotbitSeoMetaBreadcrumbTitle;
    if(!empty($sotbitSeoMetaBreadcrumbTitle)){
        $APPLICATION->AddChainItem($sotbitSeoMetaBreadcrumbTitle  );
    }
//конец
?>
