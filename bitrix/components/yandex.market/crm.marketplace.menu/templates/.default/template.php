<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

/** @var \CBitrixComponentTemplate $this */

$this->SetViewTarget('above_pagetitle');

$APPLICATION->IncludeComponent('bitrix:main.interface.buttons', '', [
	'ID' => $arResult['MENU_ID'],
	'ITEMS' => $arResult['MENU_ITEMS'],
], $component);

$this->EndViewTarget();
