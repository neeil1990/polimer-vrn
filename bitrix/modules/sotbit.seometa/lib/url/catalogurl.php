<?php
namespace Sotbit\Seometa\Url;

use Bitrix\Main\Config\Option;
use Sotbit\Seometa\Condition\Condition;
use Sotbit\Seometa\Generator\AbstractGenerator;
use Sotbit\Seometa\Generator\BitrixGenerator;
use Sotbit\Seometa\Helper\Settings;

/**
 * Class CatalogUrl
 * @package Sotbit\Seometa\Url
 */
class CatalogUrl extends AbstractUrl {
    /**
     * @var false|mixed
     */
    private $iblockId = false;
    /**
     * @var array|false
     */
    private $iblock = false;
    /**
     * @var string
     */
    protected $propertyTemplate = '#PROPERTY_CODE#-is-#PROPERTY_VALUE#';

    /**
     * @var string
     */
    private $filterType = '';

    /**
     * CatalogUrl constructor.
     * @param Condition $condition
     * @param $siteID
     */
    public function __construct(
        Condition $condition,
        $siteID
    ) {
        $this->iblockId = $condition->getIblockId();
        $this->iblock = \CIBlock::GetById($this->iblockId)->Fetch();
        if ($condition->FILTER_TYPE == 'default') {
            $condition->FILTER_TYPE = Settings::getSettingsForSite($siteID)->FILTER_TYPE;
        }

        $this->filterType = $condition->FILTER_TYPE;
        if (!empty($condition->CATALOG_URL)) {
            $this->template = $this->mask = $condition->FILTER_TYPE;
        } else {
            $this->template = $this->mask = $this->getIblockTemplate($condition->FILTER_TYPE, $siteID);
        }

        $replace = '#PRICES#/#FILTER#/#PROPERTIES#';
        $this->propertyFields = [
            '#PROPERTY_CODE#' => 'CODE',
            '#PROPERTY_VALUE#' => 'URL_ID'
        ];
        if ($condition->FILTER_TYPE == 'bitrix_not_chpu') {
            $this->propertyFields = [
                '#PROPERTY_CODE#' => 'CONTROL_NAME',
                '#PROPERTY_VALUE#' => 'HTML_VALUE'
            ];

            $replace = '&#PRICES#&#FILTER#&#PROPERTIES#';
        } elseif ($condition->FILTER_TYPE == 'combox_chpu') {
            $this->propertyFields = [
                '#PROPERTY_CODE#' => 'CODE',
                '#PROPERTY_VALUE#' => 'VALUE'
            ];
        }

        $this->mask = $this->templateWithSection = str_replace('#FILTER_PARAMS#', $replace, $this->mask);

        if(!empty($spaceReplacement = $condition->getMeta('SPACE_REPLACEMENT')) && $condition->FILTER_TYPE === 'combox_chpu') {
            parent::setSpaceReplacement($spaceReplacement);
        }
    }

    /**
     * @param $template
     */
    public function setTemplate(
        $template
    ) {
        $this->template = $this->mask = $template;
    }

    /**
     * @param string $chpuType
     * @return string
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function getIblockTemplate(
        $chpuType = '',
        $siteId = 's1'
    ) {
        $sectionMask = !empty($this->iblock)
            ? '/' . trim(str_replace('#SITE_DIR#', '', $this->iblock['SECTION_PAGE_URL']), '/')
            : '';

        if ($chpuType == 'default') {
            $chpuType = Settings::getSettingsForSite($siteId)->FILTER_TYPE;
        }

        $filterSef = Option::get('sotbit.seometa', 'FILTER_SEF', '', $siteId);
        if (!empty($filterSef)) {
            $chpuType = 'custom';
        }

        switch ($chpuType) {
            case 'custom':
                $mask = $sectionMask . $filterSef;
                break;

            case 'misshop_chpu':
            case 'bitrix_chpu':
                $mask = $sectionMask . "/filter/#FILTER_PARAMS#/apply/";
                break;

            case 'bitrix_not_chpu':
                $mask = $sectionMask . "/?set_filter=y#FILTER_PARAMS#";
                break;

            case 'combox_chpu':
                $mask = $sectionMask . "/filter/#FILTER_PARAMS#/";
                break;

            case 'combox_not_chpu':
                $mask = $sectionMask . "/?#FILTER_PARAMS#";
                break;

            default:
                $mask = $sectionMask;
        }

        if ($this->hasIblockPlaceholders($mask)) {
            $this->replaceIblockHolders($mask);
        }

        return $this->mask = $mask;
    }

    /**
     * @param $mask
     * @return false|int
     */
    protected function hasIblockPlaceholders(
        $mask
    ) {
        return preg_match('/\#(IBLOCK_ID|IBLOCK_CODE|IBLOCK_TYPE_ID)\#/', $mask);
    }

    /**
     * @param $mask
     */
    protected function replaceIblockHolders(
        $mask
    ) {
        preg_match_all('/\#(IBLOCK_ID|IBLOCK_CODE|IBLOCK_TYPE_ID)\#/', $mask, $match);
        $keys = [];
        if ( !empty($match[0])) {
            $keys = $match[0];
        }

        if (empty($keys)) {
            return;
        }

        $result = [];
        foreach ($keys as &$key) {
            $clearKey = str_replace(['#IBLOCK_', '#'], '', $key);
            if (isset($this->iblock[$clearKey]) && $clearKey != 'TYPE_ID') {
                $result[$key] = $this->iblock[str_replace(['#IBLOCK_', '#'], '', $key)];
            } elseif ($clearKey == 'TYPE_ID') {
                $result[$key] = $this->iblockId['IBLOCK_TYPE_ID'];
            }
        }

        $this->mask = $mask;
        $this->ReplaceHolders($result);
    }

//    /**
//     * @param PropertySet $propertySet
//     */
//    public function replacePropertySet(PropertySet $propertySet) {
//        if(preg_match('/#FILTER_PARAMS#/', $this->mask))
//            foreach($propertySet as $propertySetEntity) {
//
//            }
//    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed|void
     */
    protected function replacePropertiesFromSet(
        array &$filteredProps,
        AbstractGenerator $generator
    ) {
        $glue = '/';
        if ($generator instanceof BitrixGenerator) {
            $glue = '&';
        }

        if (empty($filteredProps['PROPERTY'])) {
            $this->mask = str_replace($glue . '#PROPERTIES#', '', $this->mask);
            return;
        }

        $result = [];
        foreach ($filteredProps['PROPERTY'] as $propertySetEntity) {
            $result[] = $generator->generate($this, $propertySetEntity);
        }

        $this->mask = str_replace('#PROPERTIES#', implode($glue, $result), $this->mask);
    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed|void
     */
    protected function replacePriceFromSet(array &$filteredProps, AbstractGenerator $generator) {
        $glue = '/';
        $search = '#PRICES#';
        if($generator instanceof BitrixGenerator) {
            $glue = '&';
        }

        if(!isset($filteredProps['PRICE']) || empty($filteredProps['PRICE'])) {
            $this->mask = str_replace($glue . $search, '', $this->mask);
            return;
        }

        $result = [];

        foreach($filteredProps['PRICE'] as $propertySetEntity) {
            $result[] = $generator->generate($this, $propertySetEntity);
        }

        $this->mask = str_replace($search, implode($glue, $result), $this->mask);
    }

    /**
     * @param array $filteredProps
     * @param AbstractGenerator $generator
     * @return mixed|void
     */
    protected function replaceFilterFromSet(
        array &$filteredProps,
        AbstractGenerator $generator
    ) {
        $result = [];
        $glue = '/';
        if ($generator instanceof BitrixGenerator) {
            $glue = '&';
        }

        if (empty($filteredProps['FILTER'])) {
            $this->mask = str_replace($glue . '#FILTER#', '', $this->mask);
            return;
        }

        foreach ($filteredProps['FILTER'] as $propertySetEntity) {
            $result[] = $generator->generate($this, $propertySetEntity);
        }

        $this->mask = str_replace('#FILTER#', implode($glue, $result), $this->mask);
    }

    /**
     * @param false $full
     */
    public function cleanTemplate(
        $full = false
    ) {
        parent::cleanTemplate($full);

        $replace = '#PRICES#/#FILTER#/#PROPERTIES#';
        if ($this->filterType == 'bitrix_not_chpu') {
            $replace = '&#PRICES#&#FILTER#&#PROPERTIES#';
        }

        $this->mask = str_replace('#FILTER_PARAMS#', $replace, $this->mask);
    }
}
