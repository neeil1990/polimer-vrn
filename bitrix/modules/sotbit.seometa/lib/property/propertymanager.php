<?
namespace Sotbit\Seometa\Property;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\Condition\Condition;

class PropertyManager
{
    private $condition = false;
    private $IBLOCK_ID;
    private $SKU_IBLOCK_ID;
    private $SKU_PROPERTY_ID;
    private $data = [];
    private $cache = [];

    public function __construct(
        Condition $condition
    ) {
        $this->condition = $condition;
        $this->IBLOCK_ID = $condition->getIblockId();

        if (Loader::includeModule('catalog') && class_exists('CCatalogSku')) {
            $arCatalog = \CCatalogSKU::GetInfoByProductIBlock($this->IBLOCK_ID);

            if (!empty($arCatalog)) {
                $this->SKU_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
                $this->SKU_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
            }
        }
    }

    public function getIblockId(
    ) {
        return $this->IBLOCK_ID;
    }

    public function getSkuIblockId(
    ) {
        return $this->SKU_IBLOCK_ID;
    }

    public function getSkuPropertyId(
    ) {
        return $this->SKU_PROPERTY_ID;
    }

    public function getProperties(
    ) {
        if (!empty($this->data)) {
            return $this->data;
        }

        $this->data = $this->condition->getRuleProperties();
        $tagProperties = $this->condition->getTagProperties();

        if (!empty($tagProperties['ProductProperty'])) {
            $props = $this->getPropertiesIdByCode($this->IBLOCK_ID, $tagProperties['ProductProperty']);
            if($this->data[$this->IBLOCK_ID]){
                $this->data[$this->IBLOCK_ID] = array_unique(array_merge($this->data[$this->IBLOCK_ID], $props));
            }
        }

        if ($this->SKU_IBLOCK_ID > 0 && !empty($tagProperties['OfferProperty'])) {
            $props = $this->getPropertiesIdByCode($this->SKU_IBLOCK_ID, $tagProperties['OfferProperty']);
            if ($this->data[$this->SKU_IBLOCK_ID]) {
                $this->data[$this->SKU_IBLOCK_ID] = array_unique(array_merge($this->data[$this->SKU_IBLOCK_ID], $props));
            }
        }

        return $this->data;
    }

    private function getPropertiesIdByCode($iblockId, array $propCode) {
        $result = PropertyTable::getList([
            'select' => ['ID'],
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $propCode
            ],
            'cache' => ['ttl' => 3600],
        ])->fetchAll();

