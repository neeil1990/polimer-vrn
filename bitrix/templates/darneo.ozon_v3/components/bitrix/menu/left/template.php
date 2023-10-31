<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);
?>

<?php if (!empty($arResult)): ?>
    <?php
    foreach ($arResult as $key => $arItem):
        if ($arParams['MAX_LEVEL'] == 1 && $arItem['DEPTH_LEVEL'] > 1) {
            continue;
        }
        ?>
        <?php if ($arItem['PARAMS']['TITLE']): ?>
        <div class='menu-item pt-5'>
            <div class='menu-content'>
                <span class='menu-heading fw-bold text-uppercase fs-7'>
                    <?= $arItem['PARAMS']['TITLE'] ?>
                </span>
            </div>
        </div>
    <?php endif ?>
        <?php $active = $arItem['SELECTED'] ? ' active' : ''; ?>
        <?php if ($arItem['CHILDREN']): ?>
        <div data-kt-menu-trigger='click' class='menu-item here show menu-accordion'>
            <span class='menu-link <?= $active ?>'>
                <span class='menu-icon'><?= $arItem['PARAMS']['ICON'] ?></span>
                <span class='menu-title'><?= $arItem['TEXT'] ?></span>
                <span class='menu-arrow'></span>
            </span>
            <div class='menu-sub menu-sub-accordion'>
                <?php foreach ($arItem['CHILDREN'] as $child): ?>
                    <?php
                    $active = $child['SELECTED'] ? 'active' : '';
                    $link = $child['CHILDREN'] ? 'javascript:void(0)' : $child['LINK'];
                    ?>
                    <div class='menu-item'>
                        <a class='menu-link <?= $active ?>' href='<?= $link ?>'>
                            <span class='menu-bullet'>
                                <span class='bullet bullet-dot'></span>
                            </span>
                            <span class='menu-title'><?= $child['TEXT'] ?></span>
                        </a>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php else: ?>
        <div class='menu-item'>
            <a class='menu-link <?= $active ?>' href='<?= $arItem['LINK'] ?>'>
                <span class='menu-icon'>
                    <?= $arItem['PARAMS']['ICON'] ?>
                </span>
                <span class='menu-title'><?= $arItem['TEXT'] ?></span>
            </a>
        </div>
    <?php endif ?>
    <?php endforeach ?>
<?php endif ?>