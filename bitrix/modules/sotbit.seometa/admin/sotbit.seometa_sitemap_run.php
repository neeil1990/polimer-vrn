<?

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Helper\BackupMethods;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Helper\XMLMethods;
use Sotbit\Seometa\Orm\SitemapTable;
use Sotbit\Seometa\Link\XmlWriter;

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;

$moduleId = "sotbit.seometa";

if (!Loader::includeModule($moduleId) || $APPLICATION->GetGroupRight($moduleId) == 'D') {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$ID = intval($_REQUEST['ID']);
$arSitemap = [];

if ($ID > 0) {
    $dbSitemap = SitemapTable::getById($ID);
    $arSitemap = $dbSitemap->fetch();
}

if (empty($arSitemap)) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    ShowError(Loc::getMessage("SEO_META_ERROR_SITEMAP_NOT_FOUND"));
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
} else {
    $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
}

$arSites = [];
$rsSites = CSite::GetById($arSitemap['SITE_ID']);
$arSite = $rsSites->Fetch();
$SiteUrl = "";
$error = [];
$SiteUrl = !empty($arSitemap['SETTINGS']['PROTO']) ? 'https://' : 'http://';

if (!empty($arSitemap['SETTINGS']['DOMAIN'])) {
    $SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'];
} else {
    $error = [
        'TYPE' => 'ERROR',
        'MSG' => ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"))
    ];
}

if (!empty($arSitemap['SETTINGS']['FILENAME_INDEX'])) {
    $mainSitemapName = $arSitemap['SETTINGS']['FILENAME_INDEX'];
} else {
    $error = [
        'TYPE' => 'ERROR',
        'MSG' => ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"))
    ];
}

if (!empty($arSitemap['SETTINGS']['FILTER_TYPE'])) {
    $FilterTypeKey = key($arSitemap['SETTINGS']['FILTER_TYPE']);
    $FilterCHPU = $arSitemap['SETTINGS']['FILTER_TYPE'][$FilterTypeKey];
    $FilterType = mb_strtolower($FilterTypeKey . ((!$FilterCHPU) ? '_not' : '') . '_chpu');
} else {
    $error = [
        'TYPE' => 'ERROR',
        'MSG' => ShowError(Loc::getMessage("SEO_META_ERROR_SITEMAP_FILTER_TYPE_NOT_FOUND"))
    ];
}

if ($error['TYPE'] == 'ERROR' && !empty($error['MSG'])) {
    echo json_encode($error);
    exit;
}

$mainSitemapUrl = $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . $mainSitemapName;

//initialize params
if ($_REQUEST['params']) {
    $conditions = $_REQUEST['params'];
}
//---/-------------

