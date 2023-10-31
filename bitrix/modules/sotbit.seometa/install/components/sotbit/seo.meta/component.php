<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Iblock\Template\Engine;
use Bitrix\Iblock\Template\Entity\Section;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\Emoji;
use Sotbit\Seometa\Helper\OGraphTWCard;
use Sotbit\Seometa\Orm\OpengraphTable;
use Sotbit\Seometa\SeoMetaMorphy;
use Sotbit\Seometa\Orm\SeometaNotConfiguredPagesTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\TwitterCardTable;

$moduleId = "sotbit.seometa";

if (!Loader::includeModule($moduleId) || !Loader::includeModule('iblock')) {
    return false;
}

global $USER;
global $APPLICATION;
global $sotbitSeoMetaTitle; //Meta title
global $sotbitSeoMetaKeywords; //Meta keywords
global $sotbitSeoMetaDescription; //Meta description
global $sotbitFilterResult; //Filter result
global $sotbitSeoMetaH1; //for set h1
global $sotbitSeoMetaBottomDesc; //for set bottom description
global $sotbitSeoMetaTopDesc; //for set top description
global $sotbitSeoMetaAddDesc; //for set additional description
global $sotbitSeoMetaFile;
global $sotbitSeoMetaBreadcrumbLink;
global $sotbitSeoMetaBreadcrumbTitle;
global ${$arParams['FILTER_NAME']};
global $issetCondition;
global $SeoMetaWorkingConditions;

$SeoMetaWorkingConditions = [];

CSeometa::excludeFilterParams(${$arParams['FILTER_NAME']});

if (
    Option::get($moduleId, "NO_INDEX_" . SITE_ID, "N") != "N"
    && !empty(${$arParams['FILTER_NAME']})
) {
    $APPLICATION->SetPageProperty("robots", 'noindex, nofollow');
}

$paginationText = "";
if ($_REQUEST['PAGEN_1']) {
    $pagOption = Option::get($moduleId, "PAGINATION_TEXT_" . SITE_ID);
    if ($pagOption) {
        $paginationText = " " . str_replace('%N%', $_REQUEST['PAGEN_1'], $pagOption);
    }
}

$str = $APPLICATION->GetCurPage();
if ($arParams['KOMBOX_FILTER'] == 'Y' && CModule::IncludeModule('kombox.filter')) {
    $str = CKomboxFilter::GetCurPageParam();
    $str = explode("?", $str);
    $str = $str[0];
}

$str = CSeoMeta::encodeRealUrl($str);

$metaData = SeometaUrlTable::getByRealUrl($str, SITE_ID);
if(empty($metaData)) {
    $metaData = SeometaUrlTable::getByRealUrl(preg_replace('/index.php$/', '', $str), SITE_ID);
}

if(empty($metaData)) {
    $requestGet = Context::getCurrent()->getRequest()->getQueryList()->toArray();
    $pageParams = array_keys($requestGet);
    $str = $APPLICATION->GetCurPageParam(
        '',
        $pageParams
    );
    $str = CSeoMeta::encodeRealUrl($str);
    $metaData = SeometaUrlTable::getByRealUrl($str, SITE_ID);
}

if (!empty($metaData['NEW_URL'])) {
    $APPLICATION->SetCurPage($metaData['NEW_URL']);
}

CSeoMeta::SetFilterResult($sotbitFilterResult, $arParams['SECTION_ID']); //filter result for class
CSeoMeta::AddAdditionalFilterResults(${$arParams['FILTER_NAME']}, $arParams['KOMBOX_FILTER']);
CSeoMeta::FilterCheck();

if ($this->StartResultCache($arParams["CACHE_TIME"] ?: false,
    $arParams["CACHE_GROUPS"] ? $USER->GetGroups() : false)
) {
    $arResult = CSeoMeta::getRules($arParams); //list of conditions for current section
    $this->endResultCache();
}

$sku = new Section($arParams['SECTION_ID']);
$morphyObject = SeoMetaMorphy::morphyLibInit();
$curReq = Context::getCurrent()->getRequest();
$protocol = $curReq->isHttps() ? 'https://' : 'http://';
$port = $curReq->getServerPort() == '80' ? ':' . $curReq->getServerPort() : '';
$classEmojiExist = class_exists('\Bitrix\Main\Text\Emoji');

