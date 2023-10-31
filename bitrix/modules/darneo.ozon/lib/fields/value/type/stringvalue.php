<?php

namespace Darneo\Ozon\Fields\Value\Type;

use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Fields\Views\ViewInterface;

class StringValue extends Base
{
    private $fieldNameForContent;

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

        if (is_array($value)) {
            $value = $this->convertToUtf($value);
        }

        $view->setValue($value);
    }

    public function getContent(): string
    {
        $content = $this->getFieldFromRawValue($this->fieldNameForContent);
        $content = ($content ?? '');

        return $content;
    }
}
