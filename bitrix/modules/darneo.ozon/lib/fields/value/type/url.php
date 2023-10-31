<?php

namespace Darneo\Ozon\Fields\Value\Type;

use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Fields\Views\Show\Link;
use Darneo\Ozon\Fields\Views\ViewInterface;

class Url extends Base
{
    private $urlTemplate = '';
    private $fieldNameForUrlParam;
    private $fieldNameForUrlText;
    private $fieldNameForUrlTitle;
    private $showUrl = false;

    public function __construct($fieldNames)
    {
        parent::__construct($fieldNames);

        if ($fieldNames['URL_PARAM']) {
            $this->fieldNameForUrlParam = $fieldNames['URL_PARAM'];
        }

        if ($fieldNames['TEXT']) {
            $this->fieldNameForUrlText = $fieldNames['TEXT'];
        }

        if ($fieldNames['TITLE']) {
            $this->fieldNameForUrlTitle = $fieldNames['TITLE'];
        }
    }

    public function setDataToView(ViewInterface $view): void
    {
        if ($view instanceof Link) {
            $value['URL_PARAM'] = $this->getFieldNameForUrlParam();
            $value['URL'] = $this->getUrl();
            $value['TEXT'] = $this->getUrlText();
            $value['TITLE'] = $this->getUrlTitle();
        } else {
            $value = $this->getUrlText();
        }

        $view->setValue($value);
    }

    public function getFieldNameForUrlParam(): string
    {
        return $this->getFieldFromRawValue($this->fieldNameForUrlParam);
    }

    public function getUrl()
    {
        if ($this->isShowUrl()) {
            $urlParam = $this->getFieldFromRawValue($this->fieldNameForUrlParam);

            return str_replace('#URL_PARAM#', $urlParam, $this->urlTemplate);
        }

        return '';
    }

    public function isShowUrl(): bool
    {
        return $this->showUrl;
    }

    public function setShowUrl(bool $showUrl): void
    {
        $this->showUrl = $showUrl;
    }

    public function getUrlText(): string
    {
        $urlText = $this->getFieldFromRawValue($this->fieldNameForUrlText);
        $urlText = ($urlText ?? '');

        return $urlText;
    }

    public function getUrlTitle(): string
    {
        $urlTitle = $this->getFieldFromRawValue($this->fieldNameForUrlTitle);
        $urlTitle = ($urlTitle ?? '');

        return $urlTitle;
    }

    public function setUrlTemplate($urlTemplate): void
    {
        $this->urlTemplate = $urlTemplate;
    }
}