if(empty($arResult) && !empty($metaData)){

    $APPLICATION->SetPageProperty("robots", 'index, follow');

    if ($metaData['SEOMETA_DATA']['ELEMENT_TITLE_REPLACE'] == 'Y') {
        $result['TITLE'] = $metaData['SEOMETA_DATA']['ELEMENT_TITLE'];
    }

    if (!empty($result['TITLE'])) {
        if ($classEmojiExist) {
            $result['TITLE'] = Emoji::decode($result['TITLE']);
        }

        $sotbitSeoMetaTitle = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy($result['TITLE']));
        $sotbitSeoMetaTitle = SeoMetaMorphy::convertMorphy($sotbitSeoMetaTitle, $morphyObject);
        $sotbitSeoMetaTitle .= $paginationText ?: '';
        $APPLICATION->SetPageProperty("title", $sotbitSeoMetaTitle);
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_KEYWORDS_REPLACE'] == 'Y') {
        $result['KEYWORDS'] = $metaData['SEOMETA_DATA']['ELEMENT_KEYWORDS'];
    }

    if (!empty($result['KEYWORDS'])) {
        if ($classEmojiExist) {
            $result['KEYWORDS'] = Emoji::decode($result['KEYWORDS']);
        }

        $sotbitSeoMetaKeywords = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy($result['KEYWORDS']));
        $sotbitSeoMetaKeywords = SeoMetaMorphy::convertMorphy($sotbitSeoMetaKeywords, $morphyObject);
        $APPLICATION->SetPageProperty("keywords", $sotbitSeoMetaKeywords);
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_DESCRIPTION_REPLACE'] == 'Y') {
        $result['DESCRIPTION'] = $metaData['SEOMETA_DATA']['ELEMENT_DESCRIPTION'];
    }

    if (!empty($result['DESCRIPTION'])) {
        if ($classEmojiExist) {
            $result['DESCRIPTION'] = Emoji::decode($result['DESCRIPTION']);
        }

        $sotbitSeoMetaDescription = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy($result['DESCRIPTION']));
        $sotbitSeoMetaDescription = SeoMetaMorphy::convertMorphy($sotbitSeoMetaDescription, $morphyObject);
        $sotbitSeoMetaDescription .= $paginationText ?: '';
        $APPLICATION->SetPageProperty("description", $sotbitSeoMetaDescription);
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_PAGE_TITLE_REPLACE'] == 'Y') {
        $result['PAGE_TITLE'] = $metaData['SEOMETA_DATA']['ELEMENT_PAGE_TITLE'];
    }

    if (!empty($result['PAGE_TITLE'])) {
        if ($classEmojiExist) {
            $result['PAGE_TITLE'] = Emoji::decode($result['PAGE_TITLE']);
        }

        $sotbitSeoMetaH1 = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy($result['PAGE_TITLE']));
        $arResult['ELEMENT_H1'] = $sotbitSeoMetaH1 .= $paginationText ?: '';
        $sotbitSeoMetaH1 = SeoMetaMorphy::convertMorphy($sotbitSeoMetaH1, $morphyObject);
        $APPLICATION->SetTitle($sotbitSeoMetaH1);
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_BREADCRUMB_TITLE_REPLACE'] == 'Y') {
        $result['BREADCRUMB_TITLE'] = $metaData['SEOMETA_DATA']['ELEMENT_BREADCRUMB_TITLE'];
    }

    if (!empty($result['BREADCRUMB_TITLE'])) {
        if ($classEmojiExist) {
            $result['BREADCRUMB_TITLE'] = Emoji::decode($result['BREADCRUMB_TITLE']);
        }

        $url = $protocol . $curReq->getServer()->getServerName() . $port . $curReq->getRequestUri();
        $sotbitSeoMetaBreadcrumbLink = $url;
        $sotbitSeoMetaBreadcrumbTitle = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy($result['BREADCRUMB_TITLE']));
        $sotbitSeoMetaBreadcrumbTitle = SeoMetaMorphy::convertMorphy($sotbitSeoMetaBreadcrumbTitle, $morphyObject);
        if (!empty($sotbitSeoMetaBreadcrumbLink)) {
            $arResult['BREADCRUMB_TITLE'] = $sotbitSeoMetaBreadcrumbTitle;
            $arResult['BREADCRUMB_LINK'] = $url;
        }
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_TOP_DESC_REPLACE'] == 'Y') {
        $result['ELEMENT_TOP_DESC'] = $metaData['SEOMETA_DATA']['ELEMENT_TOP_DESC'];
    }

    if (!empty($result['ELEMENT_TOP_DESC'])) {
        if ($classEmojiExist) {
            $result['ELEMENT_TOP_DESC'] = Emoji::decode($result['ELEMENT_TOP_DESC']);
        }

        $sotbitSeoMetaTopDesc = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['ELEMENT_TOP_DESC'])));
        $sotbitSeoMetaTopDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaTopDesc, $morphyObject);
        if (!empty($sotbitSeoMetaTopDesc)) {
            if ($result['ELEMENT_TOP_DESC_TYPE'] == 'text') {
                $sotbitSeoMetaTopDesc = htmlspecialchars($sotbitSeoMetaTopDesc);
            }
            $arResult['ELEMENT_TOP_DESC'] = $sotbitSeoMetaTopDesc;
        }
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_BOTTOM_DESC_REPLACE'] == 'Y') {
        $result['ELEMENT_BOTTOM_DESC'] = $metaData['SEOMETA_DATA']['ELEMENT_BOTTOM_DESC'];
    }
    if (!empty($result['ELEMENT_BOTTOM_DESC'])) {
        if ($classEmojiExist) {
            $result['ELEMENT_BOTTOM_DESC'] = Emoji::decode($result['ELEMENT_BOTTOM_DESC']);
        }

        $sotbitSeoMetaBottomDesc = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['ELEMENT_BOTTOM_DESC'])));
        $sotbitSeoMetaBottomDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaBottomDesc, $morphyObject);
        if (!empty($sotbitSeoMetaBottomDesc)) {
            if ($result['ELEMENT_BOTTOM_DESC_TYPE'] == 'text') {
                $sotbitSeoMetaBottomDesc = htmlspecialchars($sotbitSeoMetaBottomDesc);
            }

            $arResult['ELEMENT_BOTTOM_DESC'] = $sotbitSeoMetaBottomDesc;
        }
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_ADD_DESC_REPLACE'] == 'Y') {
        $result['ELEMENT_ADD_DESC'] = $metaData['SEOMETA_DATA']['ELEMENT_ADD_DESC'];
    }
    if (!empty($result['ELEMENT_ADD_DESC'])) {
        if ($classEmojiExist) {
            $result['ELEMENT_ADD_DESC'] = Emoji::decode($result['ELEMENT_ADD_DESC']);
        }

        $sotbitSeoMetaAddDesc = Engine::process($sku,
            SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['ELEMENT_ADD_DESC'])));
        $sotbitSeoMetaAddDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaAddDesc, $morphyObject);
        if (!empty($sotbitSeoMetaAddDesc)) {
            if ($result['ELEMENT_ADD_DESC_TYPE'] == 'text') {
                $sotbitSeoMetaAddDesc = htmlspecialchars($sotbitSeoMetaAddDesc);
            }
            $arResult['ELEMENT_ADD_DESC'] = $sotbitSeoMetaAddDesc;
        }
        $issetCondition = true;
    }

    if ($metaData['SEOMETA_DATA']['ELEMENT_FILE_REPLACE'] == 'Y') {
        $result['ELEMENT_FILE'] = $metaData['SEOMETA_DATA']['ELEMENT_FILE'];
    }
    if (intval($result['ELEMENT_FILE']) > 0) {
        $fileArray = CFile::GetFileArray($result['ELEMENT_FILE']);
        $arResult['ELEMENT_FILE']['SRC'] = $fileArray['SRC'];
        $arResult['ELEMENT_FILE']['DESCRIPTION'] = $fileArray['DESCRIPTION'];
        $sotbitSeoMetaFile = '<img src="' . $arResult['ELEMENT_FILE']['SRC'] . '" alt="' . $arResult['ELEMENT_FILE']['DESCRIPTION'] . '">';
        $issetCondition = true;
    }
}else{
    $COND = [];
    foreach ($arResult as $key => $condition) {
        //get conditions and metatags
        $COND[$key]['RULES'] = unserialize($condition['RULE']);
        $COND[$key]['META'] = unserialize($condition['META']);
        $COND[$key]['ID'] = $condition['ID'];
        $COND[$key]['NO_INDEX'] = $condition['NO_INDEX'];
        $COND[$key]['STRONG'] = $condition['STRONG'];
    }

    $issetCondition = false;
    $results = [];
    foreach ($COND as $rule) //get metatags if condition true
    {
        if ($res = CSeoMeta::SetMetaCondition($rule, $arParams['SECTION_ID'], $condition['INFOBLOCK'])) {
            $results[] = $res;
        }
    }

    $sectionParams = CIBlockSection::GetList(
        [],
        ['ID' => $arParams['SECTION_ID']],
        false,
        ['SECTION_PAGE_URL']
    )->GetNext();

    if ($results && is_array($results)) {
        foreach ($results as $result) //set metatags
        {
            //INDEX
            if ($result['NO_INDEX'] == 'Y') {
                $APPLICATION->SetPageProperty("robots", 'noindex, nofollow');
            } elseif ($result['NO_INDEX'] == 'N') {
                $APPLICATION->SetPageProperty("robots", 'index, follow');
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_TITLE_REPLACE'] == 'Y') {
                $result['TITLE'] = $metaData['SEOMETA_DATA']['ELEMENT_TITLE'];
            }

            if (!empty($result['TITLE'])) {
                if ($classEmojiExist) {
                    $result['TITLE'] = Emoji::decode($result['TITLE']);
                }

                $sotbitSeoMetaTitle = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($result['TITLE']));
                $sotbitSeoMetaTitle = SeoMetaMorphy::convertMorphy($sotbitSeoMetaTitle, $morphyObject);
                $sotbitSeoMetaTitle .= $paginationText ?: '';
                $APPLICATION->SetPageProperty("title", $sotbitSeoMetaTitle);
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_KEYWORDS_REPLACE'] == 'Y') {
                $result['KEYWORDS'] = $metaData['SEOMETA_DATA']['ELEMENT_KEYWORDS'];
            }

            if (!empty($result['KEYWORDS'])) {
                if ($classEmojiExist) {
                    $result['KEYWORDS'] = Emoji::decode($result['KEYWORDS']);
                }

                $sotbitSeoMetaKeywords = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($result['KEYWORDS']));
                $sotbitSeoMetaKeywords = html_entity_decode(SeoMetaMorphy::convertMorphy($sotbitSeoMetaKeywords, $morphyObject));
                $APPLICATION->SetPageProperty("keywords", $sotbitSeoMetaKeywords);
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_DESCRIPTION_REPLACE'] == 'Y') {
                $result['DESCRIPTION'] = $metaData['SEOMETA_DATA']['ELEMENT_DESCRIPTION'];
            }

            if (!empty($result['DESCRIPTION'])) {
                if ($classEmojiExist) {
                    $result['DESCRIPTION'] = Emoji::decode($result['DESCRIPTION']);
                }

                $sotbitSeoMetaDescription = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['DESCRIPTION'])));
                $sotbitSeoMetaDescription = html_entity_decode(SeoMetaMorphy::convertMorphy($sotbitSeoMetaDescription, $morphyObject));
                $sotbitSeoMetaDescription .= $paginationText ?: '';
                $APPLICATION->SetPageProperty("description", $sotbitSeoMetaDescription);
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_PAGE_TITLE_REPLACE'] == 'Y') {
                $result['PAGE_TITLE'] = $metaData['SEOMETA_DATA']['ELEMENT_PAGE_TITLE'];
            }

            if (!empty($result['PAGE_TITLE'])) {
                if ($classEmojiExist) {
                    $result['PAGE_TITLE'] = Emoji::decode($result['PAGE_TITLE']);
                }

                $sotbitSeoMetaH1 = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($result['PAGE_TITLE']));
                $arResult['ELEMENT_H1'] = $sotbitSeoMetaH1 .= $paginationText ?: '';
                $sotbitSeoMetaH1 = SeoMetaMorphy::convertMorphy($sotbitSeoMetaH1, $morphyObject);
                $APPLICATION->SetTitle($sotbitSeoMetaH1);
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_BREADCRUMB_TITLE_REPLACE'] == 'Y') {
                $result['BREADCRUMB_TITLE'] = $metaData['SEOMETA_DATA']['ELEMENT_BREADCRUMB_TITLE'];
            }

            if (!empty($result['BREADCRUMB_TITLE'])) {
                if ($classEmojiExist) {
                    $result['BREADCRUMB_TITLE'] = Emoji::decode($result['BREADCRUMB_TITLE']);
                }

                $url = $protocol . $curReq->getServer()->getServerName() . $port . $curReq->getRequestUri();
                $sotbitSeoMetaBreadcrumbLink = $url;
                $sotbitSeoMetaBreadcrumbTitle = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($result['BREADCRUMB_TITLE']));
                $sotbitSeoMetaBreadcrumbTitle = SeoMetaMorphy::convertMorphy($sotbitSeoMetaBreadcrumbTitle, $morphyObject);
                if (!empty($sotbitSeoMetaBreadcrumbLink)) {
                    $arResult['BREADCRUMB_TITLE'] = $sotbitSeoMetaBreadcrumbTitle;
                    $arResult['BREADCRUMB_LINK'] = $url;
                }
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_TOP_DESC_REPLACE'] == 'Y') {
                $result['ELEMENT_TOP_DESC'] = $metaData['SEOMETA_DATA']['ELEMENT_TOP_DESC'];
            }

            if (!empty($result['ELEMENT_TOP_DESC'])) {
                if ($classEmojiExist) {
                    $result['ELEMENT_TOP_DESC'] = Emoji::decode($result['ELEMENT_TOP_DESC']);
                }

                $sotbitSeoMetaTopDesc = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['ELEMENT_TOP_DESC'])));
                $sotbitSeoMetaTopDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaTopDesc, $morphyObject);
                if (!empty($sotbitSeoMetaTopDesc)) {
                    if ($result['ELEMENT_TOP_DESC_TYPE'] == 'text') {
                        $sotbitSeoMetaTopDesc = htmlspecialchars($sotbitSeoMetaTopDesc);
                    }
                    $arResult['ELEMENT_TOP_DESC'] = $sotbitSeoMetaTopDesc;
                }
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_BOTTOM_DESC_REPLACE'] == 'Y') {
                $result['ELEMENT_BOTTOM_DESC'] = $metaData['SEOMETA_DATA']['ELEMENT_BOTTOM_DESC'];
            }
            if (!empty($result['ELEMENT_BOTTOM_DESC'])) {
                if ($classEmojiExist) {
                    $result['ELEMENT_BOTTOM_DESC'] = Emoji::decode($result['ELEMENT_BOTTOM_DESC']);
                }

                $sotbitSeoMetaBottomDesc = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['ELEMENT_BOTTOM_DESC'])));
                $sotbitSeoMetaBottomDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaBottomDesc, $morphyObject);
                if (!empty($sotbitSeoMetaBottomDesc)) {
                    if ($result['ELEMENT_BOTTOM_DESC_TYPE'] == 'text') {
                        $sotbitSeoMetaBottomDesc = htmlspecialchars($sotbitSeoMetaBottomDesc);
                    }

                    $arResult['ELEMENT_BOTTOM_DESC'] = $sotbitSeoMetaBottomDesc;
                }
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_ADD_DESC_REPLACE'] == 'Y') {
                $result['ELEMENT_ADD_DESC'] = $metaData['SEOMETA_DATA']['ELEMENT_ADD_DESC'];
            }
            if (!empty($result['ELEMENT_ADD_DESC'])) {
                if ($classEmojiExist) {
                    $result['ELEMENT_ADD_DESC'] = Emoji::decode($result['ELEMENT_ADD_DESC']);
                }

                $sotbitSeoMetaAddDesc = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($result['ELEMENT_ADD_DESC'])));
                $sotbitSeoMetaAddDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaAddDesc, $morphyObject);
                if (!empty($sotbitSeoMetaAddDesc)) {
                    if ($result['ELEMENT_ADD_DESC_TYPE'] == 'text') {
                        $sotbitSeoMetaAddDesc = htmlspecialchars($sotbitSeoMetaAddDesc);
                    }
                    $arResult['ELEMENT_ADD_DESC'] = $sotbitSeoMetaAddDesc;
                }
                $issetCondition = true;
            }

            if ($metaData['SEOMETA_DATA']['ELEMENT_FILE_REPLACE'] == 'Y') {
                $result['ELEMENT_FILE'] = $metaData['SEOMETA_DATA']['ELEMENT_FILE'];
            }
            if (intval($result['ELEMENT_FILE']) > 0) {
                $fileArray = CFile::GetFileArray($result['ELEMENT_FILE']);
                $arResult['ELEMENT_FILE']['SRC'] = $fileArray['SRC'];
                $arResult['ELEMENT_FILE']['DESCRIPTION'] = $fileArray['DESCRIPTION'];
                $sotbitSeoMetaFile = '<img src="' . $arResult['ELEMENT_FILE']['SRC'] . '" alt="' . $arResult['ELEMENT_FILE']['DESCRIPTION'] . '">';
                $issetCondition = true;
            }

            //CANONICAL

            if ($arParams['KOMBOX_FILTER'] == 'Y' && CModule::IncludeModule('kombox.filter')) {
                $str = CKomboxFilter::GetCurPageParam();
                $str = explode("?", $str);
                $CurPage_temp = SeometaUrlTable::getByRealUrl($str[0], SITE_ID);
                if (!empty($CurPage_temp['NEW_URL'])) {
                    $CurPage = $CurPage_temp['NEW_URL'];
                } else {
                    $CurPage = $str[0];
                }
            } else {
                $CurPage = $APPLICATION->GetCurPage(false);
            }

            if ($issetCondition && Option::get($moduleId, "USE_CANONICAL_" . SITE_ID, "Y") != "N") {
                if ($metaData['NEW_URL'] || $metaData['REAL_URL']) {
                    $APPLICATION->SetPageProperty("canonical",
                        $protocol . $curReq->getServer()->getServerName()
                        . $metaData['NEW_URL'] ?: $metaData['REAL_URL']);
                } elseif ($curReq->getServer()->get('REDIRECT_URL') || $curReq->getRequestUri()) {
                    $APPLICATION->SetPageProperty("canonical",
                        $protocol . $curReq->getServer()->getServerName()
                        . $curReq->getServer()->get('REDIRECT_URL') ?: $curReq->getRequestUri());
                } else {
                    $APPLICATION->SetPageProperty("canonical", $protocol . $curReq->getServer()->getServerName() . $CurPage);
                }
            }

            //OpenGraph and TwitterCard
            $metaDataOgTw = new OGraphTWCard();
            $arOGParams = OpengraphTable::getByConditionID($result['ID']);
            if(is_array($arOGParams) && $arOGParams['OG_FIELD_ACTIVE'] == 'Y') {
                unset($arOGParams['OG_FIELD_ACTIVE']);
                unset($arOGParams['ID']);
                unset($arOGParams['CONDITION_ID']);
                if ($metaData['NEW_URL'] || $metaData['REAL_URL']) {
                    $arOGParams['OG_FIELD_URL'] = $protocol . $curReq->getServer()->getServerName() . $metaData['NEW_URL'] ?: $metaData['REAL_URL'];
                } else {
                    $arOGParams['OG_FIELD_URL'] = $metaDataOgTw->getHttpSchema() . '://' . $curReq->getServer()->getServerName() . $CurPage;
                }
                foreach ($arOGParams as $name => $value) {
                    if($name == 'OG_FIELD_TITLE' || $name == 'OG_FIELD_DESCRIPTION') {
                        $afterProcess = Engine::process($sku,
                            SeoMetaMorphy::prepareForMorphy($value));
                        $afterProcess = SeoMetaMorphy::convertMorphy($afterProcess,
                            $morphyObject);

                        if($afterProcess) {
                            $value = $afterProcess;
                        }
                    }

                    $metaDataOgTw->setData($name,
                        $value);
                }
            }

            $arTWParams = TwitterCardTable::getByConditionID($result['ID']);
            if(is_array($arTWParams) && $arTWParams['TW_FIELD_ACTIVE'] == 'Y') {
                unset($arTWParams['TW_FIELD_ACTIVE']);
                unset($arTWParams['ID']);
                unset($arTWParams['CONDITION_ID']);
                unset($arTWParams['TW_FIELD_IMAGE_descr']);
                foreach ($arTWParams as $name => $value) {
                    if($name == 'TW_FIELD_TITLE' || $name == 'TW_FIELD_DESCRIPTION') {
                        $afterProcess = Engine::process($sku,
                            SeoMetaMorphy::prepareForMorphy($value));
                        $afterProcess = SeoMetaMorphy::convertMorphy($afterProcess,
                            $morphyObject);

                        if($afterProcess) {
                            $value = $afterProcess;
                        }
                    }

                    $metaDataOgTw->setData($name, $value);
                }
            }

            //tags
            if ($issetCondition && $result['ID'] > 0) {
                $SeoMetaWorkingConditions[] = $result['ID'];
            }
        }
    } else {
        $notConfiguredSeoData = SeometaNotConfiguredPagesTable::getBySiteID(SITE_ID);
        if($notConfiguredSeoData['ACTIVE'] == 'Y' && CSeoMeta::isFilterChecked()) {
            $mode = $_REQUEST['PAGEN_1'] ? $notConfiguredSeoData['BEHAVIOR_PAGINATION_PAGES'] : $notConfiguredSeoData['BEHAVIOR_FILTERED_PAGES'];
            if ($mode == 'no_index') {
                $APPLICATION->SetPageProperty("robots", 'noindex, nofollow');
            } elseif ($mode == 'canonical') {
                if (Option::get($moduleId, "NO_INDEX_" . SITE_ID, "N") === 'Y') {
                    $APPLICATION->SetPageProperty("robots", 'noindex, nofollow');
                } else {
                    $APPLICATION->SetPageProperty("robots", 'index, follow');
                }
            }

            if ($notConfiguredSeoData['META_ELEMENT_TITLE']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_TITLE'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_TITLE']);
                }

                $sotbitSeoMetaTitle = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($notConfiguredSeoData['META_ELEMENT_TITLE']));
                $sotbitSeoMetaTitle = SeoMetaMorphy::convertMorphy($sotbitSeoMetaTitle, $morphyObject);
                $sotbitSeoMetaTitle .= $paginationText ?: '';
                $sotbitSeoMetaTitle = \CSeoMeta::UserFields($sotbitSeoMetaTitle, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                $APPLICATION->SetPageProperty("title", $sotbitSeoMetaTitle);
            }

            if ($notConfiguredSeoData['META_ELEMENT_KEYWORDS']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_KEYWORDS'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_KEYWORDS']);
                }

                $sotbitSeoMetaKeywords = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($notConfiguredSeoData['META_ELEMENT_KEYWORDS']));
                $sotbitSeoMetaKeywords = SeoMetaMorphy::convertMorphy($sotbitSeoMetaKeywords, $morphyObject);
                $sotbitSeoMetaKeywords = \CSeoMeta::UserFields($sotbitSeoMetaKeywords, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                $APPLICATION->SetPageProperty("keywords", $sotbitSeoMetaKeywords);
            }

            if ($notConfiguredSeoData['META_ELEMENT_DESCRIPTION']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_DESCRIPTION'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_DESCRIPTION']);
                }

                $sotbitSeoMetaDescription = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($notConfiguredSeoData['META_ELEMENT_DESCRIPTION']));
                $sotbitSeoMetaDescription = SeoMetaMorphy::convertMorphy($sotbitSeoMetaDescription, $morphyObject);
                $sotbitSeoMetaDescription .= $paginationText ?: '';
                $sotbitSeoMetaDescription = \CSeoMeta::UserFields($sotbitSeoMetaDescription, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                $APPLICATION->SetPageProperty("description", $sotbitSeoMetaDescription);
            }

            if ($notConfiguredSeoData['META_ELEMENT_PAGE_TITLE']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_PAGE_TITLE'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_PAGE_TITLE']);
                }

                $sotbitSeoMetaH1 = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($notConfiguredSeoData['META_ELEMENT_PAGE_TITLE']));

                if ($sotbitSeoMetaH1) {
                    $sotbitSeoMetaH1 .= $paginationText ?: '';
                    $sotbitSeoMetaH1 = SeoMetaMorphy::convertMorphy($sotbitSeoMetaH1, $morphyObject);
                    $sotbitSeoMetaH1 = \CSeoMeta::UserFields($sotbitSeoMetaH1, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                    $arResult['ELEMENT_H1'] = $sotbitSeoMetaH1;
                    $APPLICATION->SetTitle($sotbitSeoMetaH1);
                }
            }

            if ($notConfiguredSeoData['META_ELEMENT_BREADCRUMB_TITLE']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_BREADCRUMB_TITLE'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_BREADCRUMB_TITLE']);
                }

                $url = $protocol . $curReq->getServer()->getServerName() . $port . $curReq->getRequestUri();
                $sotbitSeoMetaBreadcrumbLink = $url;
                $sotbitSeoMetaBreadcrumbTitle = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy($notConfiguredSeoData['META_ELEMENT_BREADCRUMB_TITLE']));
                $sotbitSeoMetaBreadcrumbTitle = SeoMetaMorphy::convertMorphy($sotbitSeoMetaBreadcrumbTitle, $morphyObject);
                $sotbitSeoMetaBreadcrumbTitle = \CSeoMeta::UserFields($sotbitSeoMetaBreadcrumbTitle, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                if (isset($sotbitSeoMetaBreadcrumbLink) && !empty($sotbitSeoMetaBreadcrumbLink)) {
                    $arResult['BREADCRUMB_TITLE'] = $sotbitSeoMetaBreadcrumbTitle;
                    $arResult['BREADCRUMB_LINK'] = $url;
                }
            }

            if ($notConfiguredSeoData['META_ELEMENT_TOP_DESC']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_TOP_DESC'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_TOP_DESC']);
                }

                $sotbitSeoMetaTopDesc = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($notConfiguredSeoData['META_ELEMENT_TOP_DESC'])));
                $sotbitSeoMetaTopDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaTopDesc, $morphyObject);
                $sotbitSeoMetaTopDesc = \CSeoMeta::UserFields($sotbitSeoMetaTopDesc, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                $arResult['ELEMENT_TOP_DESC'] = $sotbitSeoMetaTopDesc ?: '';
            }

            if ($notConfiguredSeoData['META_ELEMENT_BOTTOM_DESC']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_BOTTOM_DESC'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_BOTTOM_DESC']);
                }

                $sotbitSeoMetaBottomDesc = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($notConfiguredSeoData['META_ELEMENT_BOTTOM_DESC'])));
                $sotbitSeoMetaBottomDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaBottomDesc, $morphyObject);
                $sotbitSeoMetaBottomDesc = \CSeoMeta::UserFields($sotbitSeoMetaBottomDesc, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                $arResult['ELEMENT_BOTTOM_DESC'] = $sotbitSeoMetaBottomDesc ?: '';
            }

            if ($notConfiguredSeoData['META_ELEMENT_ADD_DESC']) {
                if ($classEmojiExist) {
                    $notConfiguredSeoData['META_ELEMENT_ADD_DESC'] = Emoji::decode($notConfiguredSeoData['META_ELEMENT_ADD_DESC']);
                }

                $sotbitSeoMetaAddDesc = Engine::process($sku,
                    SeoMetaMorphy::prepareForMorphy(html_entity_decode($notConfiguredSeoData['META_ELEMENT_ADD_DESC'])));
                $sotbitSeoMetaAddDesc = SeoMetaMorphy::convertMorphy($sotbitSeoMetaAddDesc, $morphyObject);
                $sotbitSeoMetaAddDesc = \CSeoMeta::UserFields($sotbitSeoMetaAddDesc, $arParams['SECTION_ID'], $condition['INFOBLOCK']);
                $arResult['ELEMENT_ADD_DESC'] = $sotbitSeoMetaAddDesc ?: '';
            }

            //CANONICAL
            if ($mode == 'canonical') {
                $CurPage = $APPLICATION->GetCurPage(false);
                if ($arParams['KOMBOX_FILTER'] == 'Y' && CModule::IncludeModule('kombox.filter')) {
                    $str = CKomboxFilter::GetCurPageParam();
                    $str = explode("?", $str);
                    $CurPage_temp = SeometaUrlTable::getByRealUrl($str[0], SITE_ID);
                    $CurPage = $str[0];
                    if (!empty($CurPage_temp['NEW_URL'])) {
                        $CurPage = $CurPage_temp['NEW_URL'];
                    }
                }

                if ($sectionParams['SECTION_PAGE_URL']) {
                    $APPLICATION->SetPageProperty("canonical",
                        $protocol . $curReq->getServer()->getServerName() . $sectionParams['SECTION_PAGE_URL']);
                }
            }
        }
    }
}

$SeoMetaWorkingConditions = array_unique($SeoMetaWorkingConditions);

if(Option::get("sotbit.seometa",'INC_STATISTIC','N',SITE_ID) == 'Y') {
    $arParams['KOMBOX_FILTER'] = $arParams['KOMBOX_FILTER'] ?: 'N';
    Asset::getInstance()->addJs("/bitrix/components/sotbit/seo.meta/js/stat.js");

    $prop['ITEMS'] = $sotbitFilterResult['ITEMS'] ?: [];

    echo '<script> let stat = new Stat('.CUtil::PhpToJSObject(${$arParams['FILTER_NAME']}).','.$arParams['SECTION_ID'].',"'.$arParams['KOMBOX_FILTER'].'","'.SITE_ID.'","'.LANG_CHARSET.'",'.CUtil::PhpToJSObject($prop).') </script>';
}

$this->IncludeComponentTemplate();
?>