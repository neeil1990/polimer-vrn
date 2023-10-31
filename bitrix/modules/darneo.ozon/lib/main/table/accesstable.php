<?php

namespace Darneo\Ozon\Main\Table;

use Bitrix\Main\GroupTable;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\EventHandlers;

class AccessTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_main_settings_access';
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('GROUP_ID', [
                'primary' => true
            ]),
            new Fields\Relations\Reference(
                'GROUP',
                GroupTable::class,
                ['=this.GROUP_ID' => 'ref.ID'],
                ['join_type' => 'left']
            )
        ];
    }
}
