<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var \CBitrixComponentTemplate $this */

$this->IncludeLangFile('template.php');

include __DIR__ . '/modifier/box-dimensions-format.php';
include __DIR__ . '/modifier/box-properties.php';
include __DIR__ . '/modifier/box-property-values.php';
