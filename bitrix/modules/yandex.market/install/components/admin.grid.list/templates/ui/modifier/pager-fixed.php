<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Yandex\Market;

/** @var $this \CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminGridList */

$adminList = $component->getViewList();
$pagerFixed = isset($arParams['PAGER_FIXED']) ? (int)$arParams['PAGER_FIXED'] : 0;

if ($pagerFixed > 0)
{
	$arResult['LIST_EXTENSION']['disablePageSize'] = true;
}
