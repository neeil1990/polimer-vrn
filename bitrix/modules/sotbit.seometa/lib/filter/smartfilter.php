<?
namespace Sotbit\Seometa\Filter;

use Bitrix\Highloadblock as HL;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Sotbit\Seometa\Condition\Condition;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Link\TagWriter;
use Sotbit\Seometa\Price\PriceManager;
use Sotbit\Seometa\Property\PropertyCollection;
use Sotbit\Seometa\Property\PropertyManager;

class SmartFilter
{
    private $conditionProperties = [];
    private $condition;
    private $propertyManager;
    private $priceManager;
    private static $arItemCache = [];
    private static $hlblockCache = [];
    private static $directoryMap = [];
    private static $hlblockClassNameCache = [];

    public function __construct(
        int $conditionId
    ) {
        $this->condition = new Condition($conditionId);
        $this->propertyManager = new PropertyManager($this->condition);
        $this->priceManager = PriceManager::getInstance();
        $this->priceManager->setConditionPrices($this->condition);
        $this->priceManager->setCurrencies();
    }

    public function testMethod(
        $conditionId
    ) {
        $writer = TagWriter::getInstance('', '');
        $linker = Linker::getInstance();
        $linker->generate($writer, $conditionId);

        return $writer->getData();
    }

    public function getPropertyCollection(
    ) {
        $this->conditionProperties = $this->propertyManager->getProperties();
        $result = $this->getIBlockItems($this->propertyManager->getIblockId());
        if ($this->propertyManager->getSkuIblockId() > 0) {
            $result = array_merge($result, $this->getIBlockItems($this->propertyManager->getSkuIblockId()));
        }

        $result = array_column($result, NULL, 'ID');
        PropertyCollection::getInstance()->setData($result);
        PropertyCollection::getInstance()->setPrices($this->priceManager);

        return PropertyCollection::getInstance();
    }

    private function getIBlockItems(
        $IBLOCK_ID
    ) {
        $arProperties = $this->getIblockProperies($IBLOCK_ID);
        foreach ($arProperties as &$arProperty) {
            $arProperty["~NAME"] = $arProperty["NAME"];
            $arProperty["NAME"] = htmlspecialcharsEx($arProperty["NAME"]);
            $arProperty["VALUES"] = [];

            $userTypeSettings = unserialize($arProperty['USER_TYPE_SETTINGS']);
            if ($userTypeSettings) {
                $arProperty['USER_TYPE_SETTINGS'] = $userTypeSettings;
            }
        }

        return $arProperties;
    }

    private function getIblockProperies(
        $IBLOCK_ID
    ) {
        if (!Loader::includeModule('iblock') || empty($this->conditionProperties[$IBLOCK_ID])) {
            return [];
        }

        $result = PropertyTable::getList([
            'filter' => [
                'IBLOCK_ID' => $IBLOCK_ID,
                'ID' => $this->conditionProperties[$IBLOCK_ID]
            ],
            'select' => [
                'ID',
                'IBLOCK_ID',
                'CODE',
                'NAME',
                'PROPERTY_TYPE',
                'USER_TYPE',
                'USER_TYPE_SETTINGS',
                'SORT'
            ],
            'order' => [
                "SORT" => "ASC",
                'ID' => 'ASC'
            ],
            'cache' => ['ttl' => 3600],
        ])->fetchAll();

        return array_column($result, NULL, 'ID');
    }

    public function getCondition(
    ) {
        return $this->condition;
    }

    public function getPropertyManager(
    ) {
        return $this->propertyManager;
    }

    public function getPriceManager(
    ) {
        return $this->priceManager;
    }

    public static function GetExtendedValue(
        $arProperty,
        $value
    ) {
        if (
            !isset($value['VALUE'])
            || is_array($value['VALUE']) && count($value['VALUE']) == 0
            || empty($arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'])
        ) {
            return false;
        }

        $tableName = $arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'];
        if (!isset(self::$arItemCache[$tableName])) {
            self::$arItemCache[$tableName] = [];
        }

        if (is_array($value['VALUE']) || !isset(self::$arItemCache[$tableName][$value['VALUE']])) {
            $data = self::getEntityFieldsByFilter(
                $arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'],
                [
                    'select' => ['UF_XML_ID', 'UF_NAME', 'ID'],
                    'filter' => ['=UF_XML_ID' => $value['VALUE']]
                ]
            );

            if (!empty($data)) {
                foreach ($data as $item) {
                    if (isset($item['UF_XML_ID'])) {
                        $item['VALUE'] = $item['UF_NAME'];
                        if (isset($item['UF_FILE'])) {
                            $item['FILE_ID'] = $item['UF_FILE'];
                        }
                        self::$arItemCache[$tableName][$item['UF_XML_ID']] = $item;
                    }
                }
            }
        }

        if (is_array($value['VALUE'])) {
            $result = [];
            foreach ($value['VALUE'] as $prop) {
                $result[$prop] = false;
                if (isset(self::$arItemCache[$tableName][$prop])) {
                    $result[$prop] = self::$arItemCache[$tableName][$prop];
                }
            }

            return $result;
        } elseif (isset(self::$arItemCache[$tableName][$value['VALUE']])) {
            return self::$arItemCache[$tableName][$value['VALUE']];
        }

        return false;
    }

    private static function getEntityFieldsByFilter(
        $tableName,
        $listDescr = []
    ) {
        $arResult = [];
        $tableName = (string)$tableName;
        if (!is_array($listDescr)) {
            $listDescr = [];
        }

        if (!empty($tableName)) {
            if (!isset(self::$hlblockCache[$tableName])) {
                self::$hlblockCache[$tableName] = HL\HighloadBlockTable::getList(
                    [
                        'select' => ['TABLE_NAME', 'NAME', 'ID'],
                        'filter' => ['=TABLE_NAME' => $tableName]
                    ]
                )->fetch();
            }

            if (!empty(self::$hlblockCache[$tableName])) {
                if (!isset(self::$directoryMap[$tableName])) {
                    $entity = HL\HighloadBlockTable::compileEntity(self::$hlblockCache[$tableName]);
                    self::$hlblockClassNameCache[$tableName] = $entity->getDataClass();
                    self::$directoryMap[$tableName] = $entity->getFields();
                    unset($entity);
                }

                if (!isset(self::$directoryMap[$tableName]['UF_XML_ID'])) {
                    return $arResult;
                }

                $entityDataClass = self::$hlblockClassNameCache[$tableName];
                $nameExist = isset(self::$directoryMap[$tableName]['UF_NAME']);
                if (!$nameExist) {
                    $listDescr['select'] = ['UF_XML_ID','ID'];
                }

                if (isset(self::$directoryMap[$tableName]['UF_FILE'])) {
                    $listDescr['select'][] = 'UF_FILE';
                }

                $sortExist = isset(self::$directoryMap[$tableName]['UF_SORT']);
                $listDescr['order'] = [];
                if ($sortExist) {
                    $listDescr['order']['UF_SORT'] = 'ASC';
                    $listDescr['select'][] = 'UF_SORT';
                }

                $listDescr['order']['ID'] = 'ASC';
                $rsData = $entityDataClass::getList($listDescr);
                while ($arData = $rsData->fetch()) {
                    if (!$nameExist) {
                        $arData['UF_NAME'] = $arData['UF_XML_ID'];
                    }

                    $arData['SORT'] = $sortExist ? $arData['UF_SORT'] : $arData['ID'];
                    $arResult[] = $arData;
                }
                unset($arData, $rsData);
            }
        }

        return $arResult;
    }
}