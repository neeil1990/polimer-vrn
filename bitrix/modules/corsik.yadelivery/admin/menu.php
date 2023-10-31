<?php

use Bitrix\Main\Localization\Loc;

$message = Loc::loadLanguageFile(__FILE__);

return [
    'parent_menu' => 'global_menu_services',
    'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_NAME'),
    'module_id' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_MENU_ID'),
    'items_id' => 'menu_' . Loc::getMessage('CORSIK_DELIVERY_SERVICE_MENU_ID'),
    'icon' => 'sale_menu_icon',
    'page_icon' => 'sale_menu_icon',
    'sort' => 1,
    'items' => [
        [
            'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_ZONES'),
            'url' => 'corsik_yadelivery_zones.php?lang=' . LANG,
            'more_url' => [
                'corsik_yadelivery_zone_edit.php?lang=' . LANG,
            ]
        ],
        [
            'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_WAREHOUSES'),
            'url' => 'corsik_yadelivery_warehouses.php?lang=' . LANG,
            'more_url' => [
                'corsik_yadelivery_warehouse_edit.php?lang=' . LANG,
            ]
        ],
        [
            'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_CONDITIONS'),
            'url' => 'corsik_yadelivery_rules.php?lang=' . LANG,
            'more_url' => [
                'corsik_yadelivery_rule_edit.php?lang=' . LANG,
            ]
        ],
        [
            'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_SETTINGS'),
            'items_id' => 'corsik_yadelivery_settings',
            'more_url' => [
                'corsik_yadelivery_delivery_setup.php',
                'corsik_yadelivery_dadata.php',
                'corsik_yadelivery_additional_fields.php'
            ],
            'items' => [
                [
                    'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_DELIVERY'),
                    'url' => 'corsik_yadelivery_delivery_setup.php?lang=' . LANG
                ],
                [
                    'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_DADATA_SETUP'),
                    'url' => 'corsik_yadelivery_dadata.php?lang=' . LANG
                ],
                [
                    'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_ADDITIONAL_FIELDS'),
                    'url' => 'corsik_yadelivery_additional_fields.php?lang=' . LANG
                ],
                [
                    'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_MODAL_SETUP'),
                    'url' => 'corsik_yadelivery_modal_setup.php?lang=' . LANG
                ]
//                [
//                    'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_ADDITIONAL_FEATURES'),
//                    'items_id' => 'corsik_yadelivery_additional_features',
//                    'more_url' => [
//                        'corsik_yadelivery_additional_features.php',
//                    ],
//                    'items' => [
//                        [
//                            'text' => Loc::getMessage('CORSIK_DELIVERY_SERVICE_ADDITIONAL_FIELDS'),
//                            'url' => 'corsik_yadelivery_additional_fields.php?lang=' . LANG
//                        ]
//                    ],
//                ],
            ],
        ],
    ],
];
?>
