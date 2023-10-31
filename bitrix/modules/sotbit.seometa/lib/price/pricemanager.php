<?
namespace Sotbit\Seometa\Price;

use \Bitrix\Main\Loader;
use Sotbit\Seometa\Condition\Condition;
use Sotbit\Seometa\Property\PropertyCollection;
use Sotbit\Seometa\Property\PropertySetCollection;
use Sotbit\Seometa\Property\PropertySetEntity;

class PriceManager {
    private static $instance = false;

//    private $template = '/condib(min|max)[filter]?price([a-z_0-9-]+)/i';
    private $template = '/condib(min|max)(filter)?price([a-z_0-9-]+)/i';
    private $condition;
    private $data = [];
    private $priceBorders = [];
    private $currency = '';
    private $convertCurrencyId = '';

    public static function getInstance() {
        if(self::$instance == false) {
            self::$instance = new PriceManager();
        }

        return self::$instance;
    }

    public function setConditionPrices(Condition $condition) {
        $this->condition = $condition;

        if($condition->hasPrice()) {
            $this->data = \CIBlockPriceTools::GetCatalogPrices($condition->getIblockId(), $this->getPriceCodes());
        }
    }

    public function setCurrencies() {
        if(\Bitrix\Main\Loader::includeModule("currency")) {
            $result = \Bitrix\Currency\CurrencyTable::getList(array(
                'select' => array(
                    'CURRENCY'
                ),
                'filter' => array(
                    '=BASE' => 'Y'
                )
            ))->fetch();

            $this->currency = $result['CURRENCY'];
            $siteIds = $this->condition->getSites();
            $this->convertCurrencyId = \COption::GetOptionString( \CCSeoMeta::MODULE_ID, 'CURRENCY_TYPE', $this->currency, $siteIds[0]);
        }
    }

    public function getPriceByCode($priceCode) {
        return isset($this->data[$priceCode]) ? $this->data[$priceCode] : false;
    }

    public function getBorderPriceBySection($sectionId, $priceGroup , $border) {
        $order = 'asc';

        if(!is_numeric($priceGroup))
            return false;

        if(mb_strtolower($border) == 'max') {
            $order = 'desc';
        }

        if(!\Bitrix\Main\Loader::includeModule("catalog") || !\Bitrix\Main\Loader::includeModule('currency'))
            return false;

        $section =  \Bitrix\Iblock\SectionTable::getList([
            'filter' => ['ID' => $sectionId],
            'select' => ['LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID', 'ID']
        ])->fetchRaw();

        // Собираем все подразделы
        $subSections = \Bitrix\Iblock\SectionTable::getList([
            'filter' => [
                '>=LEFT_MARGIN' => $section['LEFT_MARGIN'],
                '<=RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
                '=IBLOCK_ID'  => $section['IBLOCK_ID'],
            ],
            'select' => ['ID']
        ]);

        while ($section = $subSections->fetch()) {
            $arSectionsID[] = $section['ID'];
        }

        $elementSection = new \Bitrix\Main\Entity\Query('\Bitrix\Iblock\SectionElementTable');
        $elementSection->addSelect('IBLOCK_ELEMENT_ID')->setFilter(['=IBLOCK_SECTION_ID' => $arSectionsID])->registerRuntimeField(
            'SECTION',
            [
                'data_type' => '\Bitrix\Iblock\SectionTable',
                'reference' => [
                    '=this.IBLOCK_SECTION_ID' => 'ref.ID',
                ],
                'join_type' => 'inner'
            ]
        );

        // Выполняем запрос
        $resElementsID = \Bitrix\Main\Application::getConnection()->query($elementSection->getQuery());
        while ($elementsID = $resElementsID->fetch()) {
            $arElementsID[] = $elementsID['IBLOCK_ELEMENT_ID'];
        }

        $arItem = \Bitrix\Iblock\ElementTable::getList(
            [
                'filter' => ['=ID' => $arElementsID],
                'order' =>  ['PriceTable.PRICE_SCALE' => $order],
                'select' => [
                    'PriceTable.PRICE_SCALE', // Сумма конвертируется в базовую валюту
                ],
                'limit' => 1,
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PriceTable',
                        \Bitrix\Catalog\PriceTable::class,
                        ['=this.ID' => 'ref.PRODUCT_ID', $priceGroup => 'ref.CATALOG_GROUP_ID'],
                        ['join_type' => 'RIGHT']
                    )
                ]
            ]
        )->fetchRaw();

        return current($arItem);
    }

    public function getPriceCodes() {
        $result = [];

        preg_match_all($this->template, $this->condition->getMeta('TEMPLATE_NEW_URL'), $match);
        $result = array_merge($result, $match[3]);
        $this->collectBorders($match[3], $match[1]);

        preg_match_all($this->template, $this->condition->RULE, $match);
//        $result = array_merge($result, $match[2]);
        $result = array_merge($result, $match[3]);
//        $this->collectBorders($match[2], $match[1]);
        $this->collectBorders($match[3], $match[1]);

        preg_match_all($this->template, $this->condition->TAG, $match);
        $result = array_merge($result, $match[3]);
        $this->collectBorders($match[3], $match[1]);

        return array_unique($result);
    }

    private function collectBorders(&$priceCodes, &$priceBorders) {
        foreach ($priceCodes as $index => $priceCode) {
            if(!isset($this->priceBorders[$priceCode])) {
                $this->priceBorders[$priceCode] = [$priceBorders[$index]];
            } else {
                if(!in_array($priceBorders[$index], $this->priceBorders))
                    $this->priceBorders[$priceCode][] = $priceBorders[$index];
            }
        }
    }

    public function  getData() {
        return $this->data;
    }

    public function fillPriceValues(PropertySetCollection $propertySetCollection, $sectionId) {
//        $emptyPriceCodes = [];

        foreach($propertySetCollection as $propertySet) {
            foreach ($propertySet as $propertySetEntity) {
                if($propertySetEntity->isEmptyValue() && $propertySetEntity->isPrice()) {
                    $priceBorder = $this->getPriceBorder($propertySetEntity);

//                    if(!isset($this->data[$priceBorder[0]]['DATA'][mb_strtoupper($priceBorder[1])])) {
                        $price = $this->getBorderPriceBySection($sectionId, $this->data[$priceBorder[0]]['ID'], $priceBorder[1]);
                        $price = $this->formatPrice($price);
                        $price = str_ireplace('&nbsp;', '', $price);
                        $this->data[$priceBorder[0]]['DATA'][mb_strtoupper($priceBorder[1])] = $price;
//                    }

                    $propertySetEntity->setValue($this->data[$priceBorder[0]]['DATA'][mb_strtoupper($priceBorder[1])]);
                }
            }
        }

//        if(count($emptyPriceCodes) > 0) {
//            $emptyPriceCodes = array_unique($emptyPriceCodes);
//
//            foreach($emptyPriceCodes as $priceBorder) {
//
//
//
//            }
//        }
    }

    public function formatPrice(string $price) {
        if(\Bitrix\Main\Loader::includeModule("currency")) {
            $price = \CCurrencyRates::ConvertCurrency($price, $this->currency, $this->convertCurrencyId);
            $price = \CCurrencyLang::CurrencyFormat($price, $this->currency, false);
        }

        return $price;
    }

    public function getPriceBorder(PropertySetEntity $propertySetEntity) {
        $result = false;

        if(preg_match($this->template, $propertySetEntity->CLASS_ID, $match)) {
            $result = [
                $match[3],
                $match[1]
            ];
        }

        return $result;
    }
}
?>