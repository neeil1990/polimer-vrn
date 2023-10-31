<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Darneo\Ozon\Import\Table\PropertyListTable;

class ConnectionPropertyRatioTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_export_product_connection_property_ratio';
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
            new Fields\FloatField('RATIO', [
                'required' => false,
            ]),
        ];
    }
}
