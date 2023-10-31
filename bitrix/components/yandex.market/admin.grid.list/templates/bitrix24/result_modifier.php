<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;

/** @var \CBitrixComponentTemplate $this */
/** @var Yandex\Market\Components\AdminGridList $component */

if (!isset($templateFolder)) { $templateFolder = $this->__folder; }
if (!isset($component)) { $component = $this->getComponent(); }

$adminList = $component->getViewList();

if (!($adminList instanceof CAdminUiList))
{
	ShowError('ui template only for CAdminUiList');
	return;
}

$gridOptions = new Main\Grid\Options($adminList->table_id);

$arResult['GRID_PARAMETERS'] = [
	'GRID_ID' => $adminList->table_id,
];

include __DIR__ . '/modifier/grid-common.php';
include __DIR__ . '/modifier/grid-action-panel.php';
include __DIR__ . '/modifier/grid-sort.php';
include __DIR__ . '/modifier/grid-pager.php';
include __DIR__ . '/modifier/grid-headers.php';
include __DIR__ . '/modifier/grid-rows.php';
include __DIR__ . '/modifier/grid-filter.php';
include __DIR__ . '/modifier/grid-messages.php';
include __DIR__ . '/modifier/list-extension.php';
