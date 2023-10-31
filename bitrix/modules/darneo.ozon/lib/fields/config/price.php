<?php

namespace Darneo\Ozon\Fields\Config;

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Export\Table\PriceFilterTable;
use Darneo\Ozon\Export\Table\PriceListTable;
use Darneo\Ozon\Fields;
use Darneo\Ozon\Fields\Field;

class Price extends Manager
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
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRICE_VENDOR_CODE_HELPER_TEXT')
                ]
            ),
            'FILTER' => new Field(
                [
                    'INFO' => [
                        'NAME' => 'FILTER_PROP',
                        'TITLE' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRICE_FILTER_TITLE'),
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
                            'ENTITY' => PriceFilterTable::class,
                        ]
                    ),
                ]
            ),
            'TYPE_PRICE_ID' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('TYPE_PRICE_ID'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'TYPE_PRICE_ID',
                        'TYPE_PRICE_CURRENT_LANG' => 'TYPE_PRICE.CURRENT_LANG.NAME',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'TYPE_PRICE_ID',
                            'CONTENT' => 'TYPE_PRICE_CURRENT_LANG'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRICE_TYPE_PRICE_ID_HELPER_TEXT')
                ]
            ),
            'PRICE_RATIO' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('PRICE_RATIO'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'PRICE_RATIO',
                    ],
                    'VALUE' => new Fields\Value\Base(
                        [
                            'VALUE' => 'PRICE_RATIO'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRICE_PRICE_RATIO_HELPER_TEXT')
                ]
            ),
            'SITE_ID' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('SITE_ID'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'SITE_ID',
                        'SITE_NAME' => 'SITE.NAME',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'SITE_ID',
                            'CONTENT' => 'SITE_NAME'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRICE_SITE_ID_HELPER_TEXT')
                ]
            ),
            'IS_DISCOUNT_PRICE' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('IS_DISCOUNT_PRICE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Boolean(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IS_DISCOUNT_PRICE'
                    ],
                    'VALUE' => new Fields\Value\Type\Boolean(
                        [
                            'VALUE' => 'IS_DISCOUNT_PRICE',
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRICE_IS_DISCOUNT_PRICE_HELPER_TEXT')
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
        ];
    }

    protected function getEntity(): Base
    {
        return PriceListTable::getEntity();
    }
}
