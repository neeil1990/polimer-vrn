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
<div class="mp__news cl">
	<div class="content__title"><?=$arParams["PAGER_TITLE"]?></div>

	<?foreach($arResult["ITEMS"] as $arItem):?>
	<div class="newsitem cl">
		<div class="date">
			<?if($arParams["DISPLAY_DATE"]!="N" && $arItem["DISPLAY_ACTIVE_FROM"]):?>
				<?echo $arItem["DISPLAY_ACTIVE_FROM"]?>
			<?endif?>
		</div>
		<div class="info">
			<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="name"><?echo $arItem["NAME"]?></a>
			<p class="text">
				<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
					<?echo $arItem["PREVIEW_TEXT"];?>
				<?endif;?>
			</p>
		</div>
	</div>
	<?endforeach;?>



	<a href="/<?=$arParams["LINK_TITLE"]?>/" class="content__link">Архив <?=$arParams["PAGER_TITLE"]?></a>
</div><!--end::mp__news-->



