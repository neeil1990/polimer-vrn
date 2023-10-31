<?php

namespace Darneo\Ozon\Fields\Value;

use Darneo\Ozon\Fields\Views\ViewInterface;

interface ValueInterface
{
    public function forSave($value);

    public function isValueExist();

    public function get();

    public function set($value);

    public function setDataToView(ViewInterface $view);
}
