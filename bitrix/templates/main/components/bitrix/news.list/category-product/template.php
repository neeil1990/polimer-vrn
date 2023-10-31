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


	<?foreach($arResult["ITEMS"] as $arItem):

	if($arItem['PROPERTIES']['PRODUCT']['VALUE'] and in_array($arParams['SECTION_NOW'], $arItem['PROPERTIES']['CATEGORY']['VALUE'])):
	?>
<div class="category-item-product" style="margin-bottom: 30px;">
	<div class="slider_product_show_all slider_product" id="mp__product__action">
		<?
		$arSelect = Array("IBLOCK_ID", "ID", "NAME","PREVIEW_PICTURE","DETAIL_PAGE_URL", "PROPERTY_CML2_BASE_UNIT");
		foreach($arItem['PROPERTIES']['PRODUCT']['VALUE'] as $id){
			$arFilter = Array("IBLOCK_ID"=> $arItem['PROPERTIES']['PRODUCT']['LINK_IBLOCK_ID'],"ID" => $id);
			$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
			if($ob = $res->GetNextElement()) {
				$arFields = $ob->GetFields();
				$arProps = $ob->GetProperties();
				?>
				<div>
					<div class="product">
						<a href="<?= $arFields["DETAIL_PAGE_URL"] ?>" style="display: block;height: 120px">
							<img src="<?= CFile::GetPath($arFields["PREVIEW_PICTURE"]); ?>"
								 alt="<?= $arFields["NAME"] ?>" style="max-height: 110px;margin: 0 auto;" class="img">
						</a>
						<a href="<?= $arFields["DETAIL_PAGE_URL"] ?>" class="name"><?= $arFields["NAME"] ?></a>

						<div class="price">
							<span><?= price($arFields['ID']); ?></span>
							&#8381;/<?= $arProps['CML2_BASE_UNIT']['VALUE']; ?>
						</div>
						<? if (!price($arFields['ID'])) {
							print '<span class="noprice" style="font-size: 11px;margin: 17px 0">Цену уточняйте у менеджера</span>';
						} ?>

						<? if ((float)price($arFields['ID'])): ?>
							<a href="javascript:void(0)" onclick="addToBasket2(<?= $arFields['ID'] ?>,1,this);"
							   class="cart">В корзину</a>
						<? else: ?>
							<a href="javascript:void(0)" class="cart show-popup" data-id="order-product">под заказ</a>
						<? endif; ?>
					</div>
				</div>

				<?
			}
			}

		?>

	</div><!-- end::slider_product -->
</div>
		<?
	endif;
	endforeach;
	?>







