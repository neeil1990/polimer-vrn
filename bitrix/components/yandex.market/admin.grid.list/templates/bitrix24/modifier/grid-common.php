<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$arResult['GRID_PARAMETERS'] += [
	'AJAX_MODE' => 'Y',
	'AJAX_OPTION_JUMP' => 'N',
	'AJAX_OPTION_HISTORY' => 'N',
	'AJAX_ID' => CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
	'SHOW_PAGESIZE' => true,
	'ALLOW_PIN_HEADER' => true,
	'ALLOW_VALIDATE' => false,
	'HANDLE_RESPONSE_ERRORS' => true,
];