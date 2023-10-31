<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;

if (!Loader::includeModule('darneo.ozon')) {
    return;
}

if (!\Darneo\Ozon\Main\Helper\Access::isPermission()) {
    LocalRedirect('/bitrix/admin/');
}

Extension::load(['ui.alerts']);
?>
<!DOCTYPE html>
<html xml:lang='<?= LANGUAGE_ID ?>' lang='<?= LANGUAGE_ID ?>'>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

    <link rel='icon' type='image/x-icon' href='<?= SITE_TEMPLATE_PATH ?>/image/favicon.png'>
    <link rel='apple-touch-icon' sizes='152x152' href='<?= SITE_TEMPLATE_PATH ?>/image/favicon.png'>
    <link rel='apple-touch-icon-precomposed' sizes='152x152' href='<?= SITE_TEMPLATE_PATH ?>/image/favicon.png'>

    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700'/>
    <link href='<?= SITE_TEMPLATE_PATH ?>/assets/plugins/global/plugins.bundle.css' rel='stylesheet' type='text/css'/>
    <link href='<?= SITE_TEMPLATE_PATH ?>/assets/css/style.bundle.css' rel='stylesheet' type='text/css'/>
    <script src='<?= SITE_TEMPLATE_PATH ?>/js/jquery-3.5.1.min.js'></script>
    <title><?php $APPLICATION->ShowTitle() ?></title>
    <?php $APPLICATION->ShowHead(); ?>
</head>
<body id='kt_app_body' data-kt-app-layout='dark-sidebar' data-kt-app-header-fixed='true'
      data-kt-app-sidebar-enabled='true' data-kt-app-sidebar-fixed='true' data-kt-app-sidebar-hoverable='true'
      data-kt-app-sidebar-push-header='true' data-kt-app-sidebar-push-toolbar='true'
      data-kt-app-sidebar-push-footer='true' data-kt-app-toolbar-enabled='true' class='app-default'>

<script>var defaultThemeMode = 'light'
    var themeMode
    if (document.documentElement) {
        if (document.documentElement.hasAttribute('data-bs-theme-mode')) {
            themeMode = document.documentElement.getAttribute('data-bs-theme-mode')
        } else {
            if (localStorage.getItem('data-bs-theme') !== null) {
                themeMode = localStorage.getItem('data-bs-theme')
            } else {
                themeMode = defaultThemeMode
            }
        }
        if (themeMode === 'system') {
            themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        }
        document.documentElement.setAttribute('data-bs-theme', themeMode)
    }</script>

