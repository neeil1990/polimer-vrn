<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\SitemapTable;
use Sotbit\Seometa\ConditionTable;
use Sotbit\Seometa\SeometaUrlTable;
use Bitrix\Main\Loader;
use Bitrix\Seo\SitemapRuntime;
set_time_limit(10800);
Loc::loadMessages(__FILE__);

if(!$USER->CanDoOperation('sotbit.seometa'))
{
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::includeModule('sotbit.seometa');
$ID = intval($_REQUEST['ID']);
$arSitemap = null;

if($ID > 0)
{
    $dbSitemap = SitemapTable::getById($ID);
    $arSitemap = $dbSitemap->fetch();
}

if(!is_array($arSitemap))
{
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    ShowError(Loc::getMessage("SEO_META_ERROR_SITEMAP_NOT_FOUND"));
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}
else
{
    $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
}

$arSites = array();
$rsSites = CSite::GetById($arSitemap['SITE_ID']);

$arSite = $rsSites->Fetch();
$SiteUrl = "";

if(isset($arSitemap['SETTINGS']['PROTO']) && $arSitemap['SETTINGS']['PROTO'] == 1)
{
    $SiteUrl .= 'https://';
}
elseif(isset($arSitemap['SETTINGS']['PROTO']) && $arSitemap['SETTINGS']['PROTO'] == 0)
{
    $SiteUrl .= 'http://';
}
else
{
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"));
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}

if(isset($arSitemap['SETTINGS']['DOMAIN']) && !empty($arSitemap['SETTINGS']['DOMAIN']))
    $SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'] . substr($arSite['DIR'], 0, -1);
else
{
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"));
}

if(isset($arSitemap['SETTINGS']['FILENAME_INDEX']) && !empty($arSitemap['SETTINGS']['FILENAME_INDEX']))
    $mainSitemapName = $arSitemap['SETTINGS']['FILENAME_INDEX'];
else
{
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"));
}

if(isset($arSitemap['SETTINGS']['FILTER_TYPE']) && !is_null($arSitemap['SETTINGS']['FILTER_TYPE']))
{
    $FilterTypeKey = key($arSitemap['SETTINGS']['FILTER_TYPE']);
    $FilterCHPU = $arSitemap['SETTINGS']['FILTER_TYPE'][$FilterTypeKey];

    $FilterType = strtolower($FilterTypeKey . ((!$FilterCHPU) ? '_not' : '') . '_chpu');
}
else
{
    ShowError(Loc::getMessage("SEO_META_ERROR_SITEMAP_FILTER_TYPE_NOT_FOUND"));
}

$mainSitemapUrl = $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . $mainSitemapName;

if(file_exists($mainSitemapUrl))
{
    if(isset($action) && ($action == 'get_section_list')){
        file_put_contents($_SERVER['DOCUMENT_ROOT']. '/seometa_link_count.txt', '0');
        $link = \Sotbit\Seometa\Helper\Link::getInstance();
        echo json_encode($link->getSectionList($ID));
        exit;
    }

    // START GENERATE XML ARRAY
    $rsCondition = ConditionTable::getList(array(
        'select' => array(
            'ID',
            'DATE_CHANGE',
            'INFOBLOCK',
            'STRONG',
            'NO_INDEX',
            'RULE',
            'SITES',
            'SECTIONS',
            'PRIORITY',
            'CHANGEFREQ',
        ),
        'filter' => array(
            'ACTIVE' => 'Y',
            '!=NO_INDEX' => 'Y',
        ),
        'order' => array(
            'ID' => 'asc'
        )
    ));

    $connection = Application::getConnection();
    $writer = \Sotbit\Seometa\Link\XmlWriter::getInstance($ID, $arSite['ABS_DOC_ROOT'] . $arSite['DIR'], $SiteUrl, $iteration);

    $i = 0;
    while($arCondition = $rsCondition->Fetch())
    {
        if(in_array($arSitemap['SITE_ID'], unserialize($arCondition['SITES'])))
        {
            $rule = unserialize($arCondition['RULE']);
            if(empty($rule['CHILDREN']))
                continue;

            $arrIds = SeometaUrlTable::getArrIdsByConditionId($arCondition['ID']);
            if($arrIds)
            {
                $sql = "UPDATE `b_sotbit_seometa_chpu` SET `IN_SITEMAP` = 'N' WHERE `b_sotbit_seometa_chpu`.`ID` IN (" . $arrIds . ")";
                $res = $connection->query($sql);
            }

            $link = \Sotbit\Seometa\Helper\Link::getInstance();
            if (isset($currentSection) && is_array($currentSection)) {
                foreach ($currentSection as $section) {
                    $link->Generate($arCondition['ID'], $writer, array($section));
                }
            } else {
                $link->Generate($arCondition['ID'], $writer);
            }
        }
    }

    $writer->WriteEnd();
    $writer->writeMainSiteMap();

    SitemapTable::update($ID, array('DATE_RUN' => new Bitrix\Main\Type\DateTime()));
}
else
{
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND") . ' ' . $mainSitemapUrl);
}
?>
