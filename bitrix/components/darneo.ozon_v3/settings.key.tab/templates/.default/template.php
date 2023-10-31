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
?>
<?php if ($arResult['DATA_VUE']['ITEMS']): ?>
    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement='bottom-start'
         class='menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2'>
        <?php
        $nameApi = '[---]';
        foreach ($arResult['DATA_VUE']['ITEMS'] as $item) {
            if (!$item['ACTIVE']) {
                continue;
            }
            $nameApi = $item['NAME'];
        } ?>
        <span class='menu-link'>
            <span class='menu-title'><?= $nameApi ?></span>
            <span class='menu-arrow d-lg-none'></span>
        </span>
        <? if ($arResult['DATA_VUE']['ITEMS']): ?>
            <div class='menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px'>
                <?php foreach ($arResult['DATA_VUE']['ITEMS'] as $item): ?>
                    <div class='menu-item'>
                        <a class='menu-link<?php if ($item['ACTIVE']): ?> active<?php endif; ?>'
                           href='<?= $item['LINK'] ?>'>
                            <span class='menu-title'><?= $item['NAME'] ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $url = explode('/', preg_replace('/(\?.*)/', '', $_SERVER['REQUEST_URI']));
    if (!in_array('settings', $url, true)) {
        LocalRedirect($arParams['SEF_FOLDER']);
        exit;
    }
    ?>
<?php endif ?>