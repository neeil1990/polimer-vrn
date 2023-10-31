<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class ProductListTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_product_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_product_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_product_list_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', ['primary' => true]),
            new Fields\StringField('OFFER_ID', ['required' => true]),
            new Fields\StringField('NAME', ['required' => true]),
            new Fields\StringField('STATUS_CODE', ['required' => false]),
            new Fields\StringField('STATUS_NAME', ['required' => false]),
            new Fields\IntegerField('STOCK_FBS', ['required' => false, 'default_value' => 0]),
            new Fields\IntegerField('STOCK_FBS_RESERVED', ['required' => false, 'default_value' => 0]),
            new Fields\IntegerField('STOCK_FBO', ['required' => false, 'default_value' => 0]),
            new Fields\IntegerField('STOCK_FBO_RESERVED', ['required' => false, 'default_value' => 0]),
            new Fields\StringField('CATEGORY_ID', ['required' => false]),
            new Fields\Relations\Reference(
                'CATEGORY',
                TreeTable::class,
                ['=this.CATEGORY_ID' => 'ref.CATEGORY_ID'],
                ['join_type' => 'left']
            ),
            new Fields\BooleanField('IS_ERROR', ['required' => false]),
            new Fields\TextField('JSON', [
                'serialized' => true,
                'required' => true,
                'title' => 'json'
            ]),
        ];
    }

    public static function onBeforeAdd(ORM\Event $event): void
    {
        $elementId = $event->getParameter('fields')['ID'];
        $res = self::getById($elementId);
        if ($row = $res->fetch()) {
            self::delete($row['ID']);
        }
    }
}
