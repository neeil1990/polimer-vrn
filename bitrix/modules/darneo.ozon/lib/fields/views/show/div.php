<?php

namespace Darneo\Ozon\Fields\Views\Show;

use Darneo\Ozon\Fields\Views\Base;

class Div extends Base
{
    protected function getNode()
    {
        return $this->dom->createElement('div', $this->value);
    }

    protected function getDefaultAttributes(): array
    {
        return [];
    }
}
