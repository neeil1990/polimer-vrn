<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */

$this->setFrameMode(true);
?>
<? if (isset($arResult['ITEMS']) && !empty($arResult['ITEMS'])): ?>
<div class="pc__prod-also">
	<div class="pc__vertical-carousel">
		<div class="vc-shell">
            <div class="vc-title">С этим товаром покупают</div>
            <div class="vc-block">
			<?
			foreach ($arResult['ITEMS'] as $key => $arItem)
			{
				?>
				<div>
					<div class="item cl">
						<a href="<?=$arItem['DETAIL_PAGE_URL']?>"><span><img src="<?=resizeImage($arItem['PREVIEW_PICTURE']['ID'], 150, 150)?>" alt="<?=$arItem['NAME']?>"></span></a>
						<div class="cost"><span><?=price($arItem['ID']);?></span> &#8381;/<?=$arItem['PROPERTIES']['CML2_BASE_UNIT']['VALUE'];?></div>
						<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="txt"><?=$arItem['NAME']?></a>

                        <? if(checkProduct($arItem['ID'])): ?>
                            <a href="javascript:void(0)" onclick="addToBasket2(<?=$arItem['ID']?>,1,this);" class="add2cart">В корзину</a>
                        <? else: ?>
                            <a href="javascript:void(0)" class="cart show-popup" data-id="order-product">под заказ</a>
                        <? endif; ?>
					</div>
				</div>
				<?
			}
?>

			</div>
			<div class="vc-ctrl"></div>
		</div>
	</div>
</div><!--end::pc__prod-also-->
	<script type="text/javascript">
		BX.message({
			MESS_BTN_BUY: '<? echo ('' != $arParams['MESS_BTN_BUY'] ? CUtil::JSEscape($arParams['MESS_BTN_BUY']) : GetMessageJS('SRP_TPL_MESS_BTN_BUY')); ?>',
			MESS_BTN_ADD_TO_BASKET: '<? echo ('' != $arParams['MESS_BTN_ADD_TO_BASKET'] ? CUtil::JSEscape($arParams['MESS_BTN_ADD_TO_BASKET']) : GetMessageJS('SRP_TPL_MESS_BTN_ADD_TO_BASKET')); ?>',

			MESS_BTN_DETAIL: '<? echo ('' != $arParams['MESS_BTN_DETAIL'] ? CUtil::JSEscape($arParams['MESS_BTN_DETAIL']) : GetMessageJS('SRP_TPL_MESS_BTN_DETAIL')); ?>',

			MESS_NOT_AVAILABLE: '<? echo ('' != $arParams['MESS_BTN_DETAIL'] ? CUtil::JSEscape($arParams['MESS_BTN_DETAIL']) : GetMessageJS('SRP_TPL_MESS_BTN_DETAIL')); ?>',
			BTN_MESSAGE_BASKET_REDIRECT: '<? echo GetMessageJS('SRP_CATALOG_BTN_MESSAGE_BASKET_REDIRECT'); ?>',
			BASKET_URL: '<? echo $arParams["BASKET_URL"]; ?>',
			ADD_TO_BASKET_OK: '<? echo GetMessageJS('SRP_ADD_TO_BASKET_OK'); ?>',
			TITLE_ERROR: '<? echo GetMessageJS('SRP_CATALOG_TITLE_ERROR') ?>',
			TITLE_BASKET_PROPS: '<? echo GetMessageJS('SRP_CATALOG_TITLE_BASKET_PROPS') ?>',
			TITLE_SUCCESSFUL: '<? echo GetMessageJS('SRP_ADD_TO_BASKET_OK'); ?>',
			BASKET_UNKNOWN_ERROR: '<? echo GetMessageJS('SRP_CATALOG_BASKET_UNKNOWN_ERROR') ?>',
			BTN_MESSAGE_SEND_PROPS: '<? echo GetMessageJS('SRP_CATALOG_BTN_MESSAGE_SEND_PROPS'); ?>',
			BTN_MESSAGE_CLOSE: '<? echo GetMessageJS('SRP_CATALOG_BTN_MESSAGE_CLOSE') ?>'
		});
	</script>

<? endif ?>
