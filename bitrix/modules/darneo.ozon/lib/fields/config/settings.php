<?php

namespace Darneo\Ozon\Fields\Config;

use Bitrix\Main\Entity\Base;
use Darneo\Ozon\Fields;
use Darneo\Ozon\Fields\Field;
use Darneo\Ozon\Main\Table\SettingsTable;

class Settings extends Manager
{
    public function getFields(): array
    {
        return [
            'IS_TEST' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('VALUE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Boolean(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'VALUE',
                    ],
                    'VALUE' => new Fields\Value\Type\Boolean(
                        [
                            'VALUE' => 'VALUE'
                        ]
                    ),
                ]
            ),
            'IS_CHAT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('VALUE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Boolean(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'VALUE',
                    ],
                    'VALUE' => new Fields\Value\Type\Boolean(
                        [
                            'VALUE' => 'VALUE'
                        ]
                    ),
                ]
            ),
        ];
    }

    protected function getEntity(): Base
    {
        return SettingsTable::getEntity();
    }
}
