<?

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Helper\BackupMethods;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SitemapTable;

global $USER, $APPLICATION;

Loc::loadMessages(__FILE__);
$moduleId = "sotbit.seometa";
if (!Loader::includeModule($moduleId) || $APPLICATION->GetGroupRight($moduleId) == 'D') {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$seometaSitemap = new CSeoMetaSitemapLight();
$seometaSitemap->initRequestData();
$arSitemap = null;
if (!empty($seometaSitemap->getRequestData('ID'))) {
    $arSitemap = SitemapTable::getById($seometaSitemap->getRequestData('ID'))->fetch();
    if ($arSitemap['SITE_ID']) {
        $countLinksForOperation = Option::get($moduleId,
            'SEOMETA_SITEMAP_COUNT_LINKS_FOR_OPERATION',
            '10000',
            $arSitemap['SITE_ID']
        );
        $countLinks = Option::get($moduleId,
            'SEOMETA_SITEMAP_COUNT_LINKS',
            '50000',
            $arSitemap['SITE_ID']
        );
        $_REQUEST['limit'] = $countLinksForOperation < $countLinks ? $countLinksForOperation : $countLinks;
        $seometaSitemap->setRequestData('limit', $_REQUEST['limit']);
    }

    $sitePaths = $seometaSitemap->pathMainSitemap($seometaSitemap->getRequestData('ID'));
    $_REQUEST['SITE_ID'] = $sitePaths['site_id'];
    $seometaSitemap->setRequestData('SITE_ID', $_REQUEST['SITE_ID']);
}

if (!is_array($arSitemap) || $sitePaths['TYPE'] == 'ERROR') {
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"));
    die();
} else {
    $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
}

$SiteUrl = '';
if ($sitePaths['domain_dir']) {
    $SiteUrl = $sitePaths['domain_dir'];
} else {
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"));
    die();
}

$mainSitemapName = '';
if (!empty($arSitemap['SETTINGS']['FILENAME_INDEX'])) {
    $mainSitemapName = $arSitemap['SETTINGS']['FILENAME_INDEX'];
} else {
    ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"));
    die();
}

if (!empty($arSitemap['SETTINGS']['FILTER_TYPE'])) {
    $FilterTypeKey = key($arSitemap['SETTINGS']['FILTER_TYPE']);
    $FilterCHPU = $arSitemap['SETTINGS']['FILTER_TYPE'][$FilterTypeKey];
    $FilterType = mb_strtolower($FilterTypeKey . ((!$FilterCHPU) ? '_not' : '') . '_chpu');
} else {
    ShowError(Loc::getMessage("SEO_META_ERROR_SITEMAP_FILTER_TYPE_NOT_FOUND"));
    die();
}

$mainSitemap = $sitePaths['abs_path'] . $mainSitemapName;
if (file_exists($mainSitemap)) {
    if (
        $_REQUEST['action'] == 'write_sitemap'
        && (new BackupMethods)->makeBackup($sitePaths['abs_path']) == ''
    ) {
        $seometaSitemap->deleteOldSeometaSitemaps($sitePaths['abs_path']);
    }

    $arrConditionsParams = ConditionTable::getConditionBySiteId($sitePaths['site_id']);
    $filter = ['ACTIVE' => 'Y', 'SITE_ID' => $sitePaths['site_id']];
    if ($arSitemap['SETTINGS']['EXCLUDE_NOT_SEF'] == 'Y' && is_array($filter['CONDITION_ID'])) {
        foreach ($arrConditionsParams as $conditionParam) {
            $filter['CONDITION_ID'] = array_merge($filter['CONDITION_ID'],
                [$conditionParam['ID']]);
        }
    }

    $seometaSitemap->markUrlsExcludeSitemap($sitePaths['site_id']);
    $limit = $seometaSitemap->getRequestData('limit');
    $offset = $seometaSitemap->getRequestData('offset');
    $arrUrls = SeometaUrlTable::getList([
        'select' => [
            'ID',
            'NEW_URL',
            'REAL_URL',
            'DATE_CHANGE',
            'CONDITION_ID'
        ],
        'filter' => $filter,
        'order' => ['ID'],
        'limit' => $limit,
        'offset' =>  $offset !== 0 ? $limit * $offset : 0
    ])->fetchAll();
    if(!$data){
        if(empty($arrUrls)){
            ShowError(Loc::getMessage("SEO_META_ERROR_CHPU_NOT_FOUND"));
            die();
        }
    }
    if (count($arrUrls) > 0) {
        echo $seometaSitemap->generateSitemap($arrUrls, $SiteUrl);
    } else {
        echo $seometaSitemap->generateSitemapFinish(
            $seometaSitemap->getRequestData('ID'),
            $seometaSitemap->getRequestData('sitemap_index')
        );
    }

    die();
}
?>
