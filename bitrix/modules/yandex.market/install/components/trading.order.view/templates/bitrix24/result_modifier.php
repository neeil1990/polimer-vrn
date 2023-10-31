<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $this \CBitrixComponentTemplate */

$this->IncludeLangFile('template.php');

if (!isset($templateFolder)) { $templateFolder = $this->GetFolder(); }

$arResult['EDITOR'] = [
	//'ENTITY_CONFIG' => [],
	'ENTITY_FIELDS' => [],
	'ENTITY_DATA' => [],
];
$arResult['JS_MESSAGES'] = [];

include __DIR__ . '/modifier/columns.php';
include __DIR__ . '/modifier/notification.php';
include __DIR__ . '/modifier/properties.php';
include __DIR__ . '/modifier/basket-columns.php';
include __DIR__ . '/modifier/basket.php';
include __DIR__ . '/modifier/basket-confirm.php';
include __DIR__ . '/modifier/box-dimensions-format.php';
include __DIR__ . '/modifier/box-properties.php';
include __DIR__ . '/modifier/box-property-values.php';
include __DIR__ . '/modifier/shipment.php';
include __DIR__ . '/modifier/print.php';

$arResult['EDITOR']['ENTITY_CONFIG'] = array_values($arResult['COLUMNS']);
$arResult['EDITOR']['ENTITY_CONFIG'] = array_filter($arResult['EDITOR']['ENTITY_CONFIG'], static function($column) { return !empty($column['elements']); });