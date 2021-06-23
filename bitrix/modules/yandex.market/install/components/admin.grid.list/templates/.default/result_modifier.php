<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $component \Yandex\Market\Components\AdminGridList */
/** @var $this \CBitrixComponentTemplate */

$this->IncludeLangFile('template.php');

if (!isset($templateFolder)) { $templateFolder = $this->__folder; }
if (!isset($component)) { $component = $this->__component; }

include __DIR__ . '/modifier/load-more.php';
include __DIR__ . '/modifier/pager-limit.php';
include __DIR__ . '/modifier/persistent-actions.php';