<?php

namespace Darneo\Ozon\Fields\Views\Show;

use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Fields\Views\Base;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

class Boolean extends Base
{
    protected function getNode()
    {
        $answer = [
            1 => Loc::getMessage('DARNEO_OZON_FIELD_VIEWS_SHOW_BOOLEAN_1'),
            0 => Loc::getMessage('DARNEO_OZON_FIELD_VIEWS_SHOW_BOOLEAN_0'),
        ];
        $value = (int)$this->value;

        return $this->dom->createElement('span', HelpersEncoding::toUtf($answer[$value]));
    }

    protected function getDefaultAttributes(): array
    {
        return [];
    }
}
