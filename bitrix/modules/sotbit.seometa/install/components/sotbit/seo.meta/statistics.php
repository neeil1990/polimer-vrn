<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaStatisticsTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\SeoMetaMorphy;
use Bitrix\Iblock\Template\Entity\Section;
use Bitrix\Iblock\Template\Engine;
use Bitrix\Main\Text\Emoji;

$moduleId = "sotbit.seometa";

if(!defined("SITE_ID")){
    define("SITE_ID", $_REQUEST['siteID']);
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
if (!Loader::includeModule($moduleId) || !Loader::includeModule('iblock')) {
    return false;
}
Loc::LoadMessages(__FILE__);

$seoInfo = json_decode($_REQUEST['metaInfo'], true);
$seoInfo = array_map(fn($arrVal) => Encoding::convertEncodingToCurrent($arrVal), $seoInfo);
$url = explode('?', $_REQUEST['to'])[0];

$str = Option::get("sotbit.seometa",
    'PAGENAV_' . SITE_ID,
    '',
    SITE_ID
);

if($str){
    $preg = str_replace('/', '\/', $str);
    $preg = '/' . str_replace('%N%', '\d', $preg) . '/';
    $urlWithoutPagen = preg_replace($preg, '', $url);
}else{
    $urlWithoutPagen = $url;
}

$stat = SeometaStatisticsTable::getList([
    'select' => ['ID', 'LAST_DATE_CHECK', 'PAGE_STATUS'],
    'filter' => ['URL' => $url],
    'order' => ['ID']
])->fetch();

$currentDate = new \Bitrix\Main\Type\DateTime();


if ($stat['LAST_DATE_CHECK']) {
    if ($stat['PAGE_STATUS'] == '404') {
        $lastDateCheck = 0; //check for page which have 404 status
    } else {
        $lastDateCheck = ((strtotime(($currentDate->toString())) - strtotime(($stat['LAST_DATE_CHECK']))) / (86400));
    }
} else {
    $lastDateCheckNull = true; // plug for LAST_DATE_CHECK if in db value = NULL
}
$lastDateCheckSettings = Option::get("sotbit.seometa",'PERIOD_STATISTIC','1',SITE_ID);
if ($stat && ($stat['LAST_DATE_CHECK'] !== null && $lastDateCheck < $lastDateCheckSettings)) {
    return;
}

$domain = explode('//', $urlWithoutPagen);
$domain = explode('/', $domain[1]);
$domain = $domain[0];
$urlCondition = explode($domain, $urlWithoutPagen)[1];


$arChpu = SeometaUrlTable::getByRealUrl($urlCondition, SITE_ID);
if (empty($arChpu)) {
    $arChpu = SeometaUrlTable::getByNewUrl($urlCondition, SITE_ID);
}

if ($arChpu && $arChpu['SEOMETA_DATA']) {
    $META_TITLE = ($arChpu['SEOMETA_DATA']['ELEMENT_TITLE_REPLACE'] === 'Y') ? $arChpu['SEOMETA_DATA']['ELEMENT_TITLE'] : null;
    $META_KEYWORDS = ($arChpu['SEOMETA_DATA']['ELEMENT_KEYWORDS_REPLACE'] === 'Y') ? $arChpu['SEOMETA_DATA']['ELEMENT_KEYWORDS'] : null;
    $META_DESCRIPTION = ($arChpu['SEOMETA_DATA']['ELEMENT_DESCRIPTION_REPLACE'] === 'Y') ? $arChpu['SEOMETA_DATA']['ELEMENT_DESCRIPTION'] : null;
    $section = $arChpu['section_id'];
}

$section = $section ?: $seoInfo['section'];

$arFilter = array_map(
    function ($arrVal){
        if(is_array($arrVal)){
            return array_map(fn($arrValVal) => Encoding::convertEncodingToCurrent($arrValVal), $arrVal);
        }else{
            return  Encoding::convertEncodingToCurrent($arrVal);
        }
    },
    json_decode($_REQUEST['arFilter'], true)
);
CSeoMeta::AddAdditionalFilterResults($arFilter, $seoInfo['komboxFilter']);

$morphyObject = SeoMetaMorphy::morphyLibInit();
if ($arChpu) {
    $arCondition = ConditionTable::getById($arChpu['CONDITION_ID'])->fetch();

    if (!$META_TITLE || !$META_KEYWORDS || !$META_DESCRIPTION) {
        if ($arCondition && $arrSeometaData = unserialize($arCondition['META'])) {
            $resSeometaData['META_TITLE'] = ($META_TITLE === null) ? $arrSeometaData['ELEMENT_TITLE'] : '';
            $resSeometaData['META_KEYWORDS'] = ($META_KEYWORDS === null) ? $arrSeometaData['ELEMENT_KEYWORDS'] : '';
            $resSeometaData['META_DESCRIPTION'] = ($META_DESCRIPTION === null) ? $arrSeometaData['ELEMENT_DESCRIPTION'] : '';

            if ($section) {
                $sku = new Section($section);
                if ($resSeometaData['META_TITLE']) {
                    $META_TITLE = Engine::process($sku, SeoMetaMorphy::prepareForMorphy($resSeometaData['META_TITLE']));
                    $META_TITLE = Encoding::convertEncodingToCurrent($META_TITLE);
                    $META_TITLE = SeoMetaMorphy::convertMorphy($META_TITLE, $morphyObject);
                }
                if ($resSeometaData['META_KEYWORDS']) {
                    $META_KEYWORDS = Engine::process($sku, SeoMetaMorphy::prepareForMorphy($resSeometaData['META_KEYWORDS']));
                    $META_KEYWORDS = Encoding::convertEncodingToCurrent($META_KEYWORDS);
                    $META_KEYWORDS = SeoMetaMorphy::convertMorphy($META_KEYWORDS, $morphyObject);
                }
                if ($resSeometaData['META_DESCRIPTION']) {
                    $META_DESCRIPTION = Engine::process($sku, SeoMetaMorphy::prepareForMorphy($resSeometaData['META_DESCRIPTION']));
                    $META_DESCRIPTION = Encoding::convertEncodingToCurrent($META_DESCRIPTION);
                    $META_DESCRIPTION = SeoMetaMorphy::convertMorphy($META_DESCRIPTION, $morphyObject);
                }
            }
        }
    }
    $seoInfo['title'] = Emoji::encode($seoInfo['title']);
    $seoInfo['keywords'] = Emoji::encode($seoInfo['keywords']);
    $seoInfo['description'] = Emoji::encode($seoInfo['description']);
    $seoResult['META_TITLE']['COINCIDENCE'] = $seoInfo['title'] == $META_TITLE ? 'Y' : 'N';
    $seoResult['META_TITLE']['CONTENT'] = $seoInfo['title'];
    $seoResult['META_KEYWORDS']['COINCIDENCE'] = $seoInfo['keywords'] == $META_KEYWORDS ? 'Y' : 'N';
    $seoResult['META_KEYWORDS']['CONTENT'] = $seoInfo['keywords'];
    $seoResult['META_DESCRIPTION']['COINCIDENCE'] = $seoInfo['description'] == $META_DESCRIPTION ? 'Y' : 'N';
    $seoResult['META_DESCRIPTION']['CONTENT'] = $seoInfo['description'];
    $NO_INDEX = $seoInfo['index'] == 'index, follow' ? 'Y' : 'N';
    if ($stat && ($lastDateCheck >= $lastDateCheckSettings || $lastDateCheckNull)) {
        $arFields = [
            'META_TITLE' => serialize($seoResult['META_TITLE']),
            'META_KEYWORDS' => serialize($seoResult['META_KEYWORDS']),
            'META_DESCRIPTION' => serialize($seoResult['META_DESCRIPTION']),
            'LAST_DATE_CHECK' => $currentDate,
            'IN_SITEMAP' => $arChpu['IN_SITEMAP'],
            'NO_INDEX' => $NO_INDEX,
        ];
        if (!$stat['PAGE_STATUS'] || $stat['PAGE_STATUS'] == '404') {
            $arFields['PAGE_STATUS'] = '200';
        }
        SeometaStatisticsTable::update($stat['ID'], $arFields);
    } else {
        $arFields = [
            'DATE_CREATE' => $currentDate,
            'CONDITION_ID' => $arCondition['ID'],
            'URL' => $url,
            'IN_SITEMAP' => $arChpu['IN_SITEMAP'],
            'NO_INDEX' => $NO_INDEX,
            'ROBOTS_INFO' => 'N',
            'META_TITLE' => serialize($seoResult['META_TITLE']),
            'META_KEYWORDS' => serialize($seoResult['META_KEYWORDS']),
            'META_DESCRIPTION' => serialize($seoResult['META_DESCRIPTION']),
            'LAST_DATE_CHECK' => $currentDate,
            'PAGE_STATUS' => '200',
            'SITE_ID' => $arChpu['SITE_ID'],
        ];
        SeometaStatisticsTable::add($arFields);
    }
}