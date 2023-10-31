<?php

namespace Darneo\Ozon\Fields\Value\Type;

use Darneo\Ozon\Fields\Value\Base;

class Boolean extends Base
{
    public function get(): bool
    {
        return (bool)$this->getRaw();
    }

    public function forSave($value): bool
    {
        return (boolean)$value;
    }
}
