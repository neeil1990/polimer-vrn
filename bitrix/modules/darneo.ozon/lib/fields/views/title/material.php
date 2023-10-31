<?php

namespace Darneo\Ozon\Fields\Views\Title;

use Darneo\Ozon\Fields\Views\Base;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

class Material extends Base
{
    protected function getNode()
    {
        $label = $this->dom->createElement('label', HelpersEncoding::toUtf($this->field->getTitle()));

        if ($this->field->isRequired()) {
            $required = $this->dom->createElement('span', ' *');
            $required->setAttribute('style', 'color: red;');
            $label->appendChild($required);
        }

        return $label;
    }

    protected function getDefaultAttributes(): array
    {
        return [
            'for' => $this->field->getName(),
            'class' => 'block'
        ];
    }
}
