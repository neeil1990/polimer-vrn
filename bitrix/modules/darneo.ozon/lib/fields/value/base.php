<?php

namespace Darneo\Ozon\Fields\Value;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Darneo\Ozon\Fields\Views\ViewInterface;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

/**
 * Базовый класс для работы со значением поля.
 * Получает значение поля из результатов запроса к базе данных, выполненного при помощи getList.
 * В конструктор данного класса передается массив с ключами нужными для получения значения поля из результатов запроса.
 * В наследниках этого класса могут быть определены функции для получения дополнительной информации в значение поля.
 * Например, если значением поля являет ID пользователя, то в классе-наследнике User мы определяем методы
 * для формирования и добавления в массив значения имени пользователя.
 *
 * Class Base
 *
 * @package Darneo\Ozon\Fields\Value
 * @var $fieldNameForValue - ключ массива результатов запроса для получения основного значения поля.
 * @var $value - содержит значение поля.
 * @var $rawValue - содержит необработанное значение поля.
 */
class Base implements ValueFromDbInterface
{
    protected $fieldNameForValue;
    private $value;
    private $rawValue;

    /**
     * 'VALUE' - ключ массива результатов запроса для получения основного значения поля.
     *
     * Base constructor.
     *
     * @param array $fieldNames - массив с ключами:
     */
    public function __construct(array $fieldNames)
    {
        if ($fieldNames['VALUE']) {
            $this->fieldNameForValue = $fieldNames['VALUE'];
        }
    }

    /**
     * @param $value
     * Устанавливает значение поля, если оно раньше уже где-то было получено.
     */
    public function set($value): void
    {
        $this->value = $value;
        $this->rawValue = $value;
    }

    /**
     * Устанавливает значение поля из результата запроса к базе данных.
     *
     * @param $row - результат запроса к базе данных из getList'а
     * @param array $selectFields - select для поля
     */
    public function setValueFromDb($row, array $selectFields = []): void
    {
        $selectModified = [];
        /** @var ExpressionField $select */
        foreach ($selectFields as $key => $select) {
            if ($select instanceof ExpressionField) {
                $selectModified[$key] = $select->getName();
            } else {
                $selectModified[$key] = $select;
            }
        }
        $rawValue = $this->extractFieldValueFromRawRow($row, $selectModified);
        $this->rawValue = $rawValue;
        $this->value = $this->getMainValue($rawValue);
    }

    /**
     * Функция по select получает значение поля из результата запроса к базе данных.
     *
     * @param array $row - результат запроса к базе данных
     * @param $selectFields - select для поля
     *
     * @return array|mixed
     */
    private function extractFieldValueFromRawRow(array $row, $selectFields)
    {
        if (is_array($selectFields)) {
            $rawValue = array_filter(
                $row,
                static function ($key) use ($selectFields) {
                    return isset($selectFields[$key]) || in_array($key, $selectFields, true);
                },
                ARRAY_FILTER_USE_KEY
            );
            if (count($rawValue) === 1) {
                $rawValue = array_pop($rawValue);
            }
        } else {
            $rawValue = $row[$selectFields];
        }

        return $rawValue;
    }

    /**
     * САМАЯ ВАЖНАЯ ФУНКЦИЯ! Возвращает основное значение поля из необработанного значения поля.
     *
     * @param $rawValue -
     *
     * @return array|mixed|string
     */
    protected function getMainValue($rawValue)
    {
        $mainValue = $rawValue;
        if (is_array($this->fieldNameForValue)) {
            $mainValue = array_filter(
                $rawValue,
                function ($key) {
                    return isset($this->fieldNameForValue[$key]);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        if (is_array($rawValue) && array_key_exists($this->fieldNameForValue, $rawValue)) {
            $mainValue = $rawValue[$this->fieldNameForValue] ?: '';
        }

        return $mainValue;
    }

    /**
     * Обрабатывает значение поля для сохранения в базу данных.
     *
     * @param $value
     *
     * @return mixed
     */
    public function forSave($value)
    {
        return $value;
    }

    /**
     * @return bool
     *
     */
    public function isValueExist(): bool
    {
        return !($this->value === null);
    }

    /**
     * Передает значение поля в шаблон.
     *
     * @param ViewInterface $view - шаблон
     *
     */
    public function setDataToView(ViewInterface $view): void
    {
        $view->setValue($this->get());
    }

    /**
     * Получить обработанное значение поля.
     *
     * @return string
     */
    public function get()
    {
        if (is_string($this->value)) {
            return HelpersEncoding::toUtf($this->value);
        }

        return $this->value;
    }

    /**
     * Возвращает либо элемент с ключом $fieldName из массива необработанного значения поля (если оно массив)
     * Либо необработанное значение поля целиком (если оно не массив)
     *
     * @param $fieldName
     *
     * @return string
     */
    protected function getFieldFromRawValue($fieldName): string
    {
        $rawValue = $this->getRaw();

        if (is_array($rawValue)) {
            $value = $rawValue[$fieldName];
        } else {
            $value = $rawValue;
        }

        return HelpersEncoding::toUtf($value);
    }

    /**
     * Получить необработанное значение поля.
     *
     * @return mixed
     */
    public function getRaw()
    {
        return $this->rawValue;
    }

    protected function convertToUtf($row): array
    {
        foreach ($row as &$value) {
            $value = HelpersEncoding::toUtf($value);
        }

        return $row;
    }
}
