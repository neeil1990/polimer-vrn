<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;

$inheritComponent = new \CBitrixComponent();

if (!$inheritComponent->InitComponent($component->getName())) { return; }

$inheritComponent->setTemplateName('ui');

if (!$inheritComponent->initComponentTemplate()) { return; }

$inheritFolder = $inheritComponent->getTemplate()->GetFolder();

if (!$inheritFolder) { return; }

$inheritAbsolute = Main\IO\Path::convertRelativeToAbsolute($inheritFolder);

$arResult['TEMPLATE_PARENT_FOLDER'] = $inheritFolder;
$arResult['LIST_EXTENSION'] = [];

include $inheritAbsolute . '/modifier/pager-limit.php';
include $inheritAbsolute . '/modifier/pager-fixed.php';
include $inheritAbsolute . '/modifier/reload-events.php';
