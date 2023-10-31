<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Catalog\StoreTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\Type;
use Bitrix\Main\UserTable;
use CUser;
use Darneo\Ozon\EventHandlers;
use Darneo\Ozon\Import\Table\StockTable;

class StockListTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_stock_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_stock_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_stock_list_' . self::$tablePrefix;
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
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_ID')
            ]),

            new Fields\DatetimeField(
                'DATE_CREATED',
                [
                    'default_value' => new Type\DateTime(),
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_DATE_CREATED')
                ]
            ),
            new Fields\IntegerField(
                'CREATED_BY',
                [
                    'default_value' => static function () {
                        return (new CUser())->GetID();
                    },
                    'validation' => static function () {
                        return [
                            new Validators\ForeignValidator(UserTable::getEntity()->getField('ID'))
                        ];
                    },
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_CREATED_BY')
                ]
            ),
            new Fields\Relations\Reference(
                'CREATOR',
                UserTable::class,
                ['=this.CREATED_BY' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\DatetimeField(
                'DATE_CHANGED',
                [
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_DATE_CHANGED')
                ]
            ),
            new Fields\IntegerField(
                'CHANGED_BY',
                [
                    'validation' => static function () {
                        return [
                            new Validators\ForeignValidator(UserTable::getEntity()->getField('ID'))
                        ];
                    },
                ]
            ),
            new Fields\StringField('TITLE', [
                'required' => false,
                'default_value' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_TITLE_DEFAULT'),
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_TITLE')
            ]),
            new Fields\IntegerField('IBLOCK_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(IblockTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_IBLOCK_ID')
            ]),
            new Fields\Relations\Reference(
                'IBLOCK',
                IblockTable::class,
                ['=this.IBLOCK_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\IntegerField('VENDOR_CODE', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_VENDOR_CODE')
            ]),
            new Fields\StringField('OZON_STOCK_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(StockTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_OZON_STOCK_ID')
            ]),
            new Fields\Relations\Reference(
                'OZON_STOCK',
                StockTable::class,
                ['=this.OZON_STOCK_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\TextField('STORE_ID', [
                'serialized' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_STORE_ID')
            ]),
            new Fields\IntegerField('MAX_COUNT_STORE', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_MAX_COUNT_STORE')
            ]),
            new Fields\IntegerField('MIN_COUNT_STORE', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_MIN_COUNT_STORE')
            ]),
            new Fields\BooleanField('IS_CRON', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_IS_CRON')
            ]),
            new Fields\BooleanField('DISABLE_OPTIMISATION', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_LIST_DISABLE_OPTIMISATION')
            ]),
        ];
    }

    public static function onBeforeUpdate(ORM\Event $event): ORM\EventResult
    {
        return EventHandlers\Orm\Handlers::setChangedDateAndEditor($event);
    }
}
