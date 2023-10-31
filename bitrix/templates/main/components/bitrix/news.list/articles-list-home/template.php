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

<div class="mp__blog">
	<div class="content__title"><?=$arParams["PAGER_TITLE"]?></div>
	<?foreach($arResult["ITEMS"] as $arItem):?>
	<div class="blogitem">
		<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="name"><?echo $arItem["NAME"]?></a>
		<p class="text">
			<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
				<?echo $arItem["PREVIEW_TEXT"];?>
			<?endif;?>
		</p>
	</div>
	<?endforeach;?>


	<a href="/articles/" class="content__link">Все <?=$arParams["PAGER_TITLE"]?></a>
</div><!--end::mp__blog-->




