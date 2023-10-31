<?php

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

use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);

?>
<div class='app-navbar-item ms-1 ms-md-3' id='kt_header_user_menu_toggle'>
    <div class="cursor-pointer symbol symbol-30px symbol-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
         data-kt-menu-attach='parent' data-kt-menu-placement='bottom-end'>
        <img src='<?= SITE_TEMPLATE_PATH ?>/image/1.png' alt='user'/>
    </div>
    <div class='menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px'
         data-kt-menu='true'>
        <div class='menu-item px-3'>
            <div class='menu-content d-flex align-items-center px-3'>
                <div class='symbol symbol-50px me-5'>
                    <img alt='Logo' src='<?= SITE_TEMPLATE_PATH ?>/image/1.png'/>
                </div>
                <div class='d-flex flex-column'>
                    <div class='fw-bold d-flex align-items-center fs-5'><?= $arResult['DATA_VUE']['FULL_NAME'] ?>
                        <span class='badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2'>Pro</span>
                    </div>
                    <span class='fw-semibold text-muted fs-7'>
                        <?= $arResult['DATA_VUE']['EMAIL'] ?>
                    </span>
                </div>
            </div>
        </div>
        <div class='separator my-2'></div>
        <div class='menu-item px-5'>
            <a href='?logout=yes&<?= bitrix_sessid_get() ?>' class='menu-link px-5'>
                <?= Loc::getMessage('DARNEO_OZON_TEMPLATE_HEADER_LOGOUT') ?>
            </a>
        </div>
    </div>
</div>
