<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Yandex\Market;

/** @var $this \CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminGridList */

$adminList = $component->getViewList();
$limitTop = isset($arParams['PAGER_LIMIT']) ? (int)$arParams['PAGER_LIMIT'] : 0;

if ($limitTop > 0)
{
	$arResult['LIST_EXTENSION']['limitTop'] = $limitTop;
}