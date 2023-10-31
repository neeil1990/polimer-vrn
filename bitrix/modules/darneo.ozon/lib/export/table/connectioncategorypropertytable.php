<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Darneo\Ozon\Import\Table\PropertyListTable;

class ConnectionCategoryPropertyTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_export_product_connection_category_property';
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            new Fields\IntegerField('CONNECTION_SECTION_TREE_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(ConnectionSectionTreeTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
            ]),
            new Fields\Relations\Reference(
                'CONNECTION_SECTION_TREE',
                ConnectionSectionTreeTable::class,
                ['=this.CONNECTION_SECTION_TREE_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('ATTRIBUTE_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyListTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
            ]),
            new Fields\StringField('PROPERTY_TYPE', [
                'required' => false,
            ]),
            new Fields\StringField('PROPERTY_VALUE', [
                'required' => false,
            ]),
            new Fields\StringField('VALUE', [
                'required' => false,
            ]),
        ];
    }
}
