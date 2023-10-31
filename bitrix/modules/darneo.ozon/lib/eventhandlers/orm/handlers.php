<?php

namespace Darneo\Ozon\EventHandlers\Orm;

use Bitrix\Main\ORM;

class Handlers
{
    public static function setChangedDateAndEditor(ORM\Event $event): ORM\EventResult
    {
        $setValue = new SetValue($event);

        $setValue->editor('CHANGED_BY');
        $setValue->changedDate('DATE_CHANGED');

        return $setValue->getResult();
    }

    public static function setDefaultValue(ORM\Event $event): ORM\EventResult
    {
        $defaultValue = new DefaultValue($event);

        $defaultValue->set('DEFAULT');
        $defaultValue->set('FINAL');

        return $defaultValue->getResult();
    }
}
