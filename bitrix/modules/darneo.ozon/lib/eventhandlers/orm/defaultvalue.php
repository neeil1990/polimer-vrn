<?php

namespace Darneo\Ozon\EventHandlers\Orm;

use Bitrix\Main\ORM;

class DefaultValue extends Base
{
    /**
     * $fieldName - название поля в котором определяется, является ли запись таблицы значением по умолчанию.
     * Поле должно быть типа Entity\BooleanField.
     *
     * Функция вызывается в обработчиках событий onAfterAdd и onAfterUpdate для таблиц у записей которых есть значение
     * по умолчанию.
     * Проверка, является ли добавляемая/обновляемая запись значением по умолчанию.
     * Если да, то делает эту запись ЕДИНСТВЕННОЙ записью по умолчанию (у остальных записей убирает это значение).
     */
    public function set($fieldName): void
    {
        $fields = $this->eventParameters['fields'];
        $className = $this->dataClassName;

        if ($this->rowId > 0 && (bool)$fields[$fieldName] === true) {
            $result = $className::getList(
                [
                    'select' => [$this->primaryName],
                    'filter' => [
                        '!=' . $this->primaryName => $this->rowId,
                        $fieldName => true
                    ]
                ]
            );

            while ($row = $result->fetch()) {
                $className::update(
                    $row[$this->primaryName],
                    [
                        $fieldName => false
                    ]
                );
            }
        }
    }

    /**
     * $fieldName - название поля в котором определяется, является ли запись таблицы значением по умолчанию.
     * Поле должно быть типа Entity\BooleanField.
     *
     * Функция вызывается в обработчике события onBeforeDelete для таблиц у записей которых есть значение по умолчанию.
     * Проверка, является ли удаляемая запись значением по умолчанию.
     * Если да, то не дает удалить и возвращает ошибку.
     */
    public function check($fieldName): void
    {
        $className = $this->dataClassName;

        if ($this->rowId > 0) {
            $row = $className::getById($this->rowId)->fetch();
            if ((int)$row[$fieldName] === 1) {
                $this->eventResult->addError(
                    new ORM\EntityError(
                        'ERROR'
                    )
                );
            }
        }
    }
}
