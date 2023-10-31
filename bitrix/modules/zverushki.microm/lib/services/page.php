<?php

namespace Zverushki\Microm\Services;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use CBitrixComponent;
use CCatalog;
use CCatalogSKU;
use CComponentEngine;
use CIBlock;
use CPHPCache;
use Zverushki\Microm\Entities\PageData;
use Zverushki\Microm\Entities\PageRule;

/**
 *
 */
class Page
{
    /**
     * @var string
     */
    private $siteId;

    /**
     * @var string
     */
    private $siteDir;

    /**
     *
     */
    public function __construct()
    {
        $this->siteId = Context::getCurrent()->getSite();
        $this->siteDir = SITE_DIR;
    }

    /**
     * @return PageData
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function get(): PageData
    {
        $pageRule = $this->guessPagePath();

        $data = [];
        $variables = $pageRule->variables();

        switch ($pageRule->data()['key']) {
            case 'Element':
            case 'CatalogElement':
                $data = ElementTable::getList([
                    'filter' => $variables['ELEMENT_CODE']
                        ? ['CODE' => $variables['ELEMENT_CODE']]
                        : ['ID' => $variables['ELEMENT_ID']],
                    'select' => ['ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
                    'cache'  => ['ttl' => 86400, 'cache_joins' => true],
                ])
                    ->fetch();

                $data['picture'] = $data['PREVIEW_PICTURE'] ?: $data['DETAIL_PICTURE'];
                break;

            case 'Section':
            case 'CatalogSection':
                $data = SectionTable::getList([
                    'filter' => $variables['SECTION_CODE']
                        ? ['CODE' => $variables['SECTION_CODE']]
                        : ['ID' => $variables['SECTION_ID']],
                    'select' => ['ID', 'PICTURE'],
                    'cache'  => ['ttl' => 86400, 'cache_joins' => true],
                ])
                    ->fetch();

                $data['picture'] = $data['PICTURE'];
                break;
        }

        return new PageData((array)$data);
    }

    /**
     * @return PageRule
     * @throws \Bitrix\Main\LoaderException
     */
    private function guessPagePath(): PageRule
    {
        $sefFolder = $this->siteDir;
        $urls = $this->getIblockUrlTemplates();
        $sefUrlTemplates = [];
        $variables = [];

        foreach ($urls as $urlTemplate => $item) {
            if (strpos($urlTemplate, $sefFolder) === 0) {
                $urlTemplate = substr($urlTemplate, strlen($sefFolder));
            }

            $url = str_replace('#SITE_DIR#/', '', $urlTemplate);
            $url = str_replace('#SITE_DIR#', '', $url);
            $url = str_replace('//', '/', $url);

            $sefUrlTemplates[($uniquePageId = 'p'.md5($item['key'].$url))] = $url;
            $urls[$uniquePageId] = ['url_template' => $urlTemplate, 'data' => $item];
        }

        $componentEngine = new CComponentEngine;

        if (Loader::includeModule('iblock')) {
            $componentEngine->addGreedyPart('#SECTION_CODE_PATH#');
            $componentEngine->addGreedyPart('#SMART_FILTER_PATH#');
            $componentEngine->setResolveCallback(['CIBlockFindTools', 'resolveComponentEngine']);
        }

        $arUrlTemplates = CComponentEngine::makeComponentUrlTemplates([], $sefUrlTemplates);
        $uniqPageId = $componentEngine->guessComponentPath($sefFolder, $arUrlTemplates, $variables);

        if ($urls[$uniqPageId]['data']['is_iblock']) {
            $component = new CBitrixComponent;

            $component->arParams['IBLOCK_ID'] = $urls[$uniqPageId]['data']['id'];
            $component->arParams['DETAIL_STRICT_SECTION_CHECK'] = 'N';

            $componentEngine = new CComponentEngine($component);

            $componentEngine->addGreedyPart("#SECTION_CODE_PATH#");
            $componentEngine->addGreedyPart("#SMART_FILTER_PATH#");
            $componentEngine->setResolveCallback(['CIBlockFindTools', 'resolveComponentEngine']);

            $uniqPageId = $componentEngine->guessComponentPath($sefFolder, $arUrlTemplates, $variables);

            if (strpos($urls[$uniqPageId]['data']['key'], 'Element') !== false) {
                if (!$variables['ELEMENT_CODE'] && $variables['CODE']) {
                    $variables['ELEMENT_CODE'] = $variables['CODE'];
                }

                if (!$variables['ELEMENT_ID'] && $variables['ID']) {
                    $variables['ELEMENT_ID'] = $variables['ID'];
                }
            }

            if (strpos($urls[$uniqPageId]['data']['key'], 'Section') !== false) {
                if (!$variables['SECTION_CODE'] && $variables['CODE']) {
                    $variables['SECTION_CODE'] = $variables['CODE'];
                }

                if (!$variables['SECTION_ID'] && $variables['ID']) {
                    $variables['SECTION_ID'] = $variables['ID'];
                }
            }
        }

        if ($urls[$uniqPageId]) {
            if (strpos($urls[$uniqPageId]['url_template'], '?') !== false) {
                $urlTemplateItem = parse_url($urls[$uniqPageId]['url_template']);

                if ($urlTemplateItem['query']) {
                    $urlTemplateQuery = [];
                    parse_str($urlTemplateItem['query'], $urlTemplateQuery);

                    foreach ($urlTemplateQuery as $n => $v) {
                        if (array_key_exists($n, $_REQUEST) && strlen($_REQUEST[$n]) > 0) {
                            $variables[$n] = $_REQUEST[$n];
                        }
                    }
                }
            }

            return new PageRule([
                'data'      => $urls[$uniqPageId]['data'],
                'condition' => str_replace('#SITE_DIR#/', '#SITE_DIR#', $urls[$uniqPageId]['url_template']),
                'variables' => $variables,
            ]);
        }

        $server = Context::getCurrent()->getServer();

        return new PageRule([
            'data'      => [],
            'condition' => '#SITE_DIR#'.substr($server->getRequestUri(), strlen($this->siteDir)),
            'variables' => [],
        ]);
    }

