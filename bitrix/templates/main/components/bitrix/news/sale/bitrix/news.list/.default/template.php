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

<div class="sale-list">
	<?foreach($arResult["ITEMS"] as $arItem):?>
	<div class="sale-list__item cl">
		<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="img">
			<img width="200" alt="<?echo $arItem["NAME"]?>" src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" height="200">
		</a>
		<div class="info">
			<div class="date">
				<?if($arParams["DISPLAY_DATE"]!="N" && $arItem["DISPLAY_ACTIVE_FROM"]):?>
					<?echo $arItem["DISPLAY_ACTIVE_FROM"]?>
				<?endif?>
			</div>
			<div class="name">
				<a href="<?=$arItem["DETAIL_PAGE_URL"]?>"><?echo $arItem["NAME"]?></a>
			</div>
			<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
				<?echo $arItem["PREVIEW_TEXT"];?>
			<?endif;?>
		</div>
	</div>
	<!--end::sale-list__item-->
	<?endforeach;?>

	<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
		<br /><?=$arResult["NAV_STRING"]?>
	<?endif;?>
</div>
<!--end::sale-list-->