<div class='d-flex flex-column flex-root app-root' id='kt_app_root'>
    <div class='app-page flex-column flex-column-fluid' id='kt_app_page'>
        <div id='kt_app_header' class='app-header'>
            <div class='app-container container-fluid d-flex align-items-stretch justify-content-between'
                 id='kt_app_header_container'>
                <div class='d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2' title='Show sidebar menu'>
                    <div class='btn btn-icon btn-active-color-primary w-35px h-35px' id='kt_app_sidebar_mobile_toggle'>
                        <i class='ki-duotone ki-abstract-14 fs-2 fs-md-1'>
                            <span class='path1'></span>
                            <span class='path2'></span>
                        </i>
                    </div>
                </div>
                <div class='d-flex align-items-center flex-grow-1 flex-lg-grow-0'>
                    <a href='/ozon/' class='d-lg-none'>
                        <img alt='Logo' src='<?= SITE_TEMPLATE_PATH ?>/assets/media/logos/default-small.svg'
                             class='h-30px'/>
                    </a>
                </div>
                <div class='d-flex align-items-stretch justify-content-between flex-lg-grow-1'
                     id='kt_app_header_wrapper'>
                    <div class='app-header-menu app-header-mobile-drawer align-items-stretch' data-kt-drawer='true'
                         data-kt-drawer-name='app-header-menu' data-kt-drawer-activate='{default: true, lg: false}'
                         data-kt-drawer-overlay='true' data-kt-drawer-width='250px' data-kt-drawer-direction='end'
                         data-kt-drawer-toggle='#kt_app_header_menu_toggle' data-kt-swapper='true'
                         data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
                         data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                        <div class='menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0'
                             id='kt_app_header_menu' data-kt-menu='true'>
                            <?php $APPLICATION->IncludeComponent(
                                'darneo.ozon_v3:settings.key.tab',
                                '',
                                [
                                    'SEF_FOLDER' => '/ozon/settings/key/'
                                ],
                                false
                            ); ?>
                            <?php if (HelperSettings::isTest()): ?>
                                <div class='menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2'>
                                    <span class='menu-link bg-danger'>
                                        <span class='menu-title text-white'>
                                            <?= Loc::getMessage('DARNEO_OZON_TEMPLATE_HEADER_IS_TEST_TITLE') ?>
                                            <span data-hint='<?= Loc::getMessage(
                                                'DARNEO_OZON_TEMPLATE_HEADER_IS_TEST_HELPER'
                                            ) ?>'></span>
                                        </span>
                                        <span class='menu-arrow d-lg-none'></span>
                                    </span>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class='app-navbar flex-shrink-0'>
                        <div class='app-navbar-item ms-1 ms-md-3'>
                            <a href='#'
                               class='btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-30px h-30px w-md-40px h-md-40px'
                               data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach='parent'
                               data-kt-menu-placement='bottom-end'>
                                <i class='ki-duotone ki-night-day theme-light-show fs-2 fs-lg-1'>
                                    <span class='path1'></span>
                                    <span class='path2'></span>
                                    <span class='path3'></span>
                                    <span class='path4'></span>
                                    <span class='path5'></span>
                                    <span class='path6'></span>
                                    <span class='path7'></span>
                                    <span class='path8'></span>
                                    <span class='path9'></span>
                                    <span class='path10'></span>
                                </i>
                                <i class='ki-duotone ki-moon theme-dark-show fs-2 fs-lg-1'>
                                    <span class='path1'></span>
                                    <span class='path2'></span>
                                </i>
                            </a>
                            <div class='menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px'
                                 data-kt-menu='true' data-kt-element='theme-mode-menu'>
                                <div class='menu-item px-3 my-0'>
                                    <a href='#' class='menu-link px-3 py-2' data-kt-element='mode'
                                       data-kt-value='light'>
                                        <span class='menu-icon' data-kt-element='icon'>
                                            <i class='ki-duotone ki-night-day fs-2'>
                                                <span class='path1'></span>
                                                <span class='path2'></span>
                                                <span class='path3'></span>
                                                <span class='path4'></span>
                                                <span class='path5'></span>
                                                <span class='path6'></span>
                                                <span class='path7'></span>
                                                <span class='path8'></span>
                                                <span class='path9'></span>
                                                <span class='path10'></span>
                                            </i>
                                        </span>
                                        <span class='menu-title'>Light</span>
                                    </a>
                                </div>
                                <div class='menu-item px-3 my-0'>
                                    <a href='#' class='menu-link px-3 py-2' data-kt-element='mode' data-kt-value='dark'>
                                        <span class='menu-icon' data-kt-element='icon'>
                                            <i class='ki-duotone ki-moon fs-2'>
                                                <span class='path1'></span>
                                                <span class='path2'></span>
                                            </i>
                                        </span>
                                        <span class='menu-title'>Dark</span>
                                    </a>
                                </div>
                                <div class='menu-item px-3 my-0'>
                                    <a href='#' class='menu-link px-3 py-2' data-kt-element='mode'
                                       data-kt-value='system'>
												<span class='menu-icon' data-kt-element='icon'>
													<i class='ki-duotone ki-screen fs-2'>
														<span class='path1'></span>
														<span class='path2'></span>
														<span class='path3'></span>
														<span class='path4'></span>
													</i>
												</span>
                                        <span class='menu-title'>System</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php $APPLICATION->IncludeComponent(
                            'darneo.ozon_v3:user.info',
                            '',
                            [],
                            false
                        ); ?>
                        <div class='app-navbar-item d-lg-none ms-2 me-n2' title='Show header menu'>
                            <div class='btn btn-flex btn-icon btn-active-color-primary w-30px h-30px'
                                 id='kt_app_header_menu_toggle'>
                                <i class='ki-duotone ki-element-4 fs-1'>
                                    <span class='path1'></span>
                                    <span class='path2'></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='app-wrapper flex-column flex-row-fluid' id='kt_app_wrapper'>
            <div id='kt_app_sidebar' class='app-sidebar flex-column' data-kt-drawer='true'
                 data-kt-drawer-name='app-sidebar' data-kt-drawer-activate='{default: true, lg: false}'
                 data-kt-drawer-overlay='true' data-kt-drawer-width='225px' data-kt-drawer-direction='start'
                 data-kt-drawer-toggle='#kt_app_sidebar_mobile_toggle'>
                <div class='app-sidebar-logo px-6' id='kt_app_sidebar_logo'>
                    <a href='/ozon/'>
                        <img alt='Logo' src='<?= SITE_TEMPLATE_PATH ?>/assets/media/logos/default-dark.svg'
                             class='h-25px app-sidebar-logo-default'/>
                        <img alt='Logo' src='<?= SITE_TEMPLATE_PATH ?>/assets/media/logos/default-small.svg'
                             class='h-20px app-sidebar-logo-minimize'/>
                    </a>
                    <div id='kt_app_sidebar_toggle'
                         class='app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary body-bg h-30px w-30px position-absolute top-50 start-100 translate-middle rotate'
                         data-kt-toggle='true' data-kt-toggle-state='active' data-kt-toggle-target='body'
                         data-kt-toggle-name='app-sidebar-minimize'>
                        <i class='ki-duotone ki-double-left fs-2 rotate-180'>
                            <span class='path1'></span>
                            <span class='path2'></span>
                        </i>
                    </div>
                </div>
                <div class='app-sidebar-menu overflow-hidden flex-column-fluid'>
                    <div id='kt_app_sidebar_menu_wrapper' class='app-sidebar-wrapper hover-scroll-overlay-y my-5'
                         data-kt-scroll='true' data-kt-scroll-activate='true' data-kt-scroll-height='auto'
                         data-kt-scroll-dependencies='#kt_app_sidebar_logo, #kt_app_sidebar_footer'
                         data-kt-scroll-wrappers='#kt_app_sidebar_menu' data-kt-scroll-offset='5px'
                         data-kt-scroll-save-state='true'>
                        <div class='menu menu-column menu-rounded menu-sub-indention px-3' id='#kt_app_sidebar_menu'
                             data-kt-menu='true' data-kt-menu-expand='false'>
                            <?php $APPLICATION->IncludeComponent(
                                'bitrix:menu',
                                'left',
                                [
                                    'ALLOW_MULTI_SELECT' => 'N',
                                    'CHILD_MENU_TYPE' => 'section',
                                    'DELAY' => 'N',
                                    'MAX_LEVEL' => '3',
                                    'MENU_CACHE_GET_VARS' => '',
                                    'MENU_CACHE_TIME' => '36000000',
                                    'MENU_CACHE_TYPE' => 'A',
                                    'MENU_CACHE_USE_GROUPS' => 'Y',
                                    'ROOT_MENU_TYPE' => 'left',
                                    'USE_EXT' => 'N',
                                    'COMPONENT_TEMPLATE' => 'left',
                                ],
                                false
                            ); ?>
                        </div>
                    </div>
                </div>
                <div class='app-sidebar-footer flex-column-auto pt-2 pb-6 px-6' id='kt_app_sidebar_footer'>
                    <a href='https://ozon.darneo.ru' target='_blank'
                       class='btn btn-flex flex-center btn-custom btn-primary overflow-hidden text-nowrap px-0 h-40px w-100'
                       data-bs-toggle='tooltip' data-bs-trigger='hover' data-bs-dismiss-='click'>
                        <span class='btn-label'><?= Loc::getMessage('DARNEO_OZON_TEMPLATE_DOCS') ?></span>
                        <i class='ki-duotone ki-document btn-icon fs-2 m-0'>
                            <span class='path1'></span>
                            <span class='path2'></span>
                        </i>
                    </a>
                </div>
            </div>
            <div class='app-main flex-column flex-row-fluid' id='kt_app_main'>
                <div class='d-flex flex-column flex-column-fluid'>
                    <div id='kt_app_toolbar' class='app-toolbar py-3 py-lg-6'>
                        <div id='kt_app_toolbar_container' class='app-container container-fluid d-flex flex-stack'>
                            <div class='page-title d-flex flex-column justify-content-center flex-wrap me-3'>
                                <h1 class='page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0'>
                                    <?= $APPLICATION->ShowTitle(false) ?>
                                </h1>
                                <?php if ($APPLICATION->GetCurDir() !== '/ozon/'): ?>
                                    <?php $APPLICATION->IncludeComponent(
                                        'bitrix:breadcrumb',
                                        '',
                                        [
                                            'PATH' => '',
                                            'SITE_ID' => 's2',
                                            'START_FROM' => '0',
                                        ],
                                        false
                                    ); ?>
                                <?php endif; ?>
                            </div>
                            <div class='d-flex align-items-center gap-2 gap-lg-3'>
                                <?php $APPLICATION->ShowViewContent('title_right'); ?>
                            </div>
                        </div>
                    </div>
                    <div id='kt_app_content' class='app-content flex-column-fluid'>
                        <div id='kt_app_content_container' class='app-container container-fluid'>