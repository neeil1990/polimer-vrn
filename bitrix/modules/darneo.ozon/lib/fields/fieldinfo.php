<?php

namespace Darneo\Ozon\Fields;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ScalarField;
use Bitrix\Main\Localization\Loc;

/**
 * Класс помощник для получения: названия поля, имя поля, обязательность поля
 */
class FieldInfo
{
    private string $name;
    private string $title;
    private bool $required;

    public function __construct(array $params)
    {
        if ($this->isValidParams($params)) {
            $this->name = (string)$params['NAME'];
            $this->title = (string)$params['TITLE'];
            $this->required = (bool)$params['REQUIRED'];
        } else {
            throw new ArgumentException(Loc::getMessage('DARNEO_FIELDS_FIELD_INFO_INVALID_FIELD'));
        }
    }

    private function isValidParams(array $params): bool
    {
        return isset($params['NAME'], $params['TITLE'], $params['REQUIRED']);
    }

    public static function getInfoArray(ScalarField $field): array
    {
        return [
            'NAME' => $field->getName(),
            'TITLE' => $field->getTitle(),
            'REQUIRED' => $field->isRequired()
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
