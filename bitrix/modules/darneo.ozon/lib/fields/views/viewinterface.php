<?php

namespace Darneo\Ozon\Fields\Views;

use Darneo\Ozon\Fields\FieldInfo;

interface ViewInterface
{
    public function setField(FieldInfo $field);

    public function setValue($value);

    public function setAttributes(array $attributes);

    public function getHtml();

    public function setIsReport(bool $isReport);
}
