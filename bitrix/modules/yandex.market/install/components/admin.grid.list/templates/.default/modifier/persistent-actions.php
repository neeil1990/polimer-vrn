<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var $component \Yandex\Market\Components\AdminGridList */

if ($arParams['ROW_ACTIONS_PERSISTENT'] === 'Y')
{
	require_once __DIR__ . '/../extension/emptyrow.php';

	$adminList = $component->getViewList();

	$row = new YaMarketAdminListEmptyRow($adminList->aHeaders, $adminList->table_id);
	$row->pList = $adminList;
	$row->AddActions([
		[ 'dummy' => true ],
	]);

	$adminList->aRows[] = $row;
}