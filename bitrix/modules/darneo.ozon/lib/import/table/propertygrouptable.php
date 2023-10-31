<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class PropertyGroupTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_data_property_group';
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', ['primary' => true]),
            new Fields\StringField('NAME', []),
        ];
    }

    public static function onBeforeAdd(ORM\Event $event): void
    {
        $elementId = $event->getParameter('fields')['ID'];
        if (self::getById($elementId)->fetchAll()) {
            self::delete($elementId);
        }
    }
}
