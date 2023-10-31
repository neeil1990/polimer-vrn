<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
<div class="articles-list">
    <div class="h1">Другие <?=$arParams["PAGER_TITLE"]?></div>
    <div class="block cl">
        <?foreach($arResult["ITEMS"] as $arItem):?>
            <div class="item">
                <? if($arItem['PREVIEW_PICTURE']['SRC']): ?>
                    <div class="image">
                        <a href="<?=$arItem["DETAIL_PAGE_URL"]?>">
                            <img src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?echo $arItem["NAME"]?>">
                        </a>
                    </div>
                <? endif; ?>
                <div class="name">
                    <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="title"><?echo $arItem["NAME"]?></a>
                </div>

            </div>
        <?endforeach;?>
        <div class="clear"></div>
    </div>
    <a href="/<?=$arParams["LINK_TITLE"]?>/" class="archive">Все <?=$arParams["PAGER_TITLE"]?></a>
</div>



