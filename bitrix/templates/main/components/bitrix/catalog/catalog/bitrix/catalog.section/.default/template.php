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
if (!empty($arResult['ITEMS']))
{
?>



        <?if($arParams['SEO_TOP_DESC']):?>
            <div class="top-seo-description"><?=$arParams['SEO_TOP_DESC']?></div>
        <?endif;?>

        <div class="product_top">
            <? if(count($arResult['ITEM_SECTION_TOP']) > 0): ?>
            <div class="catalog_top cl">
                <? foreach($arResult['ITEM_SECTION_TOP'] as $arItem):?>
                <div class="item_c">
                    <a href="<?=$arItem['CODE']?>">
                        <div class="img_c">
                            <img src="<?=$arItem['PREVIEW_PICTURE']?>" alt="<?=$arItem['PROP']['TITLE']['VALUE']?>">
                        </div>
                        <div class="name_c"><?=$arItem['PROP']['TITLE']['VALUE']?></div>
                    </a>
                </div>
                <? endforeach; ?>
            </div>
            <? endif; ?>

            <? if(count($arResult['ITEM_FILTER_CATALOG']) > 0): ?>
            <div class="filter_top">
                <div class="header_f cl">
                    <div class="title_f">Популярные разделы</div>
                    <div class="close_f"></div>
                </div>
                <div class="body_f">
                    <? foreach($arResult['ITEM_FILTER_CATALOG'] as $arItem):?>
                    <div class="item_f">
                        <span class="name_f"><?=$arItem['PROP']['TITLE']['VALUE']?></span>
                        <? foreach ($arItem['PROP']['PARAM']['VALUE'] as $desc => $val):?>
                            <a href="<?=$arItem['PROP']['PARAM']['DESCRIPTION'][$desc]?>"><?=$val?></a>
                        <? endforeach; ?>
                    </div>
                    <? endforeach; ?>
                </div>
            </div>
            <? endif; ?>
        </div>

		<div class="products_roll">
			<? if($arResult['ORIGINAL_PARAMETERS']['SECTION_CODE'] == "garazhnye_vorota_doorhan"): ?>
			<div class="cl">
				<a href="/doorhan-calculator/">
					<img src="<?=SITE_TEMPLATE_PATH?>/img/doorhan.jpg" alt="Калькулятор Дорхан" style="max-width: 100%">
				</a>
			</div>
			<? endif; ?>

			<div class="pr_header cl">
				<div class="sort">
				<?
				$arSort = array(
                    'Популярности' => 'shows',
                    'Название А-Я' => 'NAME',
                    'Название Я-А' => 'NAME_DESC',
                    'Дата добавления' => 'timestamp_x',
                    'Наличию' => 'CATALOG_AVAILABLE',
                    'Цене' => 'catalog_PRICE_3',
                    );
				?>
					<label for="select_prh">Сортировать по:</label>
					<select name="select_prh" id="select_prh">
						<? foreach($arSort as $k => $v): ?>
							<option value="<?=$v?>" <? if($_REQUEST['ELEMENT_SORT_FIELD'] == $v):?>selected<?endif;?>><?=$k?></option>
						<? endforeach; ?>
					</select>
				</div>
				<div class="view">
					<a href="#" class="list">
						<div class="icon cl">
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
						</div>
						Список</a>
					<a href="#" class="tab active">
						<div class="icon cl">
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
							<span></span>
						</div>
						Таблица</a>
				</div>
				<?
				$arrSelect = array(20,40,80);
				if(empty($_REQUEST['PAGE_ELEMENT_COUNT'])){
					$_REQUEST['PAGE_ELEMENT_COUNT'] = 20;
				}
				?>
				<div class="quan">
					<label for="quan">Товаров на стр. :</label>
					<select name="quan" id="quan">
						<? foreach($arrSelect as $sel): ?>
						<option <? if($_REQUEST['PAGE_ELEMENT_COUNT'] == $sel):?>selected<?endif;?>><?=$sel?></option>
						<? endforeach; ?>
					</select>
				</div>
				<a href="#" class="filter" onclick="return false">
					<span>Фильтр</span>
					<span>Закрыть</span>
				</a>
			</div>

			<div class="pr_box cl">

				<? foreach ($arResult['ITEMS'] as $key => $arItem): ?>

					<div class="item" id="product_<?=$arItem['ID']?>">
						<div class="hover">
							<div class="inner">
								<div class="compare">
									<label>
										<input type="checkbox" id-cat="<?=$arItem['IBLOCK_SECTION_ID']?>" value="<?=$arItem['ID']?>">
										<span>Сравнить</span>
									</label>
								</div>
								<div class="close"></div>
								<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="pic">
								   <span>
									  <img src="<?=resizeImage($arItem['PREVIEW_PICTURE']['ID'], 150, 150)?>" alt="<?=$arItem['NAME']?>">
								   </span>
								</a>
								<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="title"><?=$arItem['NAME']?></a>
								<div class="cost">
									<span><?=price($arItem['ID']);?></span> &#8381;/<?=$arItem['PROPERTIES']['CML2_BASE_UNIT']['VALUE'];?>
								</div>
								<?if(!price($arItem['ID'])){
									print '<span class="noprice">Цену уточняйте у менеджера</span>';
								}
								?>


								<? if($arItem['CATALOG_QUANTITY'] > 0 and (float)price($arItem['ID'])): ?>
								<div class="quantity" id="count_<?=$arItem['ID']?>">
									<a class="minus na" href="#"></a>
									<input type="text" value="1"/>
									<a class="plus" href="#"></a>
								</div>
									<script>

											$('#count_<?=$arItem['ID']?> > .minus').click(function(){
												var count_val = $(this).parent().find('input').val();
												if(count_val < 2){
													$(this).addClass('na');
													$(this).parent().find('input').val(1);
												}else{
													var val = parseInt($(this).parent().find('input').val()) - 1;
													var cost = parseFloat($('#product_<?=$arItem['ID']?> .cost > span').text());
													var cost_total = cost*val;
													$('#product_<?=$arItem['ID']?> .cost_total > span').text(cost_total.toFixed(2));
													$(this).parent().find('input').val(val);
													$(this).parent().find('.plus').removeClass('na');

												}
												return false;
											});
											$('#count_<?=$arItem['ID']?> > .plus').click(function(){
												var count_val = $(this).parent().find('input').val();

												if(count_val < <?=$arItem['CATALOG_QUANTITY']?>){
													var val = parseInt($(this).parent().find('input').val()) + 1;
													var cost = parseFloat($('#product_<?=$arItem['ID']?> .cost > span').text());
													var cost_total = cost*val;
													$('#product_<?=$arItem['ID']?> .cost_total > span').text(cost_total.toFixed(2));
													$(this).parent().find('input').val(val);
													$(this).parent().find('.minus').removeClass('na');
												}else{
													$(this).addClass('na');
													$(this).parent().find('input').val(<?=$arItem['CATALOG_QUANTITY']?>);
												}
												return false;
											});



									</script>
								<div class="cost_total"><span><?=price($arItem['ID'])?></span> &#8381;</div>
								<a href="javascript:void(0)" class="add2cart">
									<span class="txt1" onclick="if(document.body.clientWidth < 659){addToBasket2(<?=$arItem['ID']?>, $('#count_<?=$arItem['ID']?> input').val(),this)};">В корзину</span>
									<span class="txt2" onclick="addToBasket2(<?=$arItem['ID']?>, $('#count_<?=$arItem['ID']?> input').val(),this);">Добавить в корзину</span>
								</a>
                                <span class="incode">Код товара: <?=$arItem['PROPERTIES']['CML2_TRAITS']['VALUE'][2];?></span>
								<div class="instock">Товар в наличии</div>
								<?else:?>
								<div class="cost_total"><span></span></div>
								<a href="javascript:void(0)" class="add2cartOrder show-popup" data-id="order-product">Купить под заказ</a>
								<div class="outstock"></div>
								<? endif; ?>

							</div>
						</div>
					</div>

				<? endforeach; ?>

			</div>

			<div class="pr_footer cl">
				<?
				if ($arParams["DISPLAY_BOTTOM_PAGER"])
				{
					?><? echo $arResult["NAV_STRING"]; ?><?
				}
				?>

				<div class="quan_b">
					<label for="quan_b">Товаров на стр. :</label>
					<select name="quan" id="quan_b">
						<? foreach($arrSelect as $sel): ?>
							<option <? if($_REQUEST['PAGE_ELEMENT_COUNT'] == $sel):?>selected<?endif;?>><?=$sel?></option>
						<? endforeach; ?>
					</select>
				</div>
			</div>
		</div><!--end::products_roll-->

		<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.products.viewed",
	"products-viewed",
	array(
		"ACTION_VARIABLE" => "action_cpv",
		"ADDITIONAL_PICT_PROP_10" => "-",
		"ADDITIONAL_PICT_PROP_11" => "-",
		"ADDITIONAL_PICT_PROP_12" => "-",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_TO_BASKET_ACTION" => "ADD",
		"BASKET_URL" => "/personal/basket.php",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"CART_PROPERTIES_10" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_11" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_12" => array(
			0 => "",
			1 => "",
		),
		"CONVERT_CURRENCY" => "N",
		"DEPTH" => "2",
		"DISPLAY_COMPARE" => "N",
		"ENLARGE_PRODUCT" => "STRICT",
		"HIDE_NOT_AVAILABLE" => "N",
		"HIDE_NOT_AVAILABLE_OFFERS" => "N",
		"IBLOCK_ID" => "21",
		"IBLOCK_MODE" => "single",
		"IBLOCK_TYPE" => "1c_catalog",
		"LABEL_PROP_10" => "",
		"LABEL_PROP_11" => "",
		"LABEL_PROP_POSITION" => "top-left",
		"MESS_BTN_ADD_TO_BASKET" => "Р’ РєРѕСЂР·РёРЅСѓ",
		"MESS_BTN_BUY" => "РљСѓРїРёС‚СЊ",
		"MESS_BTN_DETAIL" => "РџРѕРґСЂРѕР±РЅРµРµ",
		"MESS_BTN_SUBSCRIBE" => "РџРѕРґРїРёСЃР°С‚СЊСЃСЏ",
		"MESS_NOT_AVAILABLE" => "РќРµС‚ РІ РЅР°Р»РёС‡РёРё",
		"OFFER_TREE_PROPS_12" => "",
		"PAGE_ELEMENT_COUNT" => "9",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PRICE_CODE" => array(
		),
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons,compare",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
		"PRODUCT_SUBSCRIPTION" => "Y",
		"PROPERTY_CODE_10" => array(
			0 => "",
			1 => "",
		),
		"PROPERTY_CODE_11" => array(
			0 => "",
			1 => "",
		),
		"PROPERTY_CODE_12" => array(
			0 => "",
			1 => "",
		),
		"PROPERTY_CODE_MOBILE_11" => "",
		"SECTION_CODE" => "",
		"SECTION_ELEMENT_CODE" => "",
		"SECTION_ELEMENT_ID" => $GLOBALS["CATALOG_CURRENT_ELEMENT_ID"],
		"SECTION_ID" => $GLOBALS["CATALOG_CURRENT_SECTION_ID"],
		"SHOW_CLOSE_POPUP" => "N",
		"SHOW_DISCOUNT_PERCENT" => "N",
		"SHOW_FROM_SECTION" => "N",
		"SHOW_MAX_QUANTITY" => "N",
		"SHOW_OLD_PRICE" => "N",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_PRODUCTS_10" => "N",
		"SHOW_PRODUCTS_11" => "N",
		"SHOW_SLIDER" => "N",
		"SLIDER_INTERVAL" => "3000",
		"SLIDER_PROGRESS" => "N",
		"TEMPLATE_THEME" => "blue",
		"USE_ENHANCED_ECOMMERCE" => "N",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "N",
		"COMPONENT_TEMPLATE" => "products-viewed",
		"SET_VIEWED_IN_COMPONENT" => "Y",
		"PROPERTY_CODE_21" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_21" => array(
			0 => "",
			1 => "",
		),
		"ADDITIONAL_PICT_PROP_21" => "-",
		"LABEL_PROP_21" => array(
		),
		"PROPERTY_CODE_22" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_22" => array(
			0 => "",
			1 => "",
		),
		"ADDITIONAL_PICT_PROP_22" => "-",
		"OFFER_TREE_PROPS_22" => array(
		)
	),
	false
);?>

		<div class="related_articles cl">
			<div class="col-txt">
				<div class="catalog-sections-text-hidden">
				<?=($arParams['SEO_BOTTOM_DESC']) ? $arParams['SEO_BOTTOM_DESC'] : htmlspecialchars_decode($arParams['PARENT_DESC'])?>

                <?
                $APPLICATION->IncludeFile("/include/table/" .$arResult['CODE']. ".php", Array(), Array(
                    "MODE"      => "html",
                    "NAME"      => "Редактирование",
                    "TEMPLATE"  => ""
                ));
                ?>
				</div>
			</div>
			<div class="col-articles">
				<?$APPLICATION->IncludeComponent(
					"bitrix:news.list",
					"articles-list-catalog",
					array(
						"ACTIVE_DATE_FORMAT" => "j F Y",
						"ADD_SECTIONS_CHAIN" => "N",
						"AJAX_MODE" => "N",
						"AJAX_OPTION_ADDITIONAL" => "",
						"AJAX_OPTION_HISTORY" => "N",
						"AJAX_OPTION_JUMP" => "N",
						"AJAX_OPTION_STYLE" => "Y",
						"CACHE_FILTER" => "N",
						"CACHE_GROUPS" => "Y",
						"CACHE_TIME" => "36000000",
						"CACHE_TYPE" => "A",
						"CHECK_DATES" => "Y",
						"DETAIL_URL" => "",
						"DISPLAY_BOTTOM_PAGER" => "Y",
						"DISPLAY_DATE" => "Y",
						"DISPLAY_NAME" => "Y",
						"DISPLAY_PICTURE" => "Y",
						"DISPLAY_PREVIEW_TEXT" => "Y",
						"DISPLAY_TOP_PAGER" => "N",
						"FIELD_CODE" => array(
							0 => "",
							1 => "",
						),
						"FILTER_NAME" => "",
						"HIDE_LINK_WHEN_NO_DETAIL" => "N",
						"IBLOCK_ID" => "4",
						"IBLOCK_TYPE" => "news",
						"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
						"INCLUDE_SUBSECTIONS" => "Y",
						"MESSAGE_404" => "",
						"NEWS_COUNT" => "2",
						"PAGER_BASE_LINK_ENABLE" => "N",
						"PAGER_DESC_NUMBERING" => "N",
						"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
						"PAGER_SHOW_ALL" => "N",
						"PAGER_SHOW_ALWAYS" => "N",
						"PAGER_TEMPLATE" => ".default",
						"PAGER_TITLE" => "статьи",
						"LINK_TITLE" => "articles",
						"PARENT_SECTION" => "",
						"PARENT_SECTION_CODE" => "",
						"PREVIEW_TRUNCATE_LEN" => "50",
						"PROPERTY_CODE" => array(
							0 => "",
							1 => "",
						),
						"SET_BROWSER_TITLE" => "N",
						"SET_LAST_MODIFIED" => "N",
						"SET_META_DESCRIPTION" => "N",
						"SET_META_KEYWORDS" => "N",
						"SET_STATUS_404" => "N",
						"SET_TITLE" => "N",
						"SHOW_404" => "N",
						"SORT_BY1" => "ACTIVE_FROM",
						"SORT_BY2" => "SORT",
						"SORT_ORDER1" => "DESC",
						"SORT_ORDER2" => "ASC",
						"COMPONENT_TEMPLATE" => "articles-list-home"
					),
					false
				);?>

			</div>
		</div>




<?}?>
