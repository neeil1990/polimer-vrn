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
use Darneo\Ozon\Export\Product\Dimension;

class ProductListTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_product_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_product_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_product_list_' . self::$tablePrefix;
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
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_ID')
            ]),

            new Fields\DatetimeField(
                'DATE_CREATED',
                [
                    'default_value' => new Type\DateTime(),
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_DATE_CREATED')
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
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_CREATED_BY')
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
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_DATE_CHANGED')
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
                'default_value' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_TITLE_DEFAULT'),
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_TITLE')
            ]),
            new Fields\IntegerField('IBLOCK_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(IblockTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_IBLOCK_ID')
            ]),
            new Fields\Relations\Reference(
                'IBLOCK',
                IblockTable::class,
                ['=this.IBLOCK_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('PHOTO_MAIN', [
                'required' => false,
                'default_value' => 'CATALOG_DETAIL_PICTURE',
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_PHOTO_MAIN')
            ]),
            new Fields\IntegerField('PHOTO_OTHER', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_PHOTO_OTHER')
            ]),
            new Fields\StringField('ELEMENT_NAME', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_ELEMENT_NAME')
            ]),
            new Fields\IntegerField('VENDOR_CODE', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_VENDOR_CODE')
            ]),
            new Fields\IntegerField('BAR_CODE', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_BAR_CODE')
            ]),
            new Fields\StringField('DOMAIN', [
                'required' => false,
                'default_value' => static function () {
                    return $_SERVER['HTTP_ORIGIN'];
                },
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_DOMAIN')
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
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_TYPE_PRICE_ID')
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
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_SITE_ID')
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
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_IS_DISCOUNT_PRICE')
            ]),
            new Fields\FloatField('PRICE_RATIO', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_PRICE_RATIO')
            ]),
            new Fields\IntegerField('WEIGHT', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_WEIGHT')
            ]),
            new Fields\IntegerField('WIDTH', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_WIDTH')
            ]),
            new Fields\IntegerField('HEIGHT', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_HEIGHT')
            ]),
            new Fields\IntegerField('LENGTH', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_LENGTH')
            ]),
            new Fields\EnumField('DIMENSION_UNIT', [
                'required' => false,
                'values' => [Dimension::DIMENSION_UNIT_MM, Dimension::DIMENSION_UNIT_CM, Dimension::DIMENSION_UNIT_IN],
                'default_value' => static function () {
                    return Dimension::DIMENSION_UNIT_MM;
                },
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_DIMENSION_UNIT')
            ]),
            new Fields\EnumField('WEIGHT_UNIT', [
                'required' => false,
                'values' => [Dimension::WEIGHT_UNIT_G, Dimension::WEIGHT_UNIT_KG, Dimension::WEIGHT_UNIT_LB],
                'default_value' => static function () {
                    return Dimension::WEIGHT_UNIT_G;
                },
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_WEIGHT_UNIT')
            ]),
            new Fields\BooleanField('IS_CRON', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_PRODUCT_LIST_IS_CRON')
            ]),
        ];
    }

    public static function onBeforeUpdate(ORM\Event $event): ORM\EventResult
    {
        return EventHandlers\Orm\Handlers::setChangedDateAndEditor($event);
    }
}
