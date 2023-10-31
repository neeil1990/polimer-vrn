<?php

namespace Darneo\Ozon\EventHandlers\Orm;

use Bitrix\Main\Type;
use CUser;

class SetValue extends Base
{
    /**
     * $fieldName - название поля в котором хранится дата изменения.
     * Устанавливает дату изменения записи таблицы.
     */
    public function changedDate($fieldName): void
    {
        $field = [
            $fieldName => new Type\DateTime()
        ];
        $this->modifiedFields = array_merge($this->modifiedFields, $field);
    }

    /**
     * $fieldName - название поля ID пользователя
     * Проставляет ID пользователя, который изменил таблицу.
     */
    public function editor($fieldName): void
    {
        $userId = (new CUser())->GetID();
        $field = [
            $fieldName => $userId
        ];

        $this->modifiedFields = array_merge($this->modifiedFields, $field);
    }
}
