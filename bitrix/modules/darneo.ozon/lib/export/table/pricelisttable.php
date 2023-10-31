<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Catalog\GroupTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Type;
use Bitrix\Main\UserTable;
use CUser;
use Darneo\Ozon\EventHandlers;

class PriceListTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_price_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_price_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_price_list_' . self::$tablePrefix;
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
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_ID')
            ]),

            new Fields\DatetimeField(
                'DATE_CREATED',
                [
                    'default_value' => new Type\DateTime(),
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_DATE_CREATED')
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
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_CREATED_BY')
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
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_DATE_CHANGED')
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
                'default_value' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_TITLE_DEFAULT'),
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_TITLE')
            ]),
            new Fields\IntegerField('IBLOCK_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(IblockTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_IBLOCK_ID')
            ]),
            new Fields\Relations\Reference(
                'IBLOCK',
                IblockTable::class,
                ['=this.IBLOCK_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\IntegerField('VENDOR_CODE', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_VENDOR_CODE')
            ]),
            new Fields\IntegerField('TYPE_PRICE_ID', [
                'validation' => static function () {
                    return [
                        function ($value) {
                            if ($value) {
                                new Validators\ForeignValidator(GroupTable::getEntity()->getField('ID'));
                            }
                            return true;
                        }
                    ];
                },
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_TYPE_PRICE_ID')
            ]),
            new Fields\Relations\Reference(
                'TYPE_PRICE',
                GroupTable::class,
                ['=this.TYPE_PRICE_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('SITE_ID', [
                'default_value' => SITE_ID,
                'validation' => static function () {
                    return [
                        function ($value) {
                            if ($value) {
                                new Validators\ForeignValidator(SiteTable::getEntity()->getField('LID'));
                            }
                            return true;
                        }
                    ];
                },
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_SITE_ID')
            ]),
            new Fields\Relations\Reference(
                'SITE',
                SiteTable::class,
                ['=this.SITE_ID' => 'ref.LID'],
                ['join_type' => 'left']
            ),
            new Fields\BooleanField('IS_DISCOUNT_PRICE', [
                'default_value' => true,
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_IS_DISCOUNT_PRICE')
            ]),
            new Fields\FloatField('PRICE_RATIO', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_PRICE_RATIO')
            ]),
            new Fields\BooleanField('IS_CRON', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRICE_LIST_IS_CRON')
            ]),
        ];
    }

    public static function onBeforeUpdate(ORM\Event $event): ORM\EventResult
    {
        return EventHandlers\Orm\Handlers::setChangedDateAndEditor($event);
    }
}
