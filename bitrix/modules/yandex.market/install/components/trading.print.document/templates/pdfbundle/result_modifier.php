<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market\Ui;
use Bitrix\Main;

/** @var array $arResult */
/** @var array $arParams */

if (!Main\Loader::includeModule('yandex.market')) { return; }

$setupId = $arParams['SETUP_ID'];

$arResult['DOWNLOAD_LIST'] = array_map(static function($item) use ($setupId) {
	return Ui\Admin\Path::getModuleUrl('trading_file_download', [
		'setup' => $setupId,
		'url' => $item['URL'],
	]);
}, $arResult['ITEMS']);
