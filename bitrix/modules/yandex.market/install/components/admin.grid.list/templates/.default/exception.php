<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $this CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminFormEdit */

$component = $this->__component;

$component->showErrors();

if ($arResult['EXCEPTION_MIGRATION'])
{
	include __DIR__ . '/partials/migration-form.php';
}