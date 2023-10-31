<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arTemplateParameters = [
    "ADD_ZONE_PRICE" => [
        "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_ADD_ZONE_PRICE"),
        "TYPE" => "CHECKBOX",
        "DEFAULT" => "Y",
        "PARENT" => "BASE"
    ],
    "START_PRICE" => [
        "NAME" => GetMessage("CORSIK_DELIVERY_SERVICE_START_PRICE"),
        "TYPE" => "STRING",
        "MULTIPLE" => "N",
        "DEFAULT" => "",
        "PARENT" => "BASE",
    ],

];
