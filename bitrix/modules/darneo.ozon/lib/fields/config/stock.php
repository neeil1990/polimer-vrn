<?php

namespace Darneo\Ozon\Fields\Config;

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Export\Table\StockFilterTable;
use Darneo\Ozon\Export\Table\StockListTable;
use Darneo\Ozon\Fields;
use Darneo\Ozon\Fields\Field;

class Stock extends Manager
{
    public function getFields(): array
    {
        return [
            'DATE_CREATED' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('DATE_CREATED'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => false,
                    'SELECT' => [
                        'DATE_CREATED'
                    ],
                    'VALUE' => new Fields\Value\Type\Date(
                        [
                            'VALUE' => 'DATE_CREATED'
                        ]
                    ),
                ]
            ),
            'TITLE' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('TITLE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'TITLE',
                    ],
                    'VALUE' => new Fields\Value\Base(
                        [
                            'VALUE' => 'TITLE'
                        ]
                    ),
                ]
            ),
            'IBLOCK' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('IBLOCK_ID'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'IBLOCK_NAME' => 'IBLOCK.NAME',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'IBLOCK_NAME'
                        ]
                    ),
                ]
            ),
            'VENDOR_CODE' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('VENDOR_CODE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'VENDOR_CODE' => 'VENDOR_CODE',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'VENDOR_CODE'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_STOCK_VENDOR_CODE_HELPER_TEXT')
                ]
            ),
            'OZON_STOCK' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('OZON_STOCK_ID'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'OZON_STOCK_ID',
                        'OZON_STOCK_NAME' => 'OZON_STOCK.NAME',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'OZON_STOCK_ID',
                            'CONTENT' => 'OZON_STOCK_NAME'
                        ]
                    ),
                ]
            ),
            'STORE' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('STORE_ID'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'STORE_ID'
                    ],
                    'VALUE' => new Fields\Value\Type\Stock(
                        [
                            'VALUE' => 'STORE_ID'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_STOCK_STORE_HELPER_TEXT')
                ]
            ),
            'MAX_COUNT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('MAX_COUNT_STORE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'MAX_COUNT_STORE',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'MAX_COUNT_STORE',
                            'CONTENT' => 'MAX_COUNT_STORE'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_STOCK_MAX_COUNT_HELPER_TEXT')
                ]
            ),
            'MIN_COUNT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('MIN_COUNT_STORE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'MIN_COUNT_STORE',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'MIN_COUNT_STORE',
                            'CONTENT' => 'MIN_COUNT_STORE'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_STOCK_MIN_COUNT_HELPER_TEXT')
                ]
            ),
            'FILTER' => new Field(
                [
                    'INFO' => [
                        'NAME' => 'FILTER_PROP',
                        'TITLE' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_STOCK_FILTER_TITLE'),
                        'REQUIRED' => false
                    ],
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'ID',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\PropCompareValue(
                        [
                            'VALUE' => 'ID',
                            'ENTITY' => StockFilterTable::class,
                        ]
                    ),
                ]
            ),
            'IS_CRON' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('IS_CRON'),
                    'SHOW_VIEW' => new Fields\Views\Show\Boolean(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IS_CRON'
                    ],
                    'VALUE' => new Fields\Value\Type\Boolean(
                        [
                            'VALUE' => 'IS_CRON',
                        ]
                    ),
                ]
            ),
            'DISABLE_OPTIMISATION' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('DISABLE_OPTIMISATION'),
                    'SHOW_VIEW' => new Fields\Views\Show\Boolean(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'DISABLE_OPTIMISATION'
                    ],
                    'VALUE' => new Fields\Value\Type\Boolean(
                        [
                            'VALUE' => 'DISABLE_OPTIMISATION',
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_STOCK_DISABLE_OPTIMISATION_HELPER_TEXT')
                ]
            ),
        ];
    }

    protected function getEntity(): Base
    {
        return StockListTable::getEntity();
    }
}
