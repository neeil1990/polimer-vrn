<?php

namespace Sotbit\Seometa\Link;

use Sotbit\Seometa\SitemapTable;
use \Bitrix\Main\Config\Option;

class XmlWriter extends AbstractWriter
{
    private static $Writer = false;
    private $dir = false;
    private $xmlVersion = '1.0';
    private $xmlAttr = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    private $chpuAll = array();
    private $sitemapSettings = array();
    private $index = 0;
    private $countWrited = 0;
    private $allCountWrited = 0;
    private $fileName = '';
    private $siteUrl = '';
    private $siteMapStatus = array();

    private function __construct($id, $dir, $SiteUrl, $isProgress = false)
    {
        if(!is_string($dir))
            throw new \Exception('DIR must be an string, ' . gettype($dir) . ' given');

        if(!file_exists($dir))
            throw new \Exception('Not Found Directory "' . $dir . '"');

        $this->id = $id;
        $this->dir = $dir;
        $this->siteUrl = $SiteUrl;
        $this->chpuAll = \Sotbit\Seometa\SeometaUrlTable::getAll();

        $sitemap = \Sotbit\Seometa\SitemapTable::getById($this->id)->fetch();
        $this->sitemapSettings = unserialize($sitemap['SETTINGS']);
        if (!$isProgress) {
            $this->setFileName();
            if(
                $this->getCountWrited() >= Option::get('sotbit.seometa', 'SEOMETA_SITEMAP_COUNT_LINKS', '50000') ||
                round($this->getFileSize()) >= Option::get('sotbit.seometa', 'SEOMETA_SITEMAP_FILE_SIZE', '50')
            ) {
                $this->incIndex();
                $this->setCountWrited(false);
                $this->WriteEnd();
                $this->setFileName();
            }
        }
    }

    private function incIndex() {
        $this->index++;
    }

    private function setCountWrited($reset = true) {
        if($reset == false)
            $this->countWrited = 0;
        else
            $this->countWrited++;

        $this->allCountWrited++;
        file_put_contents($_SERVER['DOCUMENT_ROOT']. '/seometa_link_count.txt', $this->allCountWrited);
    }

    private function getCountWrited() {
        return $this->countWrited;
    }

    private function getFileSize() {
        $fSize = false;
        if(strlen($this->getFileName()) > 0) {
            clearstatcache(true, $this->getFileName());
            $fSize = filesize($this->getFileName());
        }

        return ( $fSize !== false ? ($fSize / 1024) / 1024 : $fSize );
    }

