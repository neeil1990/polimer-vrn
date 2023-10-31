<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

?>
<? if (!$arResult['IS_ACTIVE']): ?>
    <div class='alert alert-danger d-flex align-items-center p-5 mt-2 mb-5'>
        <i class='ki-duotone ki-shield-tick fs-2hx text-danger me-4'>
            <span class='path1'></span>
            <span class='path2'></span>
        </i>
        <div class='d-flex flex-column'>
            <h4 class='mb-1 text-danger'>
                <?= Loc::getMessage('DARNEO_OZON_VUE_ALERT_CRON_ANALYTIC_TITLE') ?>
            </h4>
            <span><?= Loc::getMessage('DARNEO_OZON_VUE_ALERT_CRON_ANALYTIC_TEXT') ?>
            <a href='<?= $arResult['SETTING_CRON_FOLDER'] ?>' class='btn btn-sm btn-primary ms-5'>
                <?= Loc::getMessage('DARNEO_OZON_VUE_ALERT_CRON_ANALYTIC_BUTTON') ?>
            </a>
        </span>
        </div>
    </div>
<? endif; ?>