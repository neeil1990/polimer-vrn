<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Corsik\YaDelivery\{Handler, Options};

Loader::includeModule('corsik.yadelivery');
$handler = Handler::getInstance();
$warehouses = $handler->getDataToSelect('warehouses');
$personTypes = [];

foreach (Options::getTypePayers() as $personType) {
    $personTypes[$personType["ID"]] = $personType["NAME"];
}

$arComponentParameters = [
    "GROUPS" => [
        "MAPS_SETTINGS" => [
            "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_MAPS_SETTINGS")
        ],
    ],
    "PARAMETERS" => [
        "SELECT_WAREHOUSE" => [
            "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_SELECT_WAREHOUSE"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => $warehouses,
            "DEFAULT" => "N",
            "REFRESH" => "Y",
            "PARENT" => "MAPS_SETTINGS",
        ],
        "PERSON_TYPE" => [
            "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_PERSON_TYPE"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => $personTypes,
            "REFRESH" => "Y",
            "PARENT" => "BASE",
        ],
        "DISPLAY_MAP" => [
            "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_DISPLAY"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => [
                "PAGE" => GetMessage("CORSIK_DELIVERY_SERVICE_DISPLAY_PAGE"),
                "MODAL" => GetMessage("CORSIK_DELIVERY_SERVICE_DISPLAY_MODAL")
            ],
            "DEFAULT" => "PAGE",
            "REFRESH" => "Y",
            "PARENT" => "MAPS_SETTINGS",
        ],
        "TYPE_PROMPTS" => [
            "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_TYPE_PROMPTS"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => [
                "YANDEX" => GetMessage("CORSIK_DELIVERY_SERVICE_TYPE_PROMPTS_YANDEX"),
                "DADATA" => GetMessage("CORSIK_DELIVERY_SERVICE_TYPE_PROMPTS_DADATA")
            ],
            "DEFAULT" => "YANDEX",
            "REFRESH" => "Y",
            "PARENT" => "MAPS_SETTINGS",
        ],
    ]
];
