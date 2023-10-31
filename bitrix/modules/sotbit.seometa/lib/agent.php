<?

namespace Sotbit\Seometa;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Sotbit\Seometa\Condition\Rule;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Helper\XMLMethods;
use Sotbit\Seometa\Helper\BackupMethods;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaStatisticsTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SitemapTable;
use Sotbit\Seometa\Link\XmlWriter;
use Bitrix\Main\Loader;

class Agent
{

    private static string $moduleId = "sotbit.seometa";

    public static function xmlWriterAgentChpuWithRegenerate($ID)
    {
        Loader::includeModule(self::$moduleId);

        libxml_use_internal_errors(true);
        $dbSitemap = SitemapTable::getById($ID);
        $arSitemap = $dbSitemap->fetch();

        $nameAgentChpu = "\Sotbit\Seometa\Agent::xmlWriterAgentChpuWithRegenerate({$ID});";
        if (empty($arSitemap)) {
            return $nameAgentChpu;
        } else {
            $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
        }

        $rsSites = \CSite::GetById($arSitemap['SITE_ID']);
        $arSite = $rsSites->Fetch();
        $arSitemapProto = !empty($arSitemap['SETTINGS']['PROTO']) ? 'https://' : 'http://';
        $SiteUrl = $arSitemapProto;

        if (!empty($arSitemap['SETTINGS']['DOMAIN'])) {
            $SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'];
        } else {
            return $nameAgentChpu;
        }

        if (!empty($arSitemap['SETTINGS']['FILENAME_INDEX'])) {
            $mainSitemapName = $arSitemap['SETTINGS']['FILENAME_INDEX'];
        } else {
            return $nameAgentChpu;
        }

        $mainSitemapUrl = $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . $mainSitemapName;

        $seometaSitemap = new \CSeoMetaSitemapLight();
        if ((new BackupMethods)->makeBackup($arSite['ABS_DOC_ROOT'] . $arSite['DIR']) == '') {
            $seometaSitemap->deleteOldSeometaSitemaps($arSite['ABS_DOC_ROOT'] . $arSite['DIR']);
        }

        $link = Linker::getInstance();

        $seometaUrlCollection = SeometaUrlTable::getList(['select' => ['*'], 'filter' => ['SITE_ID' => $arSitemap['SITE_ID']]])->fetchCollection();
        $elements = $seometaUrlCollection->getAll();
        foreach ($elements as $element) {
            $element->set('IN_SITEMAP', false);
        }
        $seometaUrlCollection->save();

        $condAndSect = $link->getConditionList($arSite['LID']);
        if (!empty($condAndSect)) {
            $conditionIDs = $condAndSect['conditions'];

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
                    'ID' => $conditionIDs
                ],
                'order' => [
                    'ID' => 'asc'
                ]
            ])->fetchAll();

            $writer = XmlWriter::getInstance(
                $ID,
                $arSite['ABS_DOC_ROOT'] . $arSite['DIR'],
                $SiteUrl,
                $arSitemap['SITE_ID'],
                false,
                true
            );

            if (!empty($arSitemap['SETTINGS']['DOMAIN'])) {
                $SiteUrl .= mb_substr($arSite['DIR'], 0, -1);
                $SiteUrl[strlen($SiteUrl) - 1] !== '/' ? $SiteUrl .= '/' : '';
            } else {
                return $nameAgentChpu;
            }

            foreach ($rsCondition as $cond) {
                $conditionSites = unserialize($cond['SITES']);
                if (is_array($conditionSites) && in_array($arSitemap['SITE_ID'], $conditionSites)) {
                    $rule = unserialize($cond['RULE']);
                    if (empty($rule['CHILDREN'])) {
                        continue;
                    }
                    $conditionSections = unserialize($cond['SECTIONS']);
                    $link->generate(
                        $writer,
                        $cond['ID'],
                        $conditionSections
                    );
                    $link->setRule(new Rule());
                }
            }

            $writer->WriteEnd();
            SitemapTable::update($ID, ['DATE_RUN' => new DateTime()]);

            //work with mainsitemap
            if ($writer->getAddID() > 0) {
                $xml = file_get_contents($mainSitemapUrl);
                if (empty($xml)) {
                    $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex></sitemapindex>';
                }
                $xmlMethods = new XMLMethods();
                $data = $xmlMethods->xml2ary($xml);

                if (is_array($data['sitemapindex']['_c']['sitemap'])) {
                    $xmlMethods->delSeometaFromMainSitemap($data['sitemapindex']['_c']['sitemap']);
                }

                for ($i = 0; $i < count((array)$xml->sitemap); $i++) {
                    if (
                        isset($xml->sitemap[$i]->loc)
                        && mb_strpos($xml->sitemap[$i]->loc, $SiteUrl . "sitemap_seometa_") !== false
                    ) {
                        $xml->sitemap[$i]->loc = '';
                    }
                }

                $item = $xmlMethods->seometaMainSitemapFiles(
                    $writer->getAddID(),
                    $ID,
                    $SiteUrl
                );

                if (is_array($item) && !empty($item)) {
                    $count = $data['sitemapindex']['_c']['sitemap'] ? count($data['sitemapindex']['_c']['sitemap']) : 0;
                    $xmlMethods->ins2ary(
                        $data['sitemapindex']['_c']['sitemap'],
                        $item,
                        $count
                    );
                    $xmlData = $xmlMethods->ary2xml($data);
                    $xmlMethods->writeSiteMap($mainSitemapUrl, $xmlData);
                }
            }
        }
        return $nameAgentChpu;
    }

    public static function xmlWriterAgentChpuNotRegenerate($ID)
    {
        Loader::includeModule(self::$moduleId);

        $nameNonChpu = "\Sotbit\Seometa\Agent::xmlWriterAgentChpuNotRegenerate($ID);";
        $seometaSitemap = new \CSeoMetaSitemapLight();
        $seometaSitemap->setRequestData('ID', $ID);
        $arSitemap = SitemapTable::getById($seometaSitemap->getRequestData('ID'))->fetch();
        $arSitemapProto = !empty($arSitemap['SETTINGS']['PROTO']) ? 'https://' : 'http://';

        if ($arSitemap['SITE_ID']) {
            $countLinks = Option::get(self::$moduleId,
                'SEOMETA_SITEMAP_COUNT_LINKS',
                '50000',
                $arSitemap['SITE_ID']
            );
        }

        $sitePaths = $seometaSitemap->pathMainSitemap($ID);
        if (!is_array($arSitemap) || $sitePaths['TYPE'] == 'ERROR') {
            return $nameNonChpu;
        } else {
            $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
        }

        $SiteUrl = '';
        if ($sitePaths['domain_dir']) {
            $SiteUrl = $sitePaths['domain_dir'];
        } else {
            return $nameNonChpu;
        }

        $mainSitemapName = '';
        if (!empty($arSitemap['SETTINGS']['FILENAME_INDEX'])) {
            $mainSitemapName = $arSitemap['SETTINGS']['FILENAME_INDEX'];
        } else {
            return $nameNonChpu;
        }

        if (!empty($arSitemap['SETTINGS']['FILTER_TYPE'])) {
            $FilterTypeKey = key($arSitemap['SETTINGS']['FILTER_TYPE']);
            $FilterCHPU = $arSitemap['SETTINGS']['FILTER_TYPE'][$FilterTypeKey];
            $FilterType = mb_strtolower($FilterTypeKey . ((!$FilterCHPU) ? '_not' : '') . '_chpu');
        } else {
            return $nameNonChpu;
        }

        $mainSitemap = $sitePaths['abs_path'] . $mainSitemapName;
        if (file_exists($mainSitemap)) {
            if ((new BackupMethods)->makeBackup($sitePaths['abs_path']) == '') {
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
            ])->fetchAll();
            if (empty($arrUrls)) {
                return $nameNonChpu;
            } else {
                $countChpuLinks = count($arrUrls);
                $sitemapIndex = 1;
                $sitemapFileName = $sitePaths['abs_path'] . 'sitemap_seometa_' . $ID . '_';
                $countIter = 0;

                $xmlMethods = new XMLMethods();
                $countNumberSymbForChange = $countChpuLinks * 2; //count bytes numbers in tags
                $countUrlSymbForChange = ($countChpuLinks * 2) * 3; //count bytes which need for change numbers in tags to the 'url', 3 that count symb in 'url'
                $version = 38; //count bytes which place version tag
                $urlset = 69; //count bytes which place urlset tag
                foreach ($arrUrls as $keyLink => $link) {
                    $conditionParams = ConditionTable::getConditionById($link['CONDITION_ID']);
                    SeometaUrlTable::update($link['ID'],
                        ['IN_SITEMAP' => 'Y']
                    );

                    if (!isset($conditionParams['PRIORITY'])) {
                        $conditionParams['PRIORITY'] = '0.0';
                    } else {
                        $conditionParams['PRIORITY'] = number_format($conditionParams['PRIORITY'],
                            1);
                    }

                    $sitemapFiles[$sitemapFileName . $sitemapIndex . '.xml'][] = [
                        '_c' => [
                            'loc' => [
                                '_v' => $arSitemapProto . $SiteUrl . ($link['NEW_URL'] ?: $link['REAL_URL'])
                            ],
                            'lastmod' => [
                                '_v' => $link['DATE_CHANGE']->format("Y-m-d\TH:i:sP")
                            ],
                            'changefreq' => [
                                '_v' => $conditionParams['CHANGEFREQ'] ?: 'always'
                            ],
                            'priority' => [
                                '_v' => $conditionParams['PRIORITY']
                            ]
                        ]
                    ];

                    $countIter += 1;
                    $countItem = count($sitemapFiles[$sitemapFileName . $sitemapIndex . '.xml']);
                    $currentXMLSize = (strlen($xmlMethods->ary2xml($sitemapFiles[$sitemapFileName . $sitemapIndex . '.xml'])) - $countNumberSymbForChange + $countUrlSymbForChange + $version + $urlset) / 1000000;
                    if($countItem == Option::get('sotbit.seometa', 'SEOMETA_SITEMAP_COUNT_LINKS', '50000', $arSitemap['SITE_ID'])){
                        $sitemapIndex++;
                    }elseif ($currentXMLSize >= Option::get('sotbit.seometa', 'SEOMETA_SITEMAP_FILE_SIZE', '50', $arSitemap['SITE_ID'], 'Mb')){
                        $lastValue = array_pop($sitemapFiles[$sitemapFileName . $sitemapIndex . '.xml']);
                        $sitemapIndex++;
                        $sitemapFiles[$sitemapFileName . $sitemapIndex . '.xml'] = [$lastValue];
                    }
                }

                foreach ($sitemapFiles as $keySitemap => $sitemap) {
                    $data = $xmlMethods->createXml($keySitemap);
                    if (!empty($data['TYPE'])) {
                        $result = $data;
                    }

                    $xmlMethods->ins2ary($data['urlset']['_c']['url'],
                        $sitemap,
                        count($data['urlset']['_c']['url']));

                    $xmlData = $xmlMethods->ary2xml($data);
                    $xmlMethods->writeSiteMap($keySitemap, $xmlData);
                }

                if (!empty($sitePaths['abs_path'])) {
                    $xml = file_get_contents($sitePaths['abs_path'] . $sitePaths['file_name']);
                    if (empty($xml)) {
                        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex></sitemapindex>';
                    }
                    $data = $xmlMethods->xml2ary($xml);

                    if (is_array($data['sitemapindex']['_c']['sitemap'])) {
                        $xmlMethods->delSeometaFromMainSitemap($data['sitemapindex']['_c']['sitemap']);
                    }

                    $item = $xmlMethods->seometaMainSitemapFiles(count($sitemapFiles),
                        $ID,
                        $sitePaths['url']);

                    if (!empty($item) && is_array($item)) {
                        $count = $data['sitemapindex']['_c']['sitemap'] ? count($data['sitemapindex']['_c']['sitemap']) : 0;
                        $xmlMethods->ins2ary($data['sitemapindex']['_c']['sitemap'],
                            $item,
                            $count);

                        $xmlData = $xmlMethods->ary2xml($data);
                        $writeStatus = $xmlMethods->writeSiteMap($sitePaths['abs_path'] . $sitePaths['file_name'],
                            $xmlData);

                        if (!empty($writeStatus['TYPE'])) {
                            $result = $writeStatus;
                        }
                    }

                } else {
                    $result = $sitePaths;
                }
                $dateRun = new DateTime();
                $result['DATE_RUN'] = $dateRun->toString();
                SitemapTable::update($ID,
                    ['DATE_RUN' => $dateRun]);

                return $nameNonChpu;
            }
        }
        return $nameNonChpu;
    }

    public static function actualizedSeoMetaStatAgent($siteID, $offset)
    {
        $limit = Option::get("sotbit.seometa",'AGENT_LIMIT_STATISTIC','10', $siteID);
        //TODO: need update, when check stat off in general settings?
        if(!Loader::includeModule(self::$moduleId))
            return "\Sotbit\Seometa\Agent::actualizedSeoMetaStatAgent('$siteID', $offset);";

        $statModule = Loader::includeModule("statistic");
        if($statModule){
            $proactive = \COption::GetOptionString("statistic", "DEFENCE_ON");
            if ($proactive === 'Y') {
                \COption::SetOptionString("statistic", "DEFENCE_ON", "N");
            }
        }

        $rsStat = SeometaStatisticsTable::getList([
            'select' => [
                'ID',
                'URL',
                'META_TITLE',
                'META_KEYWORDS',
                'META_DESCRIPTION',
            ],
            'filter' => ['SITE_ID'=>$siteID],
            'order' => ['ID'],
            'limit' => $limit,
            'offset' => $offset
        ])->fetchAll();

        if(!$rsStat){
            $offset = 0;
            if ($statModule && $proactive === 'Y') {
                \COption::SetOptionString("statistic", "DEFENCE_ON", "Y");
            }
            return "\Sotbit\Seometa\Agent::actualizedSeoMetaStatAgent('$siteID', $offset);";
        }

        foreach ($rsStat as $stat){
            $httpClient = new \Bitrix\Main\Web\HttpClient;
            $httpClient->setRedirect(false);
            $httpClient->post($stat['URL'], ["SEOMETA_STATUS_AGENT" => "Y", "SEOMETA_STATUS_CODE" => "Y", "SEOMETA_STAT_ID" => $stat["ID"]]);
            $status = $httpClient->getStatus();

            $arFields = [
                'PAGE_STATUS' => $status,
                'LAST_DATE_CHECK' => new DateTime()
            ];

            SeometaStatisticsTable::update($stat['ID'], $arFields);
            $offset++;
        }
        //$time = strtotime((new DateTime())->add('10 SEC')) - strtotime($arAgentActualized['NEXT_EXEC']);
        //that variable get from func CAgent::ExecuteAgents() in which call eval() for agent, and need if offset more than set limit
        global $pPERIOD;
        $pPERIOD = '0';

        if ($statModule && $proactive === 'Y') {
            \COption::SetOptionString("statistic", "DEFENCE_ON", "Y");
        }

        return "\Sotbit\Seometa\Agent::actualizedSeoMetaStatAgent('$siteID', $offset);";
    }
}

?>