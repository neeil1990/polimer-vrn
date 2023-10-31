<?php

namespace Darneo\Ozon\Fields\Value\Type\Settings;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Export\Helper\Compare as HelperCompare;
use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Fields\Views\ViewInterface;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

class PropCompareValue extends Base
{
    private $entity;
    private bool $isPropertyList = false;
    private bool $isPropertyElement = false;

    public function __construct(array $fieldNames)
    {
        parent::__construct($fieldNames);
        $this->entity = $fieldNames['ENTITY'];
    }

    public function setDataToView(ViewInterface $view): void
    {
        $rows = [];

        $settingId = $this->get();
        $settingFilterList = $this->getSettingFilterList($settingId);

        foreach ($settingFilterList as $settingFilter) {
            $propId = $settingFilter['PROP_ID'];
            $compareType = $settingFilter['COMPARE_TYPE'];
            $compareValue = $settingFilter['COMPARE_VALUE'];

            $data = [];
            $data[] = $this->getProperty($propId);
            $data[] = HelperCompare::getName($compareType);

            if ((int)$compareValue && $this->isPropertyList) {
                $data[] = $this->getPropertyEnum($propId, (int)$compareValue) ?: $compareValue;
            } elseif ($compareValue && $this->isPropertyElement) {
                $data[] = $this->getElement((int)$compareValue) ?: $compareValue;
            } else {
                $data[] = $compareValue === '' ?
                    Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_COMPARE_EMPTY') : $compareValue;
            }

            $rows[] = implode(' - ', $data);
        }

        $value = $rows ? implode('<br>', $rows) : '[---]';
        $value = HelpersEncoding::toUtf($value);

        $view->setValue($value);
    }

    private function getSettingFilterList(int $elementId): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ELEMENT_ID' => $elementId
            ],
            'select' => [
                'ID',
                'PROP_ID',
                'COMPARE_TYPE',
                'COMPARE_VALUE',
            ]
        ];
        $result = $this->entity::getList($parameters);

        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function getProperty(int $propId): string
    {
        $rows = '';
        $parameters = [
            'filter' => [
                'ID' => $propId,
                'ACTIVE' => 'Y',
                'PROPERTY_TYPE' => [
                    PropertyTable::TYPE_STRING,
                    PropertyTable::TYPE_NUMBER,
                    PropertyTable::TYPE_LIST,
                    PropertyTable::TYPE_ELEMENT
                ],
                'MULTIPLE' => 'N'
            ],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE'],
            'order' => ['SORT' => 'ASC', 'NAME' => 'ASC']
        ];
        $result = PropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            if ($row['PROPERTY_TYPE'] === PropertyTable::TYPE_LIST) {
                $this->isPropertyList = true;
            }
            if ($row['PROPERTY_TYPE'] === PropertyTable::TYPE_ELEMENT) {
                $this->isPropertyElement = true;
            }
            $rows = '[' . $row['CODE'] . '] ' . $row['NAME'];
        }

        return $rows;
    }

    private function getPropertyEnum(int $propertyId, int $valueId): string
    {
        $parameters = [
            'filter' => [
                'ID' => $valueId,
                'PROPERTY_ID' => $propertyId,
            ],
            'select' => ['ID', 'PROPERTY_ID', 'VALUE'],
            'order' => ['SORT' => 'ASC', 'VALUE' => 'ASC']
        ];
        $result = PropertyEnumerationTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['VALUE'] ?: '';
        }

        return '';
    }

    private function getElement(int $elementId): string
    {
        $parameters = [
            'filter' => [
                'ID' => $elementId
            ],
            'select' => [
                'NAME',
            ]
        ];
        $result = ElementTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['NAME'] ?: '';
        }

        return '';
    }

    public function getPropId(): string
    {
        $content = $this->getFieldFromRawValue($this->fieldNameForPropId);
        $content = ($content ?? '');

        return $content;
    }

    public function getCompareType(): string
    {
        $content = $this->getFieldFromRawValue($this->fieldNameForCompareType);
        $content = ($content ?? '');

        return $content;
    }

    public function getCompareValue(): string
    {
        $content = $this->getFieldFromRawValue($this->fieldNameForCompareValue);
        $content = ($content ?? '');

        return $content;
    }
}
