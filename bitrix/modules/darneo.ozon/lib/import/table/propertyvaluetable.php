<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class PropertyValueTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_data_property_value';
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', ['primary' => true]),
            new Fields\StringField('VALUE', []),
            new Fields\StringField('INFO', []),
            new Fields\StringField('PICTURE', []),
        ];
    }

    public static function onBeforeAdd(ORM\Event $event): void
    {
        $elementId = $event->getParameter('fields')['ID'];
        $res = self::getById($elementId);
        if ($row = $res->fetch()) {
            self::delete($row['ID']);
        }
    }
}
