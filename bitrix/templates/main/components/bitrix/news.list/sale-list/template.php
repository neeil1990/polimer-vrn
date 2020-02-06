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
<div class="nd__rightbar">
	<div class="h1">Другие <?=$arParams["PAGER_TITLE"]?></div>
	<div class="block cl">
		<?foreach($arResult["ITEMS"] as $arItem):?>
		<div class="item">
            <div class="image">
                <img src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?echo $arItem["NAME"]?>">
            </div>
			<div class="date">
				<?if($arParams["DISPLAY_DATE"]!="N" && $arItem["DISPLAY_ACTIVE_FROM"]):?>
					<?echo $arItem["DISPLAY_ACTIVE_FROM"]?>
				<?endif?>
			</div>
			<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="title"><?echo $arItem["NAME"]?></a>
			<div class="txt">
				<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
					<?echo $arItem["PREVIEW_TEXT"];?>
				<?endif;?>
			</div>
		</div>
		<?endforeach;?>
	</div>
	<a href="/<?=$arParams["LINK_TITLE"]?>/" class="archive">Все <?=$arParams["PAGER_TITLE"]?></a>
</div>

