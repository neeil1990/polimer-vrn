<?php

namespace Darneo\Ozon;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;

class Configuration
{
    public const MODULE_NAME = 'darneo.ozon';
    public const NAMESPACE_FOR_ENTITIES = '\Darneo\Ozon';
    private array $data = [
        'entitiesDataClasses' => [
            // ORDER
            'oFboList' => self::NAMESPACE_FOR_ENTITIES . '\Order\Table\FboListTable',
            'oFbsList' => self::NAMESPACE_FOR_ENTITIES . '\Order\Table\FbsListTable',
            // IMPORT
            'iTree' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\TreeTable',
            'iStock' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\StockTable',
            'iPropertyGroup' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\PropertyGroupTable',
            'iPropertyList' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\PropertyListTable',
            'iPropertyValue' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\PropertyValueTable',
            'iConnectionPropCategory' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\ConnectionPropCategoryTable',
            'iConnectionPropValue' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\ConnectionPropValueTable',
            'iConnectionOfferProduct' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\ConnectionOfferProductTable',
            'iProductList' => self::NAMESPACE_FOR_ENTITIES . '\Import\Table\ProductListTable',
            // EXPORT
            'eProductList' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ProductListTable',
            'eProductFilter' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ProductFilterTable',
            'eProductLog' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ProductLogTable',
            'eProductCron' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ProductCronTable',
            'eProductTmp' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ProductTmpTable',
            'ePriceList' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\PriceListTable',
            'ePriceFilter' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\PriceFilterTable',
            'ePriceLog' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\PriceLogTable',
            'ePriceCron' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\PriceCronTable',
            'ePriceTmp' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\PriceTmpTable',
            'eStockList' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\StockListTable',
            'eStockFilter' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\StockFilterTable',
            'eStockLog' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\StockLogTable',
            'eStockCron' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\StockCronTable',
            'eStockTmp' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\StockTmpTable',
            'eConnectionSectionTree' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ConnectionSectionTreeTable',
            'eConnectionCategoryProperty' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ConnectionCategoryPropertyTable',
            'eConnectionPropertyValue' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ConnectionPropertyValueTable',
            'eConnectionPropertyRatio' => self::NAMESPACE_FOR_ENTITIES . '\Export\Table\ConnectionPropertyRatioTable',
            // MAIN
            'mClientKey' => self::NAMESPACE_FOR_ENTITIES . '\Main\Table\ClientKeyTable',
            'mTreeDisable' => self::NAMESPACE_FOR_ENTITIES . '\Main\Table\TreeDisableTable',
            'mSettingsTable' => self::NAMESPACE_FOR_ENTITIES . '\Main\Table\SettingsTable',
            'mSettingsCronTable' => self::NAMESPACE_FOR_ENTITIES . '\Main\Table\SettingsCronTable',
            'mAccessTable' => self::NAMESPACE_FOR_ENTITIES . '\Main\Table\AccessTable',
            // STAT
            'sSaleTable' => self::NAMESPACE_FOR_ENTITIES . '\Analytics\Table\SaleTable'
        ],
    ];

    public function reInstallTables(): void
    {
        $connection = Application::getConnection();
        $entitiesDataClasses = $this->get('entitiesDataClasses');
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            if ($connection->isTableExists($entityDataClass::getTableName())) {
                $connection->dropTable($entityDataClass::getTableName());
            }
            $entityDataClass::getEntity()->createDbTable();
        }
    }

    public function get($key)
    {
        return $this->data[$key] ?? false;
    }

    public function deleteTableAll(): void
    {
        global $DB;
        $connection = Application::getConnection();
        $strSql = "SHOW TABLES LIKE 'darneo_ozon_%'";
        $errMess = '';
        $res = $DB->Query($strSql, false, $errMess . __LINE__);
        while ($row = $res->Fetch()) {
            foreach ($row as $tableName) {
                $connection->dropTable($tableName);
            }
        }
    }

    public function newInstallTables(): void
    {
        $connection = Application::getConnection();
        $entitiesDataClasses = $this->get('entitiesDataClasses');
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            $tableName = $entityDataClass::getTableName();
            if (!$connection->isTableExists($tableName)) {
                $entityDataClass::getEntity()->createDbTable();
            }
        }
    }
}
