<?php

namespace Darneo\Ozon\Order\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class FboListTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_order_fbo_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_order_fbo_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_order_fbo_list_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', ['primary' => true]),
            new Fields\DatetimeField('DATE_CREATED'),
            new Fields\DatetimeField('DATE_UPDATE'),
            new Fields\StringField('ORDER_ID'),
            new Fields\StringField('ORDER_NUMBER'),
            new Fields\StringField('POSTING_NUMBER'),
            new Fields\StringField('STATUS'),
            new Fields\IntegerField('CANCEL_REASON_ID'),
            new Fields\TextField('PRODUCTS', ['serialized' => true]),
            new Fields\TextField('ANALYTICS', ['serialized' => true]),
            new Fields\TextField('FINANCIAL', ['serialized' => true]),
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
