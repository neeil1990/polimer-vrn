<?php

namespace Sotbit\Seometa\Link;

class XmlWriter extends AbstractWriter
{
    private static $Writer = false;
    private $dir = false;
    private $xmlVersion = '1.0';
    private $xmlAttr = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    private $chpuAll = array();
    private $sitemapSettings = array();

    private function __construct($id, $dir, $SiteUrl)
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

        file_put_contents($dir . 'sitemap_seometa_' . $id . '.xml', '<?xml version="' . $this->xmlVersion . '" encoding="utf-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    }

    public static function getInstance($id, $dir, $SiteUrl)
    {
        if(self::$Writer === false)
            self::$Writer = new XmlWriter($id, $dir, $SiteUrl);

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
        file_put_contents($this->dir . 'sitemap_seometa_' . $this->id . '.xml', $url, FILE_APPEND);
        unset($url);
    }

    public function WriteEnd()
    {
        if(empty($this->dir) || empty($this->id))
            return;

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

        if(!empty($this->dir) && !empty($this->id))
            file_put_contents($this->dir . 'sitemap_seometa_' . $this->id . '.xml', '</urlset>', FILE_APPEND);
    }
}
