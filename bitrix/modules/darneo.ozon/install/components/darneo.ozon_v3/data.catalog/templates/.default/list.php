<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Bitrix vars
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var $component
 */
?>

<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:alert.cron.analytic',
    '',
    [
        'SETTING_CRON_FOLDER' => $arParams['SETTING_CRON_FOLDER']
    ],
    false
);
?>

<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:data.catalog.list.exchange',
    '',
    [
        'SEF_FOLDER' => $arResult['SEF_FOLDER']
    ],
    false
);
?>

<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:data.catalog.list',
    '',
    [
        'SEF_FOLDER' => $arResult['SEF_FOLDER']
    ]
);
?>

<?php $this->SetViewTarget('title_right'); ?>
<a href='javascript:void(0)' class='btn btn-sm btn-flex bg-body btn-color-gray-700 btn-active-color-primary fw-bold'
   data-bs-toggle='modal' data-bs-target='.bd-example-modal-lg'>
    <i class='ki-duotone ki-message-question fs-2'>
        <i class='path1'></i>
        <i class='path2'></i>
        <i class='path3'></i>
    </i>
</a>
<?php $this->EndViewTarget(); ?>
<div class='modal fade bd-example-modal-lg' tabindex='-1' role='dialog' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h4 class='modal-title'>
                    <?= Loc::getMessage('DARNEO_OZON_VUE_CATALOG_LIST_HELP_TITLE') ?>
                </h4>
                <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <?= Loc::getMessage('DARNEO_OZON_VUE_CATALOG_LIST_HELP_TEXT') ?>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-secondary' type='button' data-bs-dismiss='modal'>
                    <?= Loc::getMessage('DARNEO_OZON_VUE_CATALOG_LIST_HELP_CLOSE') ?>
                </button>
            </div>
        </div>
    </div>
</div>