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
<div class="mp__slider" id="mp__slider">
	<?foreach($arResult["ITEMS"] as $arItem):?>
	<div>
		<div class="item cl">
			<div class="slider__product">
				<img src="<?=resizeImage($arItem["PREVIEW_PICTURE"]["ID"], 380, 380)?>" alt="<?=$arItem['NAME']?>">
			</div>
			<div class="slider__content">
					<h2><?=$arItem['NAME']?></h2>
<!--				<img src="--><?//=$arItem["DETAIL_PICTURE"]["SRC"]?><!--" width="161" height="37" alt="--><?//=$arItem["PREVIEW_PICTURE"]["ALT"]?><!--" class="slider__img">-->
				<div class="slider__text">
					<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
						<?echo $arItem["PREVIEW_TEXT"];?>
					<?endif;?>
				</div>
				<a href="<?=$arItem["CODE"]?>" class="slider__link">Подробнее</a>
			</div>
		</div>
	</div>
	<?endforeach;?>
</div><!--end::mp__slider-->
