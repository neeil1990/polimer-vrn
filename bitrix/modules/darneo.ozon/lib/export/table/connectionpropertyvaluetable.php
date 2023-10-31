<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Darneo\Ozon\Import\Table\PropertyListTable;
use Darneo\Ozon\Import\Table\PropertyValueTable;

class ConnectionPropertyValueTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_export_product_connection_property_value';
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            new Fields\IntegerField('IBLOCK_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(IblockTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
            ]),
            new Fields\StringField('ATTRIBUTE_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyListTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
            ]),
            new Fields\Relations\Reference(
                'ATTRIBUTE',
                PropertyListTable::class,
                ['=this.ATTRIBUTE_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('ATTRIBUTE_VALUE_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyValueTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
            ]),
            new Fields\Relations\Reference(
                'ATTRIBUTE_VALUE',
                PropertyValueTable::class,
                ['=this.ATTRIBUTE_VALUE_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('PROPERTY_ID', [
                'validation' => static function () {
                    return [
                        function ($value) {
                            if ($value) {
                                new Validators\ForeignValidator(PropertyListTable::getEntity()->getField('ID'));
                            }
                            return true;
                        }
                    ];
                },
                'required' => false,
            ]),
            new Fields\Relations\Reference(
                'PROPERTY',
                PropertyListTable::class,
                ['=this.PROPERTY_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\IntegerField('PROPERTY_ENUM_ID', [
                'validation' => static function () {
                    return [
                        function ($value) {
                            if ($value) {
                                new Validators\ForeignValidator(PropertyEnumerationTable::getEntity()->getField('ID'));
                            }
                            return true;
                        }
                    ];
                },
                'required' => false,
            ]),
        ];
    }
}
