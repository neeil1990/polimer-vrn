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
//banner__garant
?>

<div class="wp__banner cl">

	<?foreach($arResult["ITEMS"] as $key => $arItem):?>
	<a href="<?=$arItem['CODE']?>" class="banner__sale <?if($key == 1)print 'garant'?> cl">
		<div class="banner__sale__img">
			<img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" alt="<?echo $arItem["NAME"]?>" width="129" height="125">
		</div>
		<div class="banner__sale__text">
			<div class="banner__sale__title"><?echo $arItem["NAME"]?></div>
			<div class="banner__sale__subtitle">
				<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
					<?echo $arItem["PREVIEW_TEXT"];?>
				<?endif;?>
			</div>
		</div>
	</a>
	<?endforeach;?>

</div><!--end::wp__banner-->


