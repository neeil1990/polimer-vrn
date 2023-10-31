<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\ChpuTagsTable;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Context;

if(!Loader::includeModule('sotbit.seometa') || !Loader::includeModule('iblock'))
{
    return false;
}

global $sotbitFilterResult;
global $SeoMetaWorkingConditions;

if(!$arParams['CACHE_TIME'])
{
    $arParams['CACHE_TIME'] = '36000000';
}

$currentPage = $APPLICATION->getCurPage();

if($arParams['EXCLUDE_PROPERTY_LIST']) {
    $arParams['EXCLUDE_PROPERTY_LIST'] = array_merge($arParams['EXCLUDE_PROPERTY_LIST'],
        [$arParams['BRANDS_LIST']]);
} else {
    $arParams['EXCLUDE_PROPERTY_LIST'] = [];
}

$cacheTime = $arParams['CACHE_TIME'];
$cache_id = serialize(array($arParams, $currentPage, ($arParams['CACHE_GROUPS'] === 'N' ? false : $USER->GetGroups())));
$cacheDir = '/sotbit.seometa.bottom-menu/';
$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
$arrProperties = array();

if($cache->read($cacheTime, $cache_id, $cacheDir))
{
    $items = $cache->get($cache_id);
}
else
{
    if($arParams['KOMBOX_FILTER'] == 'Y' && CModule::IncludeModule('kombox.filter'))
    {
        $str = CKomboxFilter::GetCurPageParam();
        $str = explode("?", $str);
        $str = $str[0];
    }
    else
    {
        $str = $APPLICATION->GetCurPage();
    }

    $str = CSeoMeta::encodeRealUrl($str);
    $metaData = SeometaUrlTable::getByRealUrl($str, SITE_ID);

    if (!$metaData) {
        $requestGet = Context::getCurrent()->getRequest()->getQueryList()->toArray();
        $pageParams = array_keys($requestGet);
        $str = $APPLICATION->GetCurPageParam(
            '',
            $pageParams
        );
        $str = CSeoMeta::encodeRealUrl($str);
        $metaData = SeometaUrlTable::getByRealUrl($str, SITE_ID);
    }

    if(!$metaData) {
        $metaData = SeometaUrlTable::getByNewUrl($str, SITE_ID);
    }

    if($metaData['ID']) {
        $arrProperties = ChpuTagsTable::getByChpuID($metaData['ID']);

        if($arrProperties['TAG_OVERRIDE_TYPE'] == 'Y') {
            $arrIDs = array_diff($arrProperties['TAG_DATA'], $arParams['EXCLUDE_PROPERTY_LIST']);

            $conditionId = $metaData['CONDITION_ID'];
            $sectionId = $metaData['section_id'];

            $smartFilter = new \Sotbit\Seometa\Filter\SmartFilter($conditionId);
            $rule = new Sotbit\Seometa\Condition\Rule();
            $condition = $rule->parse($smartFilter->getCondition());

            $generator = Sotbit\Seometa\Generator\GeneratorFactory::create($smartFilter->getCondition()->FILTER_TYPE);

            $catalogUrl = new Sotbit\Seometa\Url\CatalogUrl($smartFilter->getCondition());
//    $catalogUrl->cleanTemplate(true);
            $catalogUrl->setSectionPlaceholdersIfNeed($sectionId);

            $propertySet = new Sotbit\Seometa\Property\PropertySet();
            $propertySetEntity = new Sotbit\Seometa\Property\PropertySetEntity($condition->getData()[0]->getData()[0]->getData());
            $propertySet->add($propertySetEntity);

            $catalogUrl->replaceFromSet($propertySet, $generator);
            $catalogUrl->getMask();

            foreach ($arrIDs as $key => $arrID) {
                if($sotbitFilterResult['ITEMS'][$arrID]['VALUES']) {
                    foreach ($sotbitFilterResult['ITEMS'][$arrID]['VALUES'] as  $value) {
                        if(!$link = SeometaUrlTable::getByRealUrl($value['section_filter_link'], SITE_ID)) {
                            $link = SeometaUrlTable::getByNewUrl($value['section_filter_link'], SITE_ID);
                        }

                        if($link) {
                            $item['NAME'] = $value['VALUE'];
                            $item['URL'] = $link['REAL_URL'];
                            $items['BOTTOM_MENU_PROPERTIES']['SECTION'][$key]['LINKS'][] = $item;
                        }
                    }

                    if($items['BOTTOM_MENU_PROPERTIES']['SECTION'][$key]['LINKS']) {
                        $items['BOTTOM_MENU_PROPERTIES']['SECTION'][$key]['NAME'] = $sotbitFilterResult['ITEMS'][$arrID]['NAME'];
                    }
                }
            }
        } else if($arrProperties['TAG_OVERRIDE_TYPE'] == 'M' && $arrProperties['TAG_DATA']['SECTION']) {
            $items['BOTTOM_MENU_PROPERTIES'] = $arrProperties['TAG_DATA'];
        }
    }

    if($arrProperties['TAG_OVERRIDE_TYPE'] != 'Y' && $arrProperties['TAG_OVERRIDE_TYPE'] != 'M') {
        $arLinksInSection = SeometaUrlTable::getList([
            'filter' => [
                'ACTIVE' => 'Y',
                'section_id' => $sotbitFilterResult['SECTION_ID']
            ],
            'select' => [
                'NEW_URL',
                'SITE_ID',
                'iblock_id',
                'PROPERTIES'
            ]
        ]);


        $section = '';
        while ($linkSection = $arLinksInSection->fetch()) {
            $siteID = unserialize($linkSection['SITE_ID']);
            if ($siteID && in_array(SITE_ID,
                    $siteID)) {
                $properties = unserialize($linkSection['PROPERTIES']);

                foreach ($properties as $key => $arrVal) {
                    foreach ($arrVal as $val) {
                        $arrChpuProps[$key][$val] = $linkSection['NEW_URL'];
                    }
                }
            }
        }

        if ($arrChpuProps) {
            foreach ($arrChpuProps as $propCODE => $prop) {
                $iblockProperty = CIBlockProperty::GetList(
                    array(),
                    array(
                        'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                        'CODE' => $propCODE
                    )
                )->fetch();

                if (!$iblockProperty['ID'] && Loader::includeModule('catalog')) {
                    $skuIblockId = CCatalog::GetList(
                        array(),
                        array(
                            'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                            'CODE' => $propCODE
                        ),
                        false,
                        false,
                        array('OFFERS_IBLOCK_ID')
                    )->fetch();

                    if ($skuIblockId) {
                        $iblockProperty = CIBlockProperty::GetList(
                            array(),
                            array(
                                'IBLOCK_ID' => $skuIblockId['OFFERS_IBLOCK_ID'],
                                'CODE' => $propCODE
                            )
                        )->fetch();
                    }
                }

                if (
                    $iblockProperty['ID'] &&
                    $sotbitFilterResult['ITEMS'][$iblockProperty['ID']] &&
                    !in_array($iblockProperty['ID'],
                        $arParams['EXCLUDE_PROPERTY_LIST'])
                ) {
                    foreach ($prop as $propKey => $pr) {
                        foreach ($sotbitFilterResult['ITEMS'][$iblockProperty['ID']]['VALUES'] as $VALUE) {
                            if ($VALUE['UPPER'] == mb_strtoupper($propKey)) {
                                $link['NAME'] = $propKey;
                                $link['URL'] = $pr;

                                $items['BOTTOM_MENU_PROPERTIES']['SECTION'][$propCODE]['LINKS'][] = $link;
                            }
                        }
                    }

                    if (count($items['BOTTOM_MENU_PROPERTIES']['SECTION'][$propCODE]['LINKS']) > 0) {
                        $items['BOTTOM_MENU_PROPERTIES']['SECTION'][$propCODE]['NAME'] = $sotbitFilterResult['ITEMS'][$iblockProperty['ID']]['NAME'];
                    }
                }
            }
        }
    }

    if($arParams['BRANDS_LIST'] && $sotbitFilterResult['ITEMS'][$arParams['BRANDS_LIST']]) {
        $items['BRANDS_LIST'] = [];
        foreach ($sotbitFilterResult['ITEMS'][$arParams['BRANDS_LIST']]['VALUES'] as $brandListItem) {
            if($brandListItem['section_filter_link']) {
                $brandItem['NAME'] = $brandListItem['VALUE'];
                $brandItem['URL'] = $brandListItem['section_filter_link'];
                $items['BRANDS_LIST'][] = $brandItem;
            }
        }
    }

    $cache->set($cache_id, $items);
}

$arResult = $items;

$this->IncludeComponentTemplate();
?>