<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class PropertyListTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_data_property_list';
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', ['primary' => true]),
            new Fields\StringField('NAME', []),
            new Fields\StringField('TYPE', []),
            new Fields\StringField('DICTIONARY_ID', []),
            new Fields\TextField('DESCRIPTION', []),
            new Fields\BooleanField('IS_COLLECTION', []),
            new Fields\BooleanField('IS_REQUIRED', []),
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
