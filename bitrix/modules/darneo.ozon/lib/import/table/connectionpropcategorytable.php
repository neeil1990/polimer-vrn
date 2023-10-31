<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;

class ConnectionPropCategoryTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_data_connection_prop_category';
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            new Fields\StringField('CATEGORY_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(TreeTable::getEntity()->getField('CATEGORY_ID'))
                    ];
                }
            ]),
            new Fields\StringField('PROPERTY_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyListTable::getEntity()->getField('ID'))
                    ];
                }
            ]),
            new Fields\StringField('GROUP_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyGroupTable::getEntity()->getField('ID'))
                    ];
                }
            ]),
            new Fields\Relations\Reference(
                'PROPERTY',
                PropertyListTable::class,
                ['=this.PROPERTY_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
        ];
    }
}