if (file_exists($mainSitemapUrl)) {
    $FoundSeoMetaSitemap = false;
    libxml_use_internal_errors(true);
    //$xml = simplexml_load_file($mainSitemapUrl);

    if (isset($action) && ($action == 'get_section_list')) {
        $seometaSitemap = new CSeoMetaSitemapLight();
        $seometaSitemap->initRequestData();
        if ((new BackupMethods)->makeBackup($arSite['ABS_DOC_ROOT'] . $arSite['DIR']) == '') {
            $seometaSitemap->deleteOldSeometaSitemaps($arSite['ABS_DOC_ROOT'] . $arSite['DIR']);
        }

        $link = Linker::getInstance();
        $_SESSION['SEOMETA_SITEMAP_XMLWRITER']['URL_COUNT'] = 0;
        $_SESSION['SEOMETA_SITEMAP_XMLWRITER']['ADD_ID'] = 1;

        // reset all 'IN_SITEMAP' statuses before new generation of sitemap
        $seometaSitemap->markUrlsExcludeSitemap($arSitemap['SITE_ID']);
        $result = $link->getConditionList($arSite['LID']);
        if(!$result){
            $error = [
                'TYPE' => 'ERROR',
                'MSG' => Loc::getMessage("SEO_META_SITEMAP_COND_ERROR", ['#SITE_ID#' => $arSite['LID']])
            ];
            echo Json::encode($error);
        }else{
            echo json_encode($result);
        }

        exit;
    }

    // START GENERATE XML ARRAY
    $condId = $conditions['conditions'][$currentCondition];
    $rsCondition = ConditionTable::getList([
        'select' => [
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
        ],
        'filter' => [
            'ACTIVE' => 'Y',
            '!=NO_INDEX' => 'Y',
            'ID' => $condId
        ],
        'order' => [
            'ID' => 'asc'
        ]
    ])->fetch();

    $writer = XmlWriter::getInstance(
        $ID,
        $arSite['ABS_DOC_ROOT'] . $arSite['DIR'],
        $SiteUrl,
        $arSitemap['SITE_ID'],
        $_REQUEST['action']
    );
    if (!empty($arSitemap['SETTINGS']['DOMAIN'])) {
        $SiteUrl .= mb_substr($arSite['DIR'], 0, -1);
        $SiteUrl[strlen($SiteUrl) - 1] !== '/' ? $SiteUrl .= '/' : '';
    } else {
        $error = [
            'TYPE' => 'ERROR',
            'MSG' => ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND"))
        ];
    }

    $conditionSites = unserialize($rsCondition['SITES']);

    // if condition belongs to the site for which sitemap is generated
    if (is_array($conditionSites) && in_array($arSitemap['SITE_ID'], $conditionSites)) {
        $rule = unserialize($rsCondition['RULE']);
        if (empty($rule['CHILDREN'])) {
           // exit;
        }

        $link = Linker::getInstance();
        if (isset($currentSection)) {
            //if script fall with 500 error (TIME OUT) you should try to uncomment this code section and
            //some code in sotbit.seometa_sitemap_list
           /* if (in_array($conditions['sections'][$condId][$currentSection], unserialize($rsCondition['SECTIONS']))) {
                $link->Generate($writer,
                    $rsCondition['ID'],
                    [$conditions['sections'][$condId][$currentSection]]
                );
            }*/

            //if script fall with 500 error (TIME OUT) you should try to comment this code section
            $conditionSections = unserialize($rsCondition['SECTIONS']);
            if($conditionSections){
                $link->Generate($writer,
                    $rsCondition['ID'],
                    $conditionSections
                );
            }

            //last iteration
            if (($iteration + 1) == $countIterations) {
                $writer->WriteEnd();
                $dateRun = new Bitrix\Main\Type\DateTime();
                SitemapTable::update($ID, ['DATE_RUN' => $dateRun]);

                //work with mainsitemap
                if ($writer->getAddID() > 0) {
                    $xml = file_get_contents($mainSitemapUrl);
                    if (empty($xml)) {
                        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex></sitemapindex>';
                    }
                    $data = (new XMLMethods)->xml2ary($xml);

                    if (is_array($data['sitemapindex']['_c']['sitemap'])) {
                        (new XMLMethods)->delSeometaFromMainSitemap($data['sitemapindex']['_c']['sitemap']);
                    }

                    for ($i = 0; $i < count((array)$xml->sitemap); $i++) {
                        if (
                            isset($xml->sitemap[$i]->loc)
                            && mb_strpos($xml->sitemap[$i]->loc, $SiteUrl . "sitemap_seometa_") !== false
                        ) {
                            $xml->sitemap[$i]->loc = '';
                        }
                    }

                    $item = (new XMLMethods)->seometaMainSitemapFiles(
                        $writer->getAddID(),
                        $ID,
                        $SiteUrl
                    );

                    if (is_array($item) && !empty($item)) {
                        $count = $data['sitemapindex']['_c']['sitemap'] ? count($data['sitemapindex']['_c']['sitemap']) : 0;
                        (new XMLMethods)->ins2ary(
                            $data['sitemapindex']['_c']['sitemap'],
                            $item,
                            $count
                        );
                        $xmlData = (new XMLMethods)->ary2xml($data);
                        $writeStatus = (new XMLMethods)->writeSiteMap($mainSitemapUrl, $xmlData);
                        $date = $dateRun->toString();
                        if (isset($writeStatus['TYPE'])) {
                            ShowError(Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND") . ' ' . $mainSitemapUrl);
                        }
                    }
                }
            }
            //-----
            if (!empty($date)) {
                echo json_encode(['DATE' => $date, 'ID' => $ID]);
            } else {
                echo SitemapRuntime::showProgress(Loc::getMessage('SEO_META_SITEMAP_RUN_INIT'),
                    Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE'),
                    ($iteration + 1) * 100 / $countIterations);
            }
            exit;
        } else {
            $link->Generate($rsCondition['ID'], $writer);
        }
    }
} else {
    $error = [
        'TYPE' => 'ERROR',
        'MSG' => Loc::getMessage("SEO_META_ERROR_SITE_SITEMAP_NOT_FOUND") . ' ' . $mainSitemapUrl
    ];

    echo Json::encode($error);
    exit;
}
?>
