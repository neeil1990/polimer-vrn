<?php

namespace Darneo\Ozon\Order\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\Main\Table\TreeDisableTable;

class FbsListTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_order_fbs_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_order_fbs_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_order_fbs_list_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', ['primary' => true]),
            new Fields\StringField('POSTING_NUMBER'),
            new Fields\StringField('ORDER_ID'),
            new Fields\StringField('ORDER_NUMBER'),
            new Fields\StringField('STATUS'),
            new Fields\TextField('DELIVERY_METHOD', ['serialized' => true]),
            new Fields\StringField('WAREHOUSE_ID'),
            new Fields\Relations\Reference(
                'WAREHOUSE',
                TreeDisableTable::class,
                ['=this.WAREHOUSE_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('TRACKING_NUMBER'),
            new Fields\StringField('TPL_INTEGRATION_TYPE'),
            new Fields\DatetimeField('IN_PROCESS_AT'),
            new Fields\DatetimeField('SHIPMENT_DATE'),
            new Fields\DatetimeField('DELIVERY_DATE'),
            new Fields\TextField('CANCELLATION', ['serialized' => true]),
            new Fields\TextField('CUSTOMER', ['serialized' => true]),
            new Fields\TextField('PRODUCTS', ['serialized' => true]),
            new Fields\TextField('ADDRESSEE', ['serialized' => true]),
            new Fields\TextField('BARCODES', ['serialized' => true]),
            new Fields\TextField('ANALYTICS_DATA', ['serialized' => true]),
            new Fields\TextField('FINANCIAL_DATA', ['serialized' => true]),
            new Fields\BooleanField('IS_EXPRESS'),
            new Fields\TextField('REQUIREMENTS', ['serialized' => true]),
        ];
    }
}
