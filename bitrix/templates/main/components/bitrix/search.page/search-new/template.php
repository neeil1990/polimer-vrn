<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<div class="row cl">

    <div class="ct__content">

        <?if(count($arResult["SEARCH"])>0):?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?> "<?=$arResult['REQUEST']['QUERY']?>" найдено <?=$arResult['ROWS_COUNT'];?> шт.</div>
        <? else:?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?></div>
        <?endif;?>


        <?if($arResult['SECTIONS']):?>
            <div class="h1">Категория</div>

            <div class="product_top cl">

                <div class="catalog_top cl">
                    <? foreach($arResult['SECTIONS'] as &$arSection):?>
                        <div class="item_c">
                            <a href="<?=$arSection['SECTION_PAGE_URL']?>">
                                <div class="img_c">
                                    <img src="<?=resizeImage($arSection['PICTURE'], 140, 120);?>" alt="<?=$arSection['NAME']?>">
                                </div>
                                <div class="name_c"><?=$arSection['NAME']?></div>
                            </a>
                        </div>
                    <? endforeach; ?>
                </div>

            </div>
            <!--end::catalog-sections-->
        <? endif; ?>

        <div class="h1">Товары</div>

        <div class="products_roll">
            <?if(count($arResult["SEARCH"])>0):?>

                <div class="pr_box cl">

                    <? foreach ($arResult['SEARCH'] as $key => $arItem): ?>

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
									  <img src="<?=resizeImage($arItem['PREVIEW_PICTURE'], 150, 150)?>" alt="<?=$arItem['NAME']?>">
								   </span>
                                    </a>
                                    <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="title"><?=$arItem['NAME']?></a>
                                    <div class="cost">
                                        <span><?=price($arItem['ID']);?></span> &#8381;/<?=$arItem['PROPERTY_CML2_BASE_UNIT_VALUE'];?>
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
                                        <span class="incode"></span>
                                        <div class="instock">Товар в наличии</div>
                                    <?else:?>
                                        <div class="cost_total"><span></span></div>
                                        <a href="javascript:void(0)" class="add2cartOrder show-popup" data-id="order-product">Товар под заказ</a>
                                        <div class="outstock">Товар под заказ</div>
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
                </div>

            <?else:?>
                <?ShowNote(GetMessage("SEARCH_NOTHING_TO_FOUND"));?>
            <?endif;?>
        </div>
        <!--end::products_roll-->
    </div>
    <div class="ct__mask"></div>
</div>