    private function setFileName() {
        $this->fileName = $this->dir . 'sitemap_seometa_' . $this->id . '_'. $this->index . '.xml';
        file_put_contents($this->fileName, '<?xml version="' . $this->xmlVersion . '" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    }

    private function getFileName() {
        return $this->fileName;
    }

    public function getSiteMapLink($id) {
        if($id > 0)
        {
            $dbSitemap = SitemapTable::getById($id);
            $arSitemap = $dbSitemap->fetch();
        }

        if(is_array($arSitemap))
        {
            $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
        }
        else
        {
            return false;
        }

        $arSites = array();
        $rsSites = \CSite::GetById($arSitemap['SITE_ID']);

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
            return false;
        }

        if(isset($arSitemap['SETTINGS']['DOMAIN']) && !empty($arSitemap['SETTINGS']['DOMAIN']))
        {
            $SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'] . substr($arSite['DIR'], 0, -1);
            $this->siteUrl = $SiteUrl;
        }
        else
        {
            return false;
        }

        if(isset($arSitemap['SETTINGS']['FILENAME_INDEX']) && !empty($arSitemap['SETTINGS']['FILENAME_INDEX']))
        {
            $mainSitemapName = $arSitemap['SETTINGS']['FILENAME_INDEX'];
        }
        else
        {
            return false;
        }

        if(isset($arSitemap['SETTINGS']['FILTER_TYPE']) && !is_null($arSitemap['SETTINGS']['FILTER_TYPE']))
        {
            $FilterTypeKey = key($arSitemap['SETTINGS']['FILTER_TYPE']);
            $FilterCHPU = $arSitemap['SETTINGS']['FILTER_TYPE'][$FilterTypeKey];

            $FilterType = strtolower($FilterTypeKey . ((!$FilterCHPU) ? '_not' : '') . '_chpu');
        }
        else
        {
            return false;
        }

        $mainSitemapUrl = $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . $mainSitemapName;

        if(isset($arSitemap['DATE_RUN']) && !empty($arSitemap['DATE_RUN'])) {
            $this->siteMapStatus['DATE_RUN'] = $arSitemap['DATE_RUN'];
        }

        if(isset($arSitemap['TIMESTAMP_CHANGE']) && !empty($arSitemap['TIMESTAMP_CHANGE'])) {
            $this->siteMapStatus['TIMESTAMP_CHANGE'] = $arSitemap['TIMESTAMP_CHANGE'];
        }
        return $mainSitemapUrl;
    }

    public static function getInstance($id, $dir, $SiteUrl, $isProgress = false)
    {
        if(self::$Writer === false)
            self::$Writer = new XmlWriter($id, $dir, $SiteUrl, $isProgress);

        self::$Writer->setDir($dir);
        return self::$Writer;
    }

    public function AddRow(array $arFields)
    {
    }

    public function setDir($dir)
    {
        if(!is_string($dir))
            throw new \Exception('DIR must be an string, ' . gettype($dir) . ' given');

        if(!file_exists($dir))
            throw new \Exception('Not found derictory "' . $dir . '"');

        $this->dir = $dir;
    }

    public function Write(array $arFields)
    {
        if(empty($this->dir) || empty($this->id))
            return; //can throw new \Exception('do not have dir or id');

        $LOC = $arFields['real_url'];
        $url = \Sotbit\Seometa\SeometaUrlTable::getByRealUrl(str_replace($this->siteUrl, '', $LOC));

        // if URL is active then replace REAL_URL with NEW_URL
        if(!empty($url) && isset($this->chpuAll[$url['ID']]))
        {
            $LOC = str_replace($url['REAL_URL'], $url['NEW_URL'], $LOC);
            unset($this->chpuAll[$url['ID']]);

            \Sotbit\Seometa\SeometaUrlTable::update($url['ID'], array('IN_SITEMAP' => 'Y'));
        }
        else
        {
            if(isset($this->sitemapSettings['EXCLUDE_NOT_SEF']) && $this->sitemapSettings['EXCLUDE_NOT_SEF'] == 'Y')
            {
                return;
            }
            else
            {
                $newUrl = array(
                    'CONDITION_ID' => $arFields['condition_id'],
                    'REAL_URL' => $arFields['real_url'],
                    'NEW_URL' => $arFields['new_url'],
                    'NAME' => $arFields['name'],
                    'PROPERTIES' => serialize($arFields['properties']),
                    'iblock_id' => $arFields['iblock_id'],
                    'section_id' => $arFields['section_id'],
                    'PRODUCT_COUNT' => $arFields['product_count'],
                    'DATE_CHANGE' => new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                    'IN_SITEMAP' => 'Y',
                );

                $allUrlsByCond = \Sotbit\Seometa\SeometaUrlTable::getAllByCondition($arFields['condition_id']);

                if($allUrlsByCond)
                {
                    $count = 0;
                    foreach($allUrlsByCond as $url)
                    {
                        if($LOC == $url['REAL_URL'] && $arFields['new_url'] == $url['NEW_URL'])
                        {
                            $count++; // found a match
                            $urlID = $url['ID'];
                            break;
                        }
                    }

                    if($count == 1)
                    {
                        \Sotbit\Seometa\SeometaUrlTable::update($urlID, array('IN_SITEMAP' => 'Y'));
                    }
                    else
                    {
                        \Sotbit\Seometa\SeometaUrlTable::add($newUrl);
                    }
                }
                else
                {
                    \Sotbit\Seometa\SeometaUrlTable::add($newUrl);
                }
            }
        }

        if(substr($LOC, 0, 4) != 'http')
        {
            $LOC = $this->siteUrl . $LOC;
        }

        $url = "<url>";
        $url .= "<loc>" . str_replace('&', '&amp;', $LOC) . "</loc>";
        $url .= "<lastmod>" . str_replace(' ', 'T', date('Y-m-d H:i:sP', strtotime($this->arCondition['DATE_CHANGE']))) . "</lastmod>";

        if(isset($this->arCondition['CHANGEFREQ']) && !is_null($this->arCondition['CHANGEFREQ']))
            $url .= "<changefreq>" . $this->arCondition['CHANGEFREQ'] . "</changefreq>";

        if(isset($this->arCondition['PRIORITY']) && !is_null($this->arCondition['PRIORITY']))
            $url .= "<priority>" . $this->arCondition['PRIORITY'] . "</priority>";

        $url .= "</url>";

        if(
            $this->getCountWrited() >= Option::get('sotbit.seometa', 'SEOMETA_SITEMAP_COUNT_LINKS', '50000') ||
            round($this->getFileSize()) >= Option::get('sotbit.seometa', 'SEOMETA_SITEMAP_FILE_SIZE', '50')
        ) {
            $this->incIndex();
            $this->setCountWrited(false);
            $this->WriteEnd();
            $this->setFileName();
        }

        file_put_contents($this->getFileName(), $url, FILE_APPEND);
        $this->setCountWrited();

        unset($url);
    }

    public function writeMainSiteMap() {
        $siteMapLink = $this->getSiteMapLink($this->id);

        if($siteMapLink && file_exists($siteMapLink)) {
            $xml = simplexml_load_file($siteMapLink);

            $arrSeometaSitemapFiles = self::getSeometaFiles($_SERVER['DOCUMENT_ROOT']);

            for($i = 0; $i < count($xml->sitemap); $i++)
            {
                if (isset($xml->sitemap[$i]->loc) && in_array($xml->sitemap[$i]->loc, $arrSeometaSitemapFiles)) {
					if(count($xml->sitemap[$i]->lastmod) == 2) {
                        unset($xml->sitemap[$i]->lastmod);
                    }
                    $xml->sitemap[$i]->lastmod = date('Y-m-d\TH:i:sP');
                    $arKey = array_search($xml->sitemap[$i]->loc, $arrSeometaSitemapFiles);
                    if($arKey !== false) {
                        unset($arrSeometaSitemapFiles[$arKey]);
                    }
                }
            }

            if(count($arrSeometaSitemapFiles) > 0) // if sitemap_seometa is not found then add it to main sitemap
            {
                foreach ($arrSeometaSitemapFiles as $item) {
                    $NewSitemap = $xml->addChild("sitemap");
                    $NewSitemap->addChild("loc", $item);
                    $NewSitemap->addChild("lastmod", date('Y-m-d\TH:i:sP'));
//                    $NewSitemap->addChild(
//                        "lastmod",
//                        (isset($this->siteMapStatus['DATE_RUN']) && !empty($this->siteMapStatus['DATE_RUN'])) ?
//                            str_replace(' ', 'T', date('Y-m-d H:i:sP', strtotime($this->siteMapStatus['DATE_RUN']))) :
//                            str_replace(' ', 'T', date('Y-m-d H:i:sP', strtotime($this->siteMapStatus['TIMESTAMP_CHANGE'])))
//                    );
                }
            }

            file_put_contents($siteMapLink, $xml->asXML());

            if(file_exists($_SERVER['DOCUMENT_ROOT']. '/seometa_link_count.txt')) {
                unlink($_SERVER['DOCUMENT_ROOT']. '/seometa_link_count.txt');
            }
        }
    }

    public function WriteEnd()
    {
        /*foreach ($this->chpuAll as $chpu)
        {
            $LOC = $chpu['NEW_URL'];

            if (substr($LOC, 0, 4) != 'http')
            {
                $LOC = $this->siteUrl . $LOC;
            }

            $url = "<url>";
            $url .= "<loc>" . $LOC . "</loc>";
            $url .= "<lastmod>" . str_replace(' ', 'T', date('Y-m-d H:i:sP', strtotime($chpu['DATE_CHANGE']))) . "</lastmod>";
            $url .= "</url>";

            file_put_contents($this->dir. 'sitemap_seometa_' . $this->id . '.xml', $url, FILE_APPEND);
            unset($url, $LOC);
        }*/
        file_put_contents($this->getFileName(), '</urlset>', FILE_APPEND);
    }

    private function getSeometaFiles($path) {
        $result = array();
        if(file_exists($path)) {
            $arFiles = scandir($path);

            if($arFiles) {
                foreach ($arFiles as $arFile) {
                    if(stripos($arFile, 'sitemap_seometa') !== false) {
                        $result [] = $this->siteUrl .'/'. $arFile;
                    }
                }
            }
        }

        return $result;
    }
}
