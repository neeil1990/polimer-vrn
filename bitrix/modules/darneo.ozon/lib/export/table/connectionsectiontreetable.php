<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Darneo\Ozon\Import\Table\TreeTable;

class ConnectionSectionTreeTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_export_product_connection_section_tree';
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
            new Fields\StringField('CATEGORY_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(TreeTable::getEntity()->getField('CATEGORY_ID'))
                    ];
                },
                'required' => true,
            ]),
            new Fields\IntegerField('SECTION_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(SectionTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => false,
            ]),
            new Fields\Relations\Reference(
                'CATEGORY',
                TreeTable::class,
                ['=this.CATEGORY_ID' => 'ref.CATEGORY_ID'],
                ['join_type' => 'left']
            ),
            new Fields\Relations\Reference(
                'SECTION',
                SectionTable::class,
                ['=this.SECTION_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
        ];
    }
}