    /**
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    private function getIblockUrlTemplates(): array
    {
        $URLs = [];

        $obCache = new CPHPCache();

        $cacheTime = 864300;
        $cacheId = md5($cacheTime.$this->siteId.__FUNCTION__);
        $cachePath = '/zverushki/microm/iblock_url_templates/';

        if ($obCache->InitCache($cacheTime, $cacheId, $cachePath)) {
            $URLs = $obCache->GetVars();
        } elseif ($obCache->StartDataCache()) {
            $catalogIb = [];
            $catalogSKUId = [];

            if (Loader::includeModule('catalog')) {
                $db = CCatalog::getList();

                while (($a = $db->fetch()) !== false) {
                    if (is_array(CCatalogSKU::GetInfoByOfferIBlock($a['IBLOCK_ID']))) {
                        $catalogSKUId[] = $a['IBLOCK_ID'];
                    } else {
                        $catalogIb[] = $a['IBLOCK_ID'];
                    }
                }
            }

            $db = CIBlock::GetList(
                [],
                ['SITE_ID' => $this->siteId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y', '!ID' => $catalogSKUId],
                false
            );
            while (($a = $db->fetch()) !== false) {
                if (isset($URLs[$a['DETAIL_PAGE_URL']]) || !$a['DETAIL_PAGE_URL']) {
                    continue;
                }

                $prefix = in_array($a['ID'], $catalogIb) ? 'Catalog' : '';

                if (strpos($a['SECTION_PAGE_URL'], '#IBLOCK_CODE#')) {
                    $a['SECTION_PAGE_URL'] = str_replace('#IBLOCK_CODE#', $a['CODE'], $a['SECTION_PAGE_URL']);
                }

                if (strpos($a['DETAIL_PAGE_URL'], '#IBLOCK_CODE#')) {
                    $a['DETAIL_PAGE_URL'] = str_replace('#IBLOCK_CODE#', $a['CODE'], $a['DETAIL_PAGE_URL']);
                }

                if ($a['SECTION_PAGE_URL']) {
                    $URLs[$a['SECTION_PAGE_URL']] = [
                        'id'        => $a['ID'],
                        'key'       => $prefix.'Section',
                        'is_iblock' => true,
                    ];
                }

                $URLs[$a['DETAIL_PAGE_URL']] = [
                    'id'        => $a['ID'],
                    'key'       => $prefix.'Element',
                    'is_iblock' => true,
                ];
            }

            $obCache->EndDataCache($URLs);
        }

        return $URLs;
    }
}