<?php

namespace Sotbit\Seometa\Controllers;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use \Bitrix\Main\Engine\Controller;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaStatisticsTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\SeoMetaMorphy;
use Bitrix\Iblock\Template\Entity\Section;
use Bitrix\Iblock\Template\Engine;
use Bitrix\Main\ErrorCollection;

class Statistics extends Controller
{
    public function configureActions()
    {
        return [
            'fillStat' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ]
        ];
    }

    public static function fillStatAction(string $to, string $siteID, string $metaInfo, string $arFilter, string $sotbitFilter)
    {
        $errorCollection = new ErrorCollection();

        try {
            $metaInfo = Encoding::convertEncoding($metaInfo, LANG_CHARSET, 'utf-8');
            $arFilter = Encoding::convertEncoding($arFilter, LANG_CHARSET, 'utf-8');
            $sotbitFilter = json_decode(Encoding::convertEncoding($sotbitFilter, LANG_CHARSET, 'utf-8'), true);
            $seoInfo = json_decode($metaInfo, true);
            if(LANG_CHARSET === 'windows-1251'){
                $sotbitFilter = Encoding::convertEncoding($sotbitFilter, 'utf-8', LANG_CHARSET);
                $seoInfo = Encoding::convertEncoding($seoInfo, 'utf-8', LANG_CHARSET);
            }
            $url = explode('?', $to)[0];
            self::writeInStat($url, $siteID, $seoInfo, $arFilter, $sotbitFilter);
        }catch (\Exception $e) {
            return null;
        }

    }

    protected static function writeInStat($url, $siteID, $seoInfo, $arFilter, $sotbitFilter)
    {
        $str = Option::get("sotbit.seometa",
            'PAGENAV_' . $siteID,
            '',
            $siteID
        );

        if($str){
            $preg = str_replace('/', '\/', $str);
            $preg = '/' . str_replace('%N%', '\d', $preg) . '/';
            $urlWithoutPagen = preg_replace($preg, '', $url);
        }else{
            $urlWithoutPagen = $url;
        }

        $stat = SeometaStatisticsTable::getList([
            'select' => ['ID', 'LAST_DATE_CHECK', 'PAGE_STATUS', 'META_DESCRIPTION', 'META_KEYWORDS', 'META_TITLE'],
            'filter' => ['URL' => $url],
            'order' => ['ID']
        ])->fetch();

        $currentDate = new \Bitrix\Main\Type\DateTime();

        if ($stat['LAST_DATE_CHECK']) {
            if (($stat['PAGE_STATUS'] >= 200 && $stat['PAGE_STATUS'] <= 299) && (!$stat['META_DESCRIPTION'] && !$stat['META_KEYWORDS'] && !$stat['META_TITLE'])) {
                $lastDateCheck = INF; //check for page which have 200 status and empty metainf, it means that this is the first check
            } else {
                $lastDateCheck = strtotime($currentDate->toString()) - strtotime($stat['LAST_DATE_CHECK']);
            }
        } else {
            $lastDateCheckNull = true; // plug for LAST_DATE_CHECK if in db value = NULL
        }
        $lastDateCheckSettings = Option::get("sotbit.seometa",'PERIOD_STATISTIC','86400',$siteID);
        if ($stat && ($lastDateCheckNull !== true && $lastDateCheck < $lastDateCheckSettings)) {
            return;
        }

        $domain = explode('//', $urlWithoutPagen);
        $domain = explode('/', $domain[1]);
        $domain = $domain[0];
        $urlCondition = explode($domain, $urlWithoutPagen)[1];


        $arChpu = SeometaUrlTable::getByRealUrl($urlCondition, $siteID);
        if (empty($arChpu)) {
            $arChpu = SeometaUrlTable::getByNewUrl($urlCondition, $siteID);
        }

        if ($arChpu && $arChpu['SEOMETA_DATA']) {
            $META_TITLE = ($arChpu['SEOMETA_DATA']['ELEMENT_TITLE_REPLACE'] === 'Y') ? $arChpu['SEOMETA_DATA']['ELEMENT_TITLE'] : null;
            $META_KEYWORDS = ($arChpu['SEOMETA_DATA']['ELEMENT_KEYWORDS_REPLACE'] === 'Y') ? $arChpu['SEOMETA_DATA']['ELEMENT_KEYWORDS'] : null;
            $META_DESCRIPTION = ($arChpu['SEOMETA_DATA']['ELEMENT_DESCRIPTION_REPLACE'] === 'Y') ? $arChpu['SEOMETA_DATA']['ELEMENT_DESCRIPTION'] : null;
            $section = $arChpu['section_id'];
        }

        $section = $section ?: $seoInfo['section'];

        if(LANG_CHARSET === 'windows-1251'){
            $arFilter = Encoding::convertEncoding(json_decode($arFilter, true),'utf-8',LANG_CHARSET);
        }else{
            $arFilter = json_decode($arFilter, true);
        }

        \CSeoMeta::SetFilterResult($sotbitFilter, $section);
        \CSeoMeta::AddAdditionalFilterResults($arFilter, $seoInfo['komboxFilter']);

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
                    'NO_INDEX' => $NO_INDEX,
                ];
                SeometaStatisticsTable::update($stat['ID'], $arFields);
            } else {
                $arFields = [
                    'DATE_CREATE' => $currentDate,
                    'CONDITION_ID' => $arCondition['ID'],
                    'URL' => $url,
                    'NO_INDEX' => $NO_INDEX,
                    'ROBOTS_INFO' => 'N',
                    'META_TITLE' => serialize($seoResult['META_TITLE']),
                    'META_KEYWORDS' => serialize($seoResult['META_KEYWORDS']),
                    'META_DESCRIPTION' => serialize($seoResult['META_DESCRIPTION']),
                    'LAST_DATE_CHECK' => $currentDate,
                    'SITE_ID' => $arChpu['SITE_ID'],
                ];
                SeometaStatisticsTable::add($arFields);
            }
        }
    }
}