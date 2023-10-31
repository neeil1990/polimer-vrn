<?php

namespace Darneo\Ozon\Fields\Config;

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Export\Table\ProductFilterTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Fields;
use Darneo\Ozon\Fields\Field;

class Product extends Manager
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
            'PHOTO_MAIN' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('PHOTO_MAIN'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'PHOTO_MAIN' => 'PHOTO_MAIN',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'PHOTO_MAIN'
                        ]
                    ),
                ]
            ),
            'PHOTO_OTHER' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('PHOTO_OTHER'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'PHOTO_OTHER' => 'PHOTO_OTHER',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'PHOTO_OTHER'
                        ]
                    ),
                ]
            ),
            'ELEMENT_NAME' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('ELEMENT_NAME'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'ELEMENT_NAME' => 'ELEMENT_NAME',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'ELEMENT_NAME'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_ELEMENT_NAME_HELPER_TEXT')
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
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_VENDOR_CODE_HELPER_TEXT')
                ]
            ),
            'BAR_CODE' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('BAR_CODE'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'BAR_CODE' => 'BAR_CODE',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'BAR_CODE'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_BAR_CODE_HELPER_TEXT')
                ]
            ),
            'FILTER' => new Field(
                [
                    'INFO' => [
                        'NAME' => 'FILTER_PROP',
                        'TITLE' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_FILTER_TITLE'),
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
                            'ENTITY' => ProductFilterTable::class,
                        ]
                    ),
                ]
            ),
            'DOMAIN' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('DOMAIN'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'DOMAIN' => 'DOMAIN',
                    ],
                    'VALUE' => new Fields\Value\Base(
                        [
                            'VALUE' => 'DOMAIN'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_DOMAIN_HELPER_TEXT')
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
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_TYPE_PRICE_ID_HELPER_TEXT')
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
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_PRICE_RATIO_HELPER_TEXT')
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
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_SITE_ID_HELPER_TEXT')
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
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_IS_DISCOUNT_PRICE_HELPER_TEXT')
                ]
            ),
            'WEIGHT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('WEIGHT'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'WEIGHT',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'WEIGHT'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_WEIGHT_HELPER_TEXT')
                ]
            ),
            'WIDTH' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('WIDTH'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'WIDTH',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'WIDTH'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_WIDTH_HELPER_TEXT')
                ]
            ),
            'HEIGHT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('HEIGHT'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'HEIGHT',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'HEIGHT'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_HEIGHT_HELPER_TEXT')
                ]
            ),
            'LENGTH' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('LENGTH'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'IBLOCK_ID',
                        'LENGTH',
                    ],
                    'VALUE' => new Fields\Value\Type\Settings\Prop(
                        [
                            'VALUE' => 'IBLOCK_ID',
                            'CONTENT' => 'LENGTH'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_LENGTH_HELPER_TEXT')
                ]
            ),
            'DIMENSION_UNIT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('DIMENSION_UNIT'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'DIMENSION_UNIT',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'DIMENSION_UNIT',
                            'CONTENT' => 'DIMENSION_UNIT'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_DIMENSION_UNIT_HELPER_TEXT')
                ]
            ),
            'WEIGHT_UNIT' => new Field(
                [
                    'ENTITY_FIELD' => $this->entity->getField('WEIGHT_UNIT'),
                    'SHOW_VIEW' => new Fields\Views\Show\Div(),
                    'IS_EDIT' => true,
                    'SELECT' => [
                        'WEIGHT_UNIT',
                    ],
                    'VALUE' => new Fields\Value\Type\StringValue(
                        [
                            'VALUE' => 'WEIGHT_UNIT',
                            'CONTENT' => 'WEIGHT_UNIT'
                        ]
                    ),
                    'HELPER_TEXT' => Loc::getMessage('DARNEO_OZON_FIELD_CONFIG_PRODUCT_WEIGHT_UNIT_HELPER_TEXT')
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
        return ProductListTable::getEntity();
    }
}
