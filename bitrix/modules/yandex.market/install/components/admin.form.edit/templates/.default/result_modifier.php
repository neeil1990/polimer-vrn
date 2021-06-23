<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

$arResult['SPECIAL_FIELDS'] = [];
$arResult['SPECIAL_FIELDS_SHOWN'] = [];

include __DIR__ . '/modifier/tab-request.php';
include __DIR__ . '/modifier/iblock-data.php';
include __DIR__ . '/modifier/format-data.php';
include __DIR__ . '/modifier/special-service.php';
include __DIR__ . '/modifier/special-refresh-period.php';
include __DIR__ . '/modifier/special-shop-data.php';
include __DIR__ . '/modifier/special-setup-link.php';
include __DIR__ . '/modifier/special-promo-type.php';
include __DIR__ . '/modifier/special-promo-product.php';
include __DIR__ . '/modifier/special-external-id.php';
include __DIR__ . '/modifier/special-permissions.php';
include __DIR__ . '/modifier/special-field-finalize.php';