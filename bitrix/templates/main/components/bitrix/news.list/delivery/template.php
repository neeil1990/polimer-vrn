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

<div class="sh__cities">
	<?foreach($arResult["ITEMS"] as $key => $arItem): ?>
	<a href="#" class="city <? if($key < 1): ?>active<? endif;?>"><?echo $arItem['PROPERTIES']['DELIVERY_MAP']['VALUE']?></a>
	<?endforeach;?>
</div><!--end::sh__cities-->

<div class="delivery__areas">
	<?foreach($arResult["ITEMS"] as $key => $arItem): ?>
	<div class="area-item <? if($key < 1): ?>active<? endif;?>">
		<div class="row cl">
			<div class="sh__text">
				<p class="intro"><?echo $arItem["NAME"]?></p>

				<? if($arItem['PROPERTIES']['PICKAP_PRODUCT']['VALUE'] OR $arItem['PROPERTIES']['ORDER_DELEVERY']['VALUE']): ?>
				<div class="type cl">
					<? if($arItem['PROPERTIES']['PICKAP_PRODUCT']['VALUE'] == "Y"): ?>
					<div class="shipby self">Самовывоз <br>товара</div>
					<?endif;?>

					<? if($arItem['PROPERTIES']['ORDER_DELEVERY']['VALUE'] == "Y"): ?>
					<div class="shipby us">Заказать <br>доставку</div>
					<?endif;?>
				</div>
				<?endif;?>

				<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"]):?>
					<?echo $arItem["PREVIEW_TEXT"];?>
				<?endif;?>

				<? if($arItem['PROPERTIES']['IMG_AUTO']['VALUE']): ?>
				<div class="cars cl">
					<? foreach($arItem['PROPERTIES']['IMG_AUTO']['VALUE'] as $key => $value):
					$desc =	explode('~',$arItem['PROPERTIES']['IMG_AUTO']['DESCRIPTION'][$key]);
						?>
					<div class="car">
						<img src="<?=CFile::GetPath($value);?>" alt="<?=$arItem['PROPERTIES']['IMG_AUTO']['NAME']?>" width="160" height="180">
						<div class="txt">
							<div class="model"><?=$desc[0]?></div>
							<div class="cost"><span><?=$desc[1]?></span>руб./час</div>
						</div>
					</div>
					<? endforeach; ?>
				</div>
				<?endif;?>

			</div>

			<div class="sh__visual">
				<? if($arItem['PROPERTIES']['COST_DELEVERY']['VALUE']): ?>
				<p class="intro"><?=$arItem['PROPERTIES']['TITLE_DELEVERY']['VALUE']?></p>
				<div class="area_cost cl">
					<? foreach($arItem['PROPERTIES']['COST_DELEVERY']['VALUE'] as $key => $value):?>
					<div class="area <?=$arItem['PROPERTIES']['COST_DELEVERY']['DESCRIPTION'][$key]?>"><span><?=$value?></span>руб.</div>
					<?endforeach;?>
				</div>
				<?endif;?>

				<? if($arItem['PREVIEW_PICTURE']['SRC']): ?>
				<div class="map">
					<span>
						<a href="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" data-fancybox>
							<img src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" width="560" height="610" alt="<?=$arItem['PREVIEW_PICTURE']['ALT']?>">
						</a>
					</span>
				</div>
				<? endif; ?>

			</div>
		</div>
	</div><!--end::area-->

	<?endforeach;?>

</div><!--end::delivery__areas-->


