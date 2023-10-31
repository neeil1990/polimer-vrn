<?php

namespace Darneo\Ozon\Fields;

use Bitrix\Main\Config\ConfigurationException;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Fields\Value\ValueInterface;
use Darneo\Ozon\Fields\Views\Title\Material;
use Darneo\Ozon\Fields\Views\ViewInterface;

class Field
{
    private ViewInterface $showView;

    private Material $titleView;
    private FieldInfo $info;
    private Base $value;

    private array $select;
    private string $urlTemplate;
    private bool $isEdit = false;
    private string $helperText = '';

    public function __construct(array $fields)
    {
        $info = [];

        if (isset($fields['ENTITY_FIELD'])) {
            $info = FieldInfo::getInfoArray($fields['ENTITY_FIELD']);
        }

        if (isset($fields['INFO'])) {
            $info = $fields['INFO'];
        }

        $this->setInfo(new FieldInfo($info));

        if ($fields['SHOW_VIEW']) {
            $this->setShowView($fields['SHOW_VIEW']);
        }

        if ($fields['SELECT']) {
            $this->setSelect($fields['SELECT']);
        }

        if ($fields['VALUE']) {
            $this->setValueObject($fields['VALUE']);
        }

        if ($fields['URL_TEMPLATES']) {
            $this->setUrlTemplate($fields['URL_TEMPLATES']);
        }

        if ($fields['IS_EDIT']) {
            $this->setIsEdit($fields['IS_EDIT']);
        }

        if ($fields['HELPER_TEXT']) {
            $this->setHelperText($fields['HELPER_TEXT']);
        }

        $this->setTitleView();
    }

    public function setValueObject(Base $value): void
    {
        $this->value = $value;
    }

    public function isEdit(): bool
    {
        return $this->isEdit;
    }

    public function setIsEdit(bool $isEdit): void
    {
        $this->isEdit = $isEdit;
    }

    public function getInfo(): FieldInfo
    {
        return $this->info;
    }

    public function setInfo(FieldInfo $info): void
    {
        $this->info = $info;
    }

    public function getHelperText(): string
    {
        return $this->helperText;
    }

    public function setHelperText(string $helperText): void
    {
        $this->helperText = $helperText;
    }

    public function getTitleView(): Material
    {
        $this->titleView->setField($this->info);

        return $this->titleView;
    }

    public function setTitleView(): void
    {
        $this->titleView = new Material();
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function setSelect($select): void
    {
        $this->select = $select;
    }

    public function getShowView(): ViewInterface
    {
        if ($this->showView === null) {
            throw new ConfigurationException(Loc::getMessage('DARNEO_FIELDS_FIELD_NO_TEMPLATE_FIELD'));
        }

        $this->value->setDataToView($this->showView);

        return $this->showView;
    }

    public function setShowView(ViewInterface $view): void
    {
        $this->showView = $view;
    }

    public function setValueFromDb(array $row): void
    {
        $this->value->setValueFromDb($row, $this->select);
    }

    public function getValue(): ValueInterface
    {
        return $this->value;
    }

    public function getUrlTemplate(): string
    {
        return $this->urlTemplate;
    }

    public function setUrlTemplate($urlTemplate): void
    {
        $this->urlTemplate = $urlTemplate;
    }
}
