<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\Type;
use Darneo\Ozon\EventHandlers;

class ProductLogTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_product_log_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_product_log';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_product_log_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_ID')
            ]),

            new Fields\DatetimeField(
                'DATE_CREATED',
                [
                    'default_value' => new Type\DateTime(),
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_DATE_CREATED')
                ]
            ),
            new Fields\IntegerField('PRODUCT_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(ProductListTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_PRODUCT_ID')
            ]),
            new Fields\IntegerField('ELEMENT_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(ElementTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_ELEMENT_ID')
            ]),
            new Fields\Relations\Reference(
                'ELEMENT',
                ElementTable::class,
                ['=this.ELEMENT_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('OFFER_ID', [
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_OFFER_ID')
            ]),
            new Fields\TextField('SEND_JSON', [
                'serialized' => true,
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_SEND_JSON')
            ]),
            new Fields\TextField('ANSWER', [
                'serialized' => true,
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_ANSWER')
            ]),
            new Fields\TextField('ANSWER_JSON', [
                'serialized' => true,
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_ANSWER_JSON')
            ]),
            new Fields\BooleanField('IS_FINISH', [
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_IS_FINISH')
            ]),
            new Fields\BooleanField('IS_ERROR', [
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LOG_IS_ERROR')
            ]),
        ];
    }
}