        return array_column($result, 'ID');
    }

    public function fillPropertyValues(PropertyCollection $propertyCollection, $sectionId, $siteID)
    {
        $propertyValues = $this->getPropertyValues($sectionId, $siteID);
        foreach ($propertyValues as $arElement) {
            foreach ($propertyCollection as $PID => $property) {
                if (is_array($arElement[$PID])) {
                    foreach ($arElement[$PID] as $value) {
                        $this->fillItemValues($property, $value);
                    }
                } elseif (isset($arElement[$PID]) && $arElement[$PID] !== false) {
                    $this->fillItemValues($property, $arElement[$PID]);
                }
            }
        }

        $this->elaborateHlPropertyValues($propertyCollection);

        return $propertyCollection;
    }

    private function elaborateHlPropertyValues(
        PropertyCollection $propertyCollection
    ) {
        foreach ($propertyCollection as $property) {
            if ($property->isTypeHighload()) {
                $data = $property->getData();
                $property->clearValue();

                foreach ($data['VALUES'] as $propertyValue) {
                    $property->addValueObj($propertyValue, $propertyValue->ID);
                }
            }
        }
    }

    public function getPropertyValues($sectionId, $siteID)
    {
        if (!isset($this->data[$this->IBLOCK_ID])) {
            $this->IBLOCK_ID = $this->condition->getIblockId();
            $this->data[$this->condition->getIblockId()] = [];
        }

        $arElementFilter = [
            'IBLOCK_ID' => $this->IBLOCK_ID,
            'SUBSECTION' => $sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
            'SECTION_SCOPE' => 'IBLOCK',
            'ACTIVE_DATE' => 'Y',
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => 'Y',
        ];

        if(Option::get('sotbit.seometa', 'PRODUCT_AVAILABLE_FOR_COND', 'N', $siteID) === 'Y'){
            $arElementFilter['AVAILABLE'] = 'Y';
        }

        $arElements = [];
        $rsElements = \CIBlockElement::GetPropertyValues(
            $this->IBLOCK_ID,
            $arElementFilter,
            false,
            [
                'ID' => $this->data[$this->IBLOCK_ID]
            ]
        );
        while ($arElement = $rsElements->Fetch()) {
            $arElements[$arElement["IBLOCK_ELEMENT_ID"]] = $arElement;
        }

        if ($this->SKU_IBLOCK_ID && count((array)$this->data[$this->SKU_IBLOCK_ID]) > 0) {
            $arSkuFilter = [
                "IBLOCK_ID" => $this->SKU_IBLOCK_ID,
                "ACTIVE_DATE" => "Y",
                "ACTIVE" => "Y",
                "CHECK_PERMISSIONS" => "Y",
                "=PROPERTY_".$this->SKU_PROPERTY_ID => array_keys($arElements),
            ];

            $propertyIdList = $this->data[$this->IBLOCK_ID];
            if (isset($this->data[$this->SKU_IBLOCK_ID])) {
                $propertyIdList = array_merge($propertyIdList, $this->data[$this->SKU_IBLOCK_ID]);
            }

            $rsElements = \CIBlockElement::GetPropertyValues(
                $this->SKU_IBLOCK_ID,
                $arSkuFilter,
                false,
                [
                    'ID' => array_merge(
                        $this->data[$this->SKU_IBLOCK_ID],
                        [$this->SKU_PROPERTY_ID]
                    )
                ]
            );
            while ($arSku = $rsElements->Fetch()) {
                foreach ($propertyIdList as $PID) {
                    if (isset($arSku[$PID]) && $arSku[$this->SKU_PROPERTY_ID] > 0) {
                        if (is_array($arSku[$PID])) {
                            foreach ($arSku[$PID] as $value) {
                                $arElements[$arSku[$this->SKU_PROPERTY_ID]][$PID][] = $value;
                            }
                        } else {
                            $arElements[$arSku[$this->SKU_PROPERTY_ID]][$PID][] = $arSku[$PID];
                        }
                    }
                }
            }
        }

        return $arElements;
    }

    private function fillItemValues(
        $resultItem,
        $arProperty,
        $flag = null
    ) {
        if (is_array($arProperty)) {
            if (isset($arProperty["PRICE"])) {
                return null;
            }

            $key = $arProperty["VALUE"];
            $PROPERTY_TYPE = $arProperty["PROPERTY_TYPE"];
            $PROPERTY_USER_TYPE = $arProperty["USER_TYPE"];
            $PROPERTY_ID = $arProperty["ID"];
        } else {
            $key = $arProperty;
            $PROPERTY_TYPE = $resultItem->PROPERTY_TYPE;
            $PROPERTY_USER_TYPE = $resultItem->USER_TYPE;
            $PROPERTY_ID = $resultItem->ID;
            $arProperty = $resultItem;
        }

        if (
            ($PROPERTY_TYPE == "E" && $key <= 0)
            || ($PROPERTY_TYPE == "G" && $key <= 0)
            || mb_strlen($key) <= 0
            || $PROPERTY_TYPE == "F"
        ) {
            return null;
        } elseif ($PROPERTY_TYPE == "N") {
            $convertKey = (float)$key;
            if (mb_strlen($key) <= 0) {
                return null;
            }

            if (
                $resultItem->MIN === false
                || !array_key_exists("VALUE", $resultItem->MIN)
                || doubleval($resultItem->MIN["VALUE"]) > $convertKey
            ) {
                $resultItem->setMinVal(preg_replace("/\\.0+\$/", "", $key));
            }

            if (
                $resultItem->MAX === false
                || !array_key_exists("VALUE", $resultItem->MAX)
                || doubleval($resultItem->MAX["VALUE"]) < $convertKey
            ) {
                $resultItem->setMaxVal(preg_replace("/\\.0+\$/", "", $key));
            }
        } elseif($arProperty->DISPLAY_TYPE == "U") {
            $date = mb_substr($key, 0, 10);
            if (!$date) {
                return null;
            }

            $timestamp = MakeTimeStamp($date, "YYYY-MM-DD");
            if (!$timestamp) {
                return null;
            }

            if (
                $resultItem->MIN === false
                || !array_key_exists("VALUE", $resultItem->MIN)
                || $resultItem->MIN["VALUE"] > $timestamp
            ) {
                $resultItem->setMinVal($timestamp);
            }

            if (
                $resultItem->MAX === false
                || !array_key_exists("VALUE", $resultItem->MAX)
                || $resultItem->MAX["VALUE"] < $timestamp
            ) {
                $resultItem->setMaxVal($timestamp);
            }
        }

        $arUserType = [];
        if ($PROPERTY_USER_TYPE != "") {
            $arUserType = \CIBlockProperty::GetUserType($PROPERTY_USER_TYPE);
            if (isset($arUserType["GetExtendedValue"])) {
                $PROPERTY_TYPE = "Ux";
                if ($arProperty->getData()['USER_TYPE'] != 'EList') {
                    $arUserType['GetExtendedValue'][0] = '\\Sotbit\\Seometa\\Filter\\SmartFilter';
                }
            } elseif (isset($arUserType["GetPublicViewHTML"])) {
                $PROPERTY_TYPE = "U";
            }
        }

        if ($PROPERTY_USER_TYPE === "DateTime") {
            $key = call_user_func_array(
                $arUserType["GetPublicViewHTML"],
                [
                    $arProperty->getData(),
                    ["VALUE" => $key],
                    ["MODE" => "SIMPLE_TEXT", "DATETIME_FORMAT" => "SHORT"],
                ]
            );
            $PROPERTY_TYPE = "S";
        }

        $htmlKey = $this->makeHtmlKey($key);
        if (isset($resultItem->VALUES[$htmlKey])) {
            return $htmlKey;
        }

        $file_id = null;

        switch($PROPERTY_TYPE) {
            case "L":
                $enum = \CIBlockPropertyEnum::GetByID($key);
                if (!$enum) {
                    return null;
                }

                $value = $enum["VALUE"];
                $sort = $enum["SORT"];
                $url_id = toLower($enum["XML_ID"]);
                break;
            case "E":
                if (!isset($this->cache[$PROPERTY_TYPE][$key])) {
                    $this->predictIBElementFetch([$key]);
                }

                if (!$this->cache[$PROPERTY_TYPE][$key]) {
                    return null;
                }

                $value = $this->cache[$PROPERTY_TYPE][$key]["NAME"];
                $sort = $this->cache[$PROPERTY_TYPE][$key]["SORT"];
                $url_id = toLower($value);
                if ($this->cache[$PROPERTY_TYPE][$key]["CODE"]) {
                    $url_id = toLower($this->cache[$PROPERTY_TYPE][$key]["CODE"]);
                }
                break;
            case "G":
                if (!isset($this->cache[$PROPERTY_TYPE][$key])) {
                    $this->predictIBSectionFetch([$key]);
                }

                if (!$this->cache[$PROPERTY_TYPE][$key]) {
                    return null;
                }

                $value = $this->cache[$PROPERTY_TYPE][$key]['DEPTH_NAME'];
                $sort = $this->cache[$PROPERTY_TYPE][$key]["LEFT_MARGIN"];
                if ($this->cache[$PROPERTY_TYPE][$key]["CODE"]) {
                    $url_id = toLower($this->cache[$PROPERTY_TYPE][$key]["CODE"]);
                } else {
                    $url_id = toLower($value);
                }
                break;
            case "U":
                if (!isset($this->cache[$PROPERTY_ID])) {
                    $this->cache[$PROPERTY_ID] = [];
                }

                if (!isset($this->cache[$PROPERTY_ID][$key])) {
                    $this->cache[$PROPERTY_ID][$key] = call_user_func_array(
                        $arUserType["GetPublicViewHTML"],
                        [
                            $arProperty->getData(),
                            ["VALUE" => $key],
                            ["MODE" => "SIMPLE_TEXT"],
                        ]
                    );
                }

                $value = $this->cache[$PROPERTY_ID][$key];
                $sort = 0;
                $url_id = toLower($value);
                break;
            case "Ux":
                if (!isset($this->cache[$PROPERTY_ID])) {
                    $this->cache[$PROPERTY_ID] = [];
                }

                if (!isset($this->cache[$PROPERTY_ID][$key])) {
                    $this->cache[$PROPERTY_ID][$key] = call_user_func_array(
                        $arUserType["GetExtendedValue"],
                        [
                            $arProperty->getData(),
                            ["VALUE" => $key],
                        ]
                    );
                }

                if ($this->cache[$PROPERTY_ID][$key]) {
                    $value = $this->cache[$PROPERTY_ID][$key]['VALUE'];
                    $file_id = $this->cache[$PROPERTY_ID][$key]['FILE_ID'];
                    $sort = $this->cache[$PROPERTY_ID][$key]['SORT'] ?? 0;
                    $url_id = toLower($this->cache[$PROPERTY_ID][$key]['UF_XML_ID']);
                } else {
                    return null;
                }
                break;
            default:
                $value = $key;
                $sort = 0;
                $url_id = toLower($value);
                break;
        }

        $keyCrc = $this->makeCrcKey($htmlKey);
        $safeValue = $this->makeSaveValue($value);
        $sort = (int)$sort;

        $filterPropertyID = $this->SAFE_FILTER_NAME.'_'.$PROPERTY_ID;
        $filterPropertyIDKey = $filterPropertyID.'_'.$keyCrc;

        $ar = [
            "CONTROL_ID" => $filterPropertyIDKey,
            "CONTROL_NAME" => $filterPropertyIDKey,
            "CONTROL_NAME_ALT" => $filterPropertyID,
            "HTML_VALUE_ALT" => $keyCrc,
            "HTML_VALUE" => "Y",
            "VALUE" => $safeValue,
            "SORT" => $sort,
            "UPPER" => ToUpper($safeValue),
            "FLAG" => $flag,
        ];

        if($PROPERTY_TYPE === 'Ux') {
            $ar['ID'] = $this->cache[$PROPERTY_ID][$key]['ID'];
        }elseif ($PROPERTY_TYPE === 'N'){
            $ar['TYPE'] = $PROPERTY_TYPE;
        }

        if ($file_id) {
            $ar['FILE'] = \CFile::GetFileArray($file_id);
        }

        if (mb_strlen($url_id)) {
            $utf_id = Encoding::convertEncoding($url_id, LANG_CHARSET, "utf-8");
            $ar['URL_ID'] = rawurlencode(str_replace("/", "-", $utf_id));
        }

        $resultItem->addValueObj(new PropertyValue($ar), $htmlKey);

        return $htmlKey;
    }

    public function predictIBSectionFetch(
        $id = array()
    ) {
        if (!is_array($id) || empty($id)) {
            return;
        }

        $arLinkFilter = [
            "ID" => $id,
            "GLOBAL_ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
        ];

        $link = \CIBlockSection::GetList(
            [],
            $arLinkFilter,
            false,
            [
                "ID",
                "IBLOCK_ID",
                "NAME",
                "LEFT_MARGIN",
                "DEPTH_LEVEL",
                "CODE"
            ]
        );
        while ($sec = $link->Fetch()) {
            $this->cache['G'][$sec['ID']] = $sec;
            $this->cache['G'][$sec['ID']]['DEPTH_NAME'] = str_repeat(".", $sec["DEPTH_LEVEL"]) . $sec["NAME"];
        }

        unset($sec);
        unset($link);
    }

    public function predictIBElementFetch(
        $id = []
    ) {
        if (!is_array($id) || empty($id)) {
            return;
        }

        $linkFilter = [
            "ID" => $id,
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
            "CHECK_PERMISSIONS" => "Y",
        ];

        $link = \CIBlockElement::GetList(
            [],
            $linkFilter,
            false,
            false,
            [
                "ID",
                "IBLOCK_ID",
                "NAME",
                "SORT",
                "CODE"
            ]
        );
        while ($el = $link->Fetch()) {
            $this->cache['E'][$el['ID']] = $el;
        }

        unset($el);
        unset($link);
    }

    public function predictHlFetch(
        $userType,
        $valueIDs
    ) {
        $values = call_user_func_array(
            $userType['GetExtendedValue'],
            [
                $userType,
                ["VALUE" => $valueIDs],
            ]
        );

        foreach ($values as $key => $value) {
            $this->cache[$userType['PID']][$key] = $value;
        }
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public static function makeHtmlKey(
        $key
    ) {
        return htmlspecialcharsbx($key);
    }

    /**
     * @param $htmlKey
     * @return float|int
     */
    public static function makeCrcKey(
        $htmlKey
    ) {
        return abs(crc32($htmlKey));
    }

    /**
     * @param $value
     * @return string|string[]
     */
    public function makeSaveValue(
        $value
    ) {
        return htmlspecialcharsex($value);
    }
}
?>
