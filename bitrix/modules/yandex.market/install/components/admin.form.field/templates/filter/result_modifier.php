<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

$arParams['ALLOW_NAME'] = (isset($arParams['ALLOW_NAME']) && $arParams['ALLOW_NAME'] === 'Y');
$arParams['ALLOW_SALES_NOTES'] = (isset($arParams['ALLOW_SALES_NOTES']) && $arParams['ALLOW_SALES_NOTES'] === 'Y');
$arParams['ALLOW_DELIVERY_OPTIONS'] = (isset($arParams['ALLOW_DELIVERY_OPTIONS']) && $arParams['ALLOW_DELIVERY_OPTIONS'] === 'Y');
$arParams['NEED_LEFT_COUNT'] = (isset($arParams['NEED_LEFT_COUNT']) && $arParams['NEED_LEFT_COUNT'] === 'Y');

include __DIR__ . '/modifier/field-enum.php';
include __DIR__ . '/modifier/compare-enum.php';