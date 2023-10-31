<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Helper\XMLMethods;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SitemapTable;

Loader::includeModule('iblock');

class CSeoMetaSitemapLight
{
    private $seometaSitemapFile = 'sitemap_seometa_';
    private $maxCountLinksPerFile = '';
    private $requestData = [];

    public function initRequestData()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        if (!empty($request->get('ID'))) {
            $this->requestData['ID'] = intval($request->get('ID'));
            $this->requestData['limit'] = intval($request->get('limit')) ?? 0;
            $this->requestData['offset'] = intval($request->get('offset')) ?? 0;
            $this->requestData['sitemap_index'] = intval($request->get('sitemap_index')) ?? 0;
            $this->requestData['count_chpu'] = intval($request->get('count_chpu')) ?? 0;
            $this->requestData['count_link_writed'] = intval($request->get('count_link_writed')) ?? 0;
            $this->requestData['action'] = $request->get('action') ?? '';
        } elseif (!empty($request->get('data'))) {
            $this->requestData = Json::decode($request->get('data'));
        }
    }

    public function getRequestData($name)
    {
        return $this->requestData[$name];
    }

    public function setRequestData($name, $value)
    {
        $this->requestData[$name] = $value;
    }

    function generateSitemap($arrLinks, $siteDomain)
    {
        $sitePaths = self::pathMainSitemap($this->requestData['ID']);
        if ($sitePaths['TYPE'] == 'ERROR') {
            return $sitePaths;
        }

        if ($this->requestData['count_link_writed'] >= $this->maxCountLinksPerFile) {
            $this->requestData['sitemap_index']++;
        }

        $sitemapFileName = $sitePaths['abs_path']
            . $this->seometaSitemapFile
            . $this->requestData['ID'] . '_'
            . $this->requestData['sitemap_index'] . '.xml';

        if (file_exists($sitemapFileName)) {
            $xml = file_get_contents($sitemapFileName);
            $xmlCurrentSize = filesize($sitemapFileName);
            $data = (new XMLMethods)->xml2ary($xml);
        }

        $arSitemap = self::getSitemapSettings($this->requestData['ID']);
        $countChpuLinks = count($arrLinks);
        $countNumberSymbForChange = $countChpuLinks * 2; //count bytes numbers in tags
        $countUrlSymbForChange = ($countChpuLinks * 2) * 3; //count bytes which need for change numbers in tags to the 'url', 3 that count symb in 'url'
        $version = 38; //count bytes which place version tag
        $urlset = 69; //count bytes which place urlset tag
        foreach ($arrLinks as $link) {
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

            $item[] = [
                '_c' => [
                    'loc' => [
                        '_v' => $arSitemap['SETTINGS']['PROTO'] . $siteDomain . ($link['NEW_URL'] ?: $link['REAL_URL'])
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
        }

        if (
            !empty($data)
            && ((intval($xmlCurrentSize) + strlen((new XMLMethods)->ary2xml($item)) - $countNumberSymbForChange + $countUrlSymbForChange + $version + $urlset) / 1000) / 1000
            >= intval(Option::get(CSeoMeta::MODULE_ID, 'SEOMETA_SITEMAP_FILE_SIZE', '50', $arSitemap['SITE_ID']))
        ) {
            unset($data);
            $this->requestData['sitemap_index']++;
        }

        $filePath = $sitePaths['abs_path']
            . $this->seometaSitemapFile
            . $this->requestData['ID'] . '_'
            . $this->requestData['sitemap_index'] . '.xml';

        if (!$data || $_REQUEST['action'] != 'sitemap_in_progress') {
            $data = (new XMLMethods)->createXml($filePath);
            if (!empty($data['TYPE'])) {
                $result = $data;
            }
        }

        (new XMLMethods)->ins2ary($data['urlset']['_c']['url'],
            $item,
            count($data['urlset']['_c']['url']));

        if (empty($this->requestData['count_chpu'])) {
            $this->requestData['count_chpu'] = self::getCountChpuUrls($this->requestData['ID']);
        }

        $curPercent = intdiv(100 * (count($item) + ($this->requestData['limit'] * $this->requestData['offset'])),
            $this->requestData['count_chpu']);

        $result['limit'] = $this->requestData['limit'] ?: 0;
        $result['offset'] = $this->requestData['offset'] ? intval($this->requestData['offset']) + 1 : 1;
        $result['count_link_writed'] = $this->requestData['count_link_writed'] + count($item);
        $result['sitemap_index'] = $this->requestData['sitemap_index'];
        $result['ID'] = $this->requestData['ID'];

        $xmlData = (new XMLMethods)->ary2xml($data);
        $writeStatus = (new XMLMethods)->writeSiteMap($filePath,
            $xmlData);

        if (!empty($writeStatus['TYPE'])) {
            $result = $writeStatus;
        }

        if (!empty($data['TYPE'])) {
            $result = $data;
        }

        $result['progressbar'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_SITEMAP_GENERATING'),
            Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE'),
            $curPercent);

        return Json::encode($result,
            JSON_INVALID_UTF8_IGNORE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    function getCountChpuUrls($ID)
    {
        $arSitemap = self::getSitemapSettings($ID);

        $filter = [];
        if ($arSitemap['SETTINGS']['EXCLUDE_NOT_SEF'] == 'Y') {
            $filter = ['ACTIVE' => 'Y'];
        }

        $count = SeometaUrlTable::getList(
            [
                'select' => ['CNT'],
                'filter' => $filter,
                'runtime' => [
                    new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ]
            ]
        )->fetch();

        if (intval($count) || $count == 0) {
            $count = $count['CNT'];
        }

        return $count;
    }

    function generateSitemapFinish($ID, $index)
    {
        $sitePaths = self::pathMainSitemap($ID);

        if (!empty($sitePaths['abs_path'])) {
            $xml = file_get_contents($sitePaths['abs_path'] . $sitePaths['file_name']);
            if (empty($xml)) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex></sitemapindex>';
            }
            $data = (new XMLMethods)->xml2ary($xml);

            if (is_array($data['sitemapindex']['_c']['sitemap'])) {
                (new XMLMethods)->delSeometaFromMainSitemap($data['sitemapindex']['_c']['sitemap']);
            }

            $item = (new XMLMethods)->seometaMainSitemapFiles($index,
                $ID,
                $sitePaths['url']);

            if (!empty($item) && is_array($item)) {
                $count = $data['sitemapindex']['_c']['sitemap'] ? count($data['sitemapindex']['_c']['sitemap']) : 0;
                (new XMLMethods)->ins2ary($data['sitemapindex']['_c']['sitemap'],
                    $item,
                    $count);

                $xmlData = (new XMLMethods)->ary2xml($data);
                $writeStatus = (new XMLMethods)->writeSiteMap($sitePaths['abs_path'] . $sitePaths['file_name'],
                    $xmlData);

                if (!empty($writeStatus['TYPE'])) {
                    $result = $writeStatus;
                }
            }

            $result['progressbar'] = SitemapRuntime::showProgress(Loc::getMessage('SEO_META_SITEMAP_FINISH'),
                Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE'),
                100);
        } else {
            $result = $sitePaths;
        }
        $dateRun = new Bitrix\Main\Type\DateTime();
        $result['DATE_RUN'] = $dateRun->toString();
        $result['ID'] = $ID;
        SitemapTable::update($ID,
            ['DATE_RUN' => $dateRun]);

        $result['STATUS'] = 'finish';
        return Json::encode($result);
    }

    public function pathMainSitemap(
        $ID
    )
    {
        $arSitemap = $this->getSitemapSettings($ID);

        $arSite = CSite::GetById($arSitemap['SITE_ID'])->Fetch();
        $result['url'] = $arSitemap['SETTINGS']['PROTO'] . $arSitemap['SETTINGS']['DOMAIN'] . $arSite['DIR'];
        $result['abs_path'] = $arSite['ABS_DOC_ROOT'] . $arSite['DIR'];
        $result['domain_dir'] = $arSitemap['SETTINGS']['DOMAIN'];
        $result['file_name'] = $arSitemap['SETTINGS']['FILENAME_INDEX'];
        $result['site_id'] = $arSitemap['SITE_ID'];

        if (file_exists($result['abs_path'] . $arSitemap['SETTINGS']['FILENAME_INDEX'])) {
            return $result;
        }

        return [
            'TYPE' => 'ERROR',
            'MSG' => $arSitemap['SETTINGS']['FILENAME_INDEX'] . ' not found!'
        ];
    }

    private function getSitemapSettings(
        $ID
    )
    {
        $arSitemap = SitemapTable::getById($ID)->fetch();
        $this->maxCountLinksPerFile = Option::get(CSeoMeta::MODULE_ID,
            'SEOMETA_SITEMAP_COUNT_LINKS',
            '50000',
            $arSitemap['SITE_ID']);
        $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
        $arSitemap['SETTINGS']['PROTO'] = !empty($arSitemap['SETTINGS']['PROTO']) ? 'https://' : 'http://';

        return $arSitemap;
    }

    public function deleteOldSeometaSitemaps($dir)
    {
        $result = false;
        if (is_dir($dir) && $res = opendir($dir)) {
            while (($item = readdir($res))) {
                if ($item == '..' || $item == '.') {
                    continue;
                }
                if (
                    mb_strpos($item, $this->seometaSitemapFile) !== false
                    && is_file($dir . $item)
                ) {
                    unlink($dir . $item);
                }
            }
            closedir($res);
            $result = true;
        }

        return $result;
    }


    public function markUrlsExcludeSitemap($site_id)
    {
        if (($this->requestData['action'] === 'write_sitemap') || ($this->requestData['action'] === 'get_section_list')) {
            $seometaUrlCollection = SeometaUrlTable::getList(['select' => ['*'], 'filter' => ['SITE_ID' => $site_id]])->fetchCollection();
            $elements = $seometaUrlCollection->getAll();
            foreach ($elements as $element) {
                $element->set('IN_SITEMAP', false);
            }

            $seometaUrlCollection->save();
        }
    }
}
