<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (empty($arParams['RELOAD_EVENTS'])) { return; }

$arResult['LIST_EXTENSION']['reloadEvents'] = (array)$arParams['RELOAD_EVENTS'];
