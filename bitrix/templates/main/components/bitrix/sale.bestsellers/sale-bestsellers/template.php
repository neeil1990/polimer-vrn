<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */

$this->setFrameMode(true);
?>

<?if($arResult['ITEMS']):?>
<div class="slider_product" id="mp__product__popular">

	<? foreach($arResult['ITEMS'] as $item):?>
	<div>
		<div class="product">
			<a href="<?=$item['DETAIL_PAGE_URL']?>" style="display: block;height: 110px;">
				<img src="<?=resizeImage($item["PREVIEW_PICTURE"]["ID"], 150, 110)?>" alt="<?=$item['NAME']?>" height="110" style="max-height: 110px;margin: 0 auto;" class="img">
			</a>
			<a href="<?=$item['DETAIL_PAGE_URL']?>" class="name"><?=$item['NAME']?></a>
			<div class="price"><span><?=price($item['ID']);?></span> &#8381;/<?=$item['PROPERTIES']['CML2_BASE_UNIT']['VALUE'];?></div>

            <? if(checkProduct($item['ID'])): ?>
                <a href="javascript:void(0)" onclick="addToBasket2(<?=$item['ID']?>, 1,this);" class="cart">В корзину</a>
            <? else: ?>
                <a href="javascript:void(0)" class="cart show-popup" data-id="order-product">под заказ</a>
            <? endif; ?>
		</div>
	</div>
	<? endforeach; ?>

</div><!-- end::slider_product -->
<?endif;?>