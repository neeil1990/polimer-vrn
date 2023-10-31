<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

/** @var \CAdminResult $navigation */
$navigation = $arResult['NAV_OBJECT'];

$arResult['GRID_PARAMETERS'] += [
	'TOTAL_ROWS_COUNT' => $adminList->totalRowCount,
	'SHOW_PAGINATION' => (bool)$adminList->sNavText,
	'PAGINATION' => [
		'PAGE_NUM' => $navigation->NavPageNomer,
		'ENABLE_NEXT_PAGE' => $adminList->enableNextPage && ($navigation->NavPageNomer < $navigation->NavPageCount),
		'NAV_OBJECT' => $navigation,
		'SHOW_COUNT' => 'N',
		'URL' => $APPLICATION->GetCurPageParam('', ['apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal']),
	],
];