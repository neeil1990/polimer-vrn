<?php

namespace Darneo\Ozon\Fields\Views\Show;

use Darneo\Ozon\Fields\Views\Base;

class Link extends Base
{
    protected function getNode()
    {
        if ($this->value['URL']) {
            $link = $this->dom->createElement('a', $this->value['TEXT']);
            $link->setAttribute('href', $this->value['URL']);
            $link->setAttribute('title', $this->value['TITLE']);
        } else {
            $link = $this->dom->createElement('span', $this->value['TEXT']);
        }

        return $link;
    }

    protected function getDefaultAttributes(): array
    {
        return [];
    }
}
