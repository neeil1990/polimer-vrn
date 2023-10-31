<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

$defaultSort = $adminList->sort instanceof CAdminSorting
	? ['sort' => [$adminList->sort->getField() => $adminList->sort->getOrder()]]
	: [];
$sorting = $gridOptions->GetSorting($defaultSort);

$arResult['GRID_PARAMETERS'] += [
	'SORT' => $sorting['sort'],
	'SORT_VARS' => $sorting['vars'],
];