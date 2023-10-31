<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

if (
    (is_array($arResult['BOTTOM_MENU_PROPERTIES']) && count($arResult['BOTTOM_MENU_PROPERTIES']) > 0) ||
    ($arParams['SHOW_BRAND_TAB'] == 'Y' && is_array($arResult['BRANDS_LIST']) && count($arResult['BRANDS_LIST']) > 0)
) { ?>
    <div class="seometa-menu">
        <div class="seometa-menu__buttons-wrapper">
            <?if(is_array($arResult['BOTTOM_MENU_PROPERTIES']) && count($arResult['BOTTOM_MENU_PROPERTIES']) > 0):?>
                <div class="seometa-menu__button seometa-menu__button_active"><?=$arParams['TAB_NAME']?></div>
            <?endif;?>
            <?if($arParams['SHOW_BRAND_TAB'] == 'Y' && $arResult['BRANDS_LIST']):?>
                <div class="seometa-menu__button"><?=$arParams['BRAND_TAB_NAME']?></div>
            <?endif;?>
        </div>

        <?if(is_array($arResult['BOTTOM_MENU_PROPERTIES']) && count($arResult['BOTTOM_MENU_PROPERTIES']) > 0) {?>
            <div class="seometa-menu__content" style="display: flex">
                <? foreach ($arResult['BOTTOM_MENU_PROPERTIES']['SECTION'] as $BOTTOM_MENU_PROPERTIES) { ?>
                    <div class="seometa-menu__section">
                        <span class="seometa-menu__name">
                            <? echo $BOTTOM_MENU_PROPERTIES['NAME']; ?>
                        </span>
                        <? foreach ($BOTTOM_MENU_PROPERTIES['LINKS'] as $LINK) { ?>
                            <a class="seometa-menu__link" href="<?php echo $LINK['URL']; ?>">
                                <? echo $LINK['NAME']; ?>
                            </a>
                        <? } ?>
                    </div>
                <? } ?>
            </div>
        <?}?>

        <?if($arParams['SHOW_BRAND_TAB'] == 'Y' && count($arResult['BRANDS_LIST']) > 0):?>
            <div class="seometa-menu__content"
                <?=!is_array($arResult['BOTTOM_MENU_PROPERTIES']) && count($arResult['BOTTOM_MENU_PROPERTIES']) == 0 ? 'style="display: flex"' : '' ?>
            >
                <div class="seometa-menu__section">
                    <? foreach ($arResult['BRANDS_LIST'] as $brand) { ?>
                        <a class="seometa-menu__link" href="<?php echo $brand['URL']; ?>">
                            <? echo $brand['NAME']; ?>
                        </a>
                    <? } ?>
                </div>
            </div>
        <?endif;?>
    </div>
<? } ?>

