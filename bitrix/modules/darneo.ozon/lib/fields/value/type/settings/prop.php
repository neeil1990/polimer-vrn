<?php

namespace Darneo\Ozon\Fields\Value\Type\Settings;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Fields\Views\ViewInterface;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

class Prop extends Base
{
    private $fieldNameForContent;
    private int $iblockCatalogId;
    private int $iblockOffersId;

    public function __construct(array $fieldNames)
    {
        parent::__construct($fieldNames);

        if ($fieldNames['CONTENT']) {
            $this->fieldNameForContent = $fieldNames['CONTENT'];
        }
    }

    public function setDataToView(ViewInterface $view): void
    {
        $value = $this->getContent();
        $this->iblockCatalogId = $this->get();
        $this->iblockOffersId = $this->getOffersIblockId($this->iblockCatalogId);

        $text = '';
        switch ($value) {
            case 'CATALOG_PREVIEW_PICTURE':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_CATALOG_PREVIEW_PICTURE');
                break;
            case 'CATALOG_DETAIL_PICTURE':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_CATALOG_DETAIL_PICTURE');
                break;
            case 'OFFERS_PREVIEW_PICTURE':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_OFFERS_PREVIEW_PICTURE');
                break;
            case 'OFFERS_DETAIL_PICTURE':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_OFFERS_DETAIL_PICTURE');
                break;
            case 'CATALOG_PREVIEW_TEXT':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_CATALOG_PREVIEW_TEXT');
                break;
            case 'CATALOG_DETAIL_TEXT':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_CATALOG_DETAIL_TEXT');
                break;
            case 'OFFERS_PREVIEW_TEXT':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_OFFERS_PREVIEW_TEXT');
                break;
            case 'OFFERS_DETAIL_TEXT':
                $text = Loc::getMessage('DARNEO_OZON_FIELD_VALUE_TYPE_SETTINGS_PROP_OFFERS_DETAIL_TEXT');
                break;
        }

        if ($text) {
            $text = HelpersEncoding::toUtf($text);
            $view->setValue($text);
            return;
        }

        if ($this->iblockCatalogId > 0 && $value) {
            $value = $this->getProperty($value);
        } else {
            $value = '[---]';
        }

        $value = HelpersEncoding::toUtf($value);

        $view->setValue($value);
    }

    public function getContent(): string
    {
        $content = $this->getFieldFromRawValue($this->fieldNameForContent);
        $content = ($content ?? '');

        return $content;
    }

    private function getOffersIblockId(int $iblockId): int
    {
        $parameters = [
            'filter' => [
                'PRODUCT_IBLOCK_ID' => $iblockId
            ],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = CatalogIblockTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'];
        }

        return 0;
    }

    private function getProperty(int $propertyId): string
    {
        $rows = '';
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ID' => $propertyId
            ],
            'select' => ['ID', 'CODE', 'NAME', 'IBLOCK_ID'],
            'order' => ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
        ];
        $result = PropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            $prefix = (int)$row['IBLOCK_ID'] === $this->iblockOffersId ? 'OFFERS: ' : 'CATALOG: ';
            $rows = $prefix . $row['NAME'] . ' [' . $row['ID'] . ']';
        }

        return $rows;
    }
}
