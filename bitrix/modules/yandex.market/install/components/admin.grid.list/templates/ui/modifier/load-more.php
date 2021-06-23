<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $component \Yandex\Market\Components\AdminGridList */

$adminList = $component->getViewList();

if ($adminList instanceof CAdminUiList && $adminList->enableNextPage)
{
	$arResult['LIST_EXTENSION']['loadMore'] = true;
}