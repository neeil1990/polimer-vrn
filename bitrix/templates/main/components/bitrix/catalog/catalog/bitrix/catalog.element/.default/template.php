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
<script type="text/javascript">
    var viewedCounter = {
        path: '/bitrix/components/bitrix/catalog.element/ajax.php',
        params: {
            AJAX: 'Y',
            SITE_ID: "<?= SITE_ID ?>",
            PRODUCT_ID: "<?= $arResult['ID'] ?>",
            PARENT_ID: "<?= $arResult['ID'] ?>"
        }
    };
    BX.ready(
        BX.defer(function(){
            BX.ajax.post(
                viewedCounter.path,
                viewedCounter.params
            );
        })
    );
</script>

<div class="prod_card cl">
   <div class="pc__prod-info">

       <div class="cl">
           <div class="bb_col">
               <h1><?=$arResult['NAME']?></h1>
           </div>
           <? if($arResult['BRAND']): ?>
           <div class="bb_col right">
               <a href="/brands/<?=$arResult['BRAND']['URL']?>/" target="_blank">
                   <img src="<?=$arResult['BRAND']['IMAGE']?>" alt="<?=$arResult['NAME']?>" style="max-height:50px">
               </a>
           </div>
           <? endif; ?>
       </div>

       <div class="cl mb-40">
           <div class="bb_col">
               <span>Код товара: <?=$arResult['PROPERTIES']['CML2_TRAITS']['VALUE'][2];?></span>
           </div>
           <!--<div class="bb_col">
               <span><a href="#" class="blue">Бесплатная доставка</a></span>
           </div>-->
           <? if($arResult['ITEM_PRICES']): ?>
               <? foreach($arResult['ITEM_PRICES'] as $name => $price):
                   if(!$price['PERCENT'])
                       continue;
                   ?>
                   <div class="bb_col" >
                       <span><a href="#" class="red" title='<b>Скидка <?=$price['PERCENT']?>%</b> При заказе через интернет-магазин'>Скидка <?=$price['PERCENT']?>% онлайн</a></span>
                   </div>
               <? endforeach; ?>
           <? endif; ?>
			<div class="bb_col">
               <span><a href="#" class="blue" title="<ul><li>Доставим бесплатно заказы стоимостью выше 5000 р. по городу*.</li><li>Предложение действует при заказе и оплате на сайте.</li><li>Максимальный габарит товара 2м и вес не более 1 тн.</li><li>Доставка осуществляется в течение 1-5 рабочих дней после заказа.</li><li>Для получения товара необходимо при себе иметь паспорт или иной документ, удостоверяющий личность.</li><li>Подъем на этаж не осуществляется.</li><li>Условия действуют по г. Воронеж.</li></ul>">Доставим бесплатно заказы стоимостью выше 5000 р. по городу*.</a></span>
           </div>
           <div class="bb_col right">
               <? if($arResult['RATING']['COUNT']): ?>
               <div class="rates" id="rate_main_result"></div>
               <script type="text/javascript">
                   $(function() {
                       $('#rate_main_result').rateit({ value: <?=$arResult['RATING']['STARS']?>, ispreset: true, readonly: true });
                   });
               </script>
                   <span style="vertical-align: top;border-bottom: 1px dotted;"><?=$arResult['RATING']['COUNT']?>
                       <a href="#all_tabs" class="open_tab" for="tab_fed" style="border: none;padding: unset;color: gray;">Отзыв'а</a>
                   </span>
               <? endif; ?>
           </div>
       </div>


      <div class="cl mb-40">

         <div class="pc__prod-gallery cl">
             <ul id="imageGallery">
                 <? foreach($arResult['GALLERY'] as $key => $img): ?>
                     <li data-thumb="<?=$img['THUMB']?>" data-src="<?=$img['SRC']?>" data-alt="<?=$arResult['NAME']?>">
                         <img src="<?=$img['DATA']?>"  alt="<?=$arResult['NAME']?>"/>
                     </li>
                 <? endforeach; ?>
             </ul>
             <div class="GallerylSAction">
                 <a class="GallerylSPrev"></a>
                 <a class="GallerylSNext"></a>
             </div>
         </div>

         <div class="pc__buy-block cl">

            <? if(empty($arResult['ITEM_PRICES'])): ?>
                <div class="bb_row">
                    <div class="h1">Цену уточняйте у менеджера</div>
                </div>
            <? else: ?>
                <div class="bb_row">
                   <div class="price">
                      <? foreach($arResult['ITEM_PRICES'] as $name => $price):?>
                          <div class="price-new"><span><?=str_replace("&#8381;","",$price['BASE_PRICE'])?></span>  &#8381;/<?=$arResult['PROPERTIES']['CML2_BASE_UNIT']['VALUE'];?></div>
                        <?if(false):?>
                          <div class="price-old"><span><?=$price['BASE_PRICE']?> &#8381;</span></div>
                        <? endif; ?>

                      <? endforeach; ?>

                       <? if($arResult['WHOLESALE']): ?>
                           <div class="price-old"><span><?=$arResult['WHOLESALE']['PRICE']?></span></div>
                            <? if($arResult['WHOLESALE']['PERCENT']): ?>
                                <div class="sale-y"><span>- <?=$arResult['WHOLESALE']['PERCENT'];?>% в корзине</span></div>
                            <? endif; ?>
                       <? endif; ?>

                       <? foreach($arResult['ITEM_PRICES'] as $name => $price):
                           if(!$price['PERCENT'])
                               continue;
                           ?>
                       <div class="sale-y"><span>- <?=$price['PERCENT']?>% в корзине</span></div>
                       <? endforeach; ?>
                   </div>
                </div>
            <? endif; ?>

            <? if($arResult['CATALOG_QUANTITY'] > 0 and $arResult['ITEM_PRICES'][0]['BASE_PRICE']): ?>
            <a href="javascript:void(0)" class="add2cart" onclick="addToBasket2(<?=$arResult['ID']?>, $('#count_product').val(),this);">Добавить в корзину</a>
            <a href="#" class="bb_btn show-popup" data-id="oneclick"><span>Купить в один клик</span></a>
            <?else:?>
            <a href="javascript:void(0)" class="add2cart show-popup" data-id="order-product">Товар под заказ</a>
            <?endif;?>

            <a href="#" class="bb_btn spec_help show-popup" data-id="specialist"><span>Помощь специалиста</span></a>

             <div class="bb_compare">
                 <a href="#" id="compare" data-id="<?=$arResult['IBLOCK_SECTION_ID']?>" data-value="<?=$arResult['ID']?>">
                     <i class="fa fa-bar-chart" aria-hidden="true"></i>
                     <span>Сравнить</span>
                 </a>
             </div>

             <div class="bb_info">
                 <? if($arResult['ITEM_PRICES']): ?>
                 <div class="i_rows">
                     <i class="fa fa-check-square-o" aria-hidden="true"></i>
                     <span>
                         В наличии -
                         <a href="#all_tabs" class="open_tab" for="tab_nal">посмотреть</a>
                     </span>
                 </div>
                 <? endif; ?>
                 <div class="i_rows">
                     <i class="fa fa-truck" aria-hidden="true"></i>
                     <span>
                         <a href="/delivery/" target="_blank">Информация о доставке</a>
                     </span>
                 </div>
                 <? if($arResult['PROPERTIES']['GARANTIYA']['VALUE']): ?>
                 <div class="i_rows">
                     <i class="fa fa-shield" aria-hidden="true"></i>
                     <span>
                         Гарантия производителя - <?=$arResult['PROPERTIES']['GARANTIYA']['VALUE'];?>
                     </span>
                 </div>
                 <? endif; ?>

                 <? if($arResult['PROPERTIES']['STRANA_PROIZVODITELYA']['VALUE']): ?>
                 <div class="i_rows">
                     <i class="fa fa-flag-o" aria-hidden="true"></i>
                     <span>
                         Страна производителя - <?=$arResult['PROPERTIES']['STRANA_PROIZVODITELYA']['VALUE']?>
                     </span>
                 </div>
                 <? endif; ?>
             </div>

         </div>

      </div>



      <div class="pc__tabs" id="all_tabs">
         <div class="t-list cl">
            <a href="#"><span>Описание</span></a>
            <a href="#"><span>Технические характеристики</span></a>
            <a href="#"><span>Отзывы </span></a>
            <a href="#" class="active"><span>Наличие в магазинах</span></a>
         </div>
         <div class="t-content">
            <div class="tab tab_des">
               <a href="#" class="mtb" onclick="return false">Описание</a>
               <div class="content">
                   <div class="cl">
                       <div class="bb_col w-70">
                           <?=$arResult['DETAIL_TEXT']?>
                           <? foreach($arResult['PROPERTIES']['FILES']['VALUE'] as $key => $file): ?>
                               <p><a class="download" href="<?=CFile::GetPath($file);?>"><?=$arResult['PROPERTIES']['FILES']['DESCRIPTION'][$key];?></a></p>
                           <? endforeach; ?>
                       </div>
                       <div class="bb_col w-30"><? include('inc/list.php'); ?></div>
                   </div>
               </div>
            </div>
            <div class="tab tab_tec">
               <a href="#" class="mtb" onclick="return false">Технические характеристики</a>
               <div class="content">
                   <div class="cl">
                       <div class="bb_col w-70">
                           <?
                           if (!empty($arResult['PROPERTIES']))
                           {
                               foreach($arResult['PROPERTIES'] as $property){
                                   if(
                                       strlen($property['VALUE']) > 1 AND
                                       $property['MULTIPLE'] == "N" AND
                                       $property['CODE'] != "CML2_BASE_UNIT" AND
									   $property['CODE'] != "_1_VYGRUZKA_MARKETY" AND
                                       $property['CODE'] != "MORE_PHOTO" AND
                                       $property['CODE'] != "YANDEKS_MARKET_PREDOPLATA" AND
									   $property['CODE'] != "_4_TEST" AND
                                       ($property['PROPERTY_TYPE'] == "S" OR
                                           $property['PROPERTY_TYPE'] == "L")
                                   ){?>
                                       <div class="line cl">
                                           <div class="prop"><?=$property['NAME']?></div>
                                           <div class="val"><?=($property['CODE'] == 'PROIZVODITEL') ? print_url('/brands/'.$arResult[BRAND][URL].'/', $property['VALUE']) : $property['VALUE']?></div>
                                       </div>
                                       <?
                                   }
                               }
                           }
                           ?>
                       </div>
                       <div class="bb_col w-30">
                           <? include('inc/list.php'); ?>
                       </div>
                   </div>
               </div>
            </div>
            <div class="tab tab_fed">
               <a href="#" class="mtb" onclick="return false">Отзывы</a>
               <div class="content">
                   <div class="cl">
                       <div class="bb_col w-70">
                           <?$APPLICATION->IncludeComponent("khayr:main.comment", "catalog.comment", Array(
                               "OBJECT_ID" => $arResult["ID"],	// ID объекта комментирования
                               "COUNT" => "10",	// Количество комментариев на странице
                               "MAX_DEPTH" => "1",	// Максимальный уровень вложенности
                               "JQUERY" => "N",	// Подключить jQuery (если не подключен)
                               "MODERATE" => "N",	// Включить премодерацию
                               "LEGAL" => "N",	// Требовать согласиться с правилами
                               "LEGAL_TEXT" => "Я согласен с правилами размещения сообщений на сайте.",	// Текст галочки о согласии с правилами
                               "CAN_MODIFY" => "N",	// Разрешить редактирование комментария
                               "NON_AUTHORIZED_USER_CAN_COMMENT" => "Y",	// Разрешить неавторизованным пользователям добавлять комментарии
                               "REQUIRE_EMAIL" => "Y",	// Требовать e-mail
                               "USE_CAPTCHA" => "Y",	// Показывать CAPTCHA неавторизованным пользователям
                               "AUTH_PATH" => "/personal/",	// Путь до страницы авторизации
                               "ACTIVE_DATE_FORMAT" => "j F Y, G:i",	// Формат показа даты

                               "LOAD_MARK" => "Y",	// Разрешить оценивать
                               "LOAD_DIGNITY" => "Y",	// Разрешить Достоинства
                               "LOAD_FAULT" => "Y",	// Разрешить Недостатки
                               "ADDITIONAL" => array(	// Дополнительные свойства
                                   0 => "Опыт использования",
                               ),
                               "ALLOW_RATING" => "N",	// Включить рейтинг
                               "DISPLAY_TOP_PAGER" => "N",	// Выводить над списком
                               "DISPLAY_BOTTOM_PAGER" => "Y",	// Выводить под списком
                               "PAGER_TITLE" => "",	// Название категорий
                               "PAGER_SHOW_ALWAYS" => "N",	// Выводить всегда
                               "PAGER_TEMPLATE" => ".default",	// Шаблон постраничной навигации
                               "PAGER_DESC_NUMBERING" => "N",	// Использовать обратную навигацию
                               "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Время кеширования страниц для обратной навигации
                               "PAGER_SHOW_ALL" => "N",	// Показывать ссылку "Все"
                               "COMPONENT_TEMPLATE" => ".default"
                           ),
                               false
                           );?>
                       </div>
                       <div class="bb_col w-30"><? include('inc/list.php'); ?></div>
                   </div>
               </div>
            </div>
            <div class="tab tab_nal active">
               <a href="#" class="mtb" onclick="return false">Наличие в магазинах</a>
               <div class="content">
                   <div class="cl">
                       <div class="bb_col w-70">
                           <?$APPLICATION->IncludeComponent(
                               "nbrains:catalog.store.amount",
                               "store",
                               array(
                                   "CACHE_TIME" => "36000",
                                   "CACHE_TYPE" => "N",
                                   "ELEMENT_CODE" => "",
                                   "ELEMENT_ID" => $arResult["ID"],
                                   "FIELDS" => array(
                                       0 => "TITLE",
                                       1 => "ADDRESS",
                                       2 => "DESCRIPTION",
                                       3 => "PHONE",
                                       4 => "EMAIL",
                                       5 => "IMAGE_ID",
                                       6 => "COORDINATES",
                                       7 => "SCHEDULE",
                                       8 => "",
                                   ),
                                   "IBLOCK_ID" => "21",
                                   "IBLOCK_TYPE" => "1c_catalog",
                                   "MAIN_TITLE" => "",
                                   "MIN_AMOUNT" => "0",
                                   "OFFER_ID" => "",
                                   "SHOW_EMPTY_STORE" => "N",
                                   "SHOW_GENERAL_STORE_INFORMATION" => "N",
                                   "STORES" => array(
                                       0 => "8",
                                       1 => "6",
                                       2 => "5",
                                       3 => "3",
                                       4 => "17",
                                       5 => "7",
                                       6 => "4",
                                       7 => "19",
                                   ),
                                   "STORE_PATH" => "",
                                   "USER_FIELDS" => array(
                                       0 => "UF_STORE",
                                       1 => "",
                                   ),
                                   "USE_MIN_AMOUNT" => "N",
                                   "COMPONENT_TEMPLATE" => "store",
                                   "COMPOSITE_FRAME_MODE" => "A",
                                   "COMPOSITE_FRAME_TYPE" => "AUTO",
                                   "CATALOG_QUANTITY" => $arResult['CATALOG_QUANTITY']
                               ),
                               false
                           );?>
                       </div>
                       <div class="bb_col w-30">
                           <? include('inc/list.php'); ?>
                       </div>
                   </div>

               </div>
            </div>
         </div>
      </div>
   </div><!--end::pc__prod-info-->



   <div class="cl"></div>

    <div class="col-show-slides-6">

        <?$APPLICATION->IncludeComponent("bitrix:news.list", "same-product", Array(
            "ACTIVE_DATE_FORMAT" => "d.m.Y",	// Р¤РѕСЂРјР°С‚ РїРѕРєР°Р·Р° РґР°С‚С‹
            "ADD_SECTIONS_CHAIN" => "N",	// Р’РєР»СЋС‡Р°С‚СЊ СЂР°Р·РґРµР» РІ С†РµРїРѕС‡РєСѓ РЅР°РІРёРіР°С†РёРё
            "AJAX_MODE" => "N",	// Р’РєР»СЋС‡РёС‚СЊ СЂРµР¶РёРј AJAX
            "AJAX_OPTION_ADDITIONAL" => "",	// Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Р№ РёРґРµРЅС‚РёС„РёРєР°С‚РѕСЂ
            "AJAX_OPTION_HISTORY" => "N",	// Р’РєР»СЋС‡РёС‚СЊ СЌРјСѓР»СЏС†РёСЋ РЅР°РІРёРіР°С†РёРё Р±СЂР°СѓР·РµСЂР°
            "AJAX_OPTION_JUMP" => "N",	// Р’РєР»СЋС‡РёС‚СЊ РїСЂРѕРєСЂСѓС‚РєСѓ Рє РЅР°С‡Р°Р»Сѓ РєРѕРјРїРѕРЅРµРЅС‚Р°
            "AJAX_OPTION_STYLE" => "Y",	// Р’РєР»СЋС‡РёС‚СЊ РїРѕРґРіСЂСѓР·РєСѓ СЃС‚РёР»РµР№
            "CACHE_FILTER" => "N",	// РљРµС€РёСЂРѕРІР°С‚СЊ РїСЂРё СѓСЃС‚Р°РЅРѕРІР»РµРЅРЅРѕРј С„РёР»СЊС‚СЂРµ
            "CACHE_GROUPS" => "Y",	// РЈС‡РёС‚С‹РІР°С‚СЊ РїСЂР°РІР° РґРѕСЃС‚СѓРїР°
            "CACHE_TIME" => "36000000",	// Р’СЂРµРјСЏ РєРµС€РёСЂРѕРІР°РЅРёСЏ (СЃРµРє.)
            "CACHE_TYPE" => "A",	// РўРёРї РєРµС€РёСЂРѕРІР°РЅРёСЏ
            "CHECK_DATES" => "Y",	// РџРѕРєР°Р·С‹РІР°С‚СЊ С‚РѕР»СЊРєРѕ Р°РєС‚РёРІРЅС‹Рµ РЅР° РґР°РЅРЅС‹Р№ РјРѕРјРµРЅС‚ СЌР»РµРјРµРЅС‚С‹
            "DETAIL_URL" => "",	// URL СЃС‚СЂР°РЅРёС†С‹ РґРµС‚Р°Р»СЊРЅРѕРіРѕ РїСЂРѕСЃРјРѕС‚СЂР° (РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ - РёР· РЅР°СЃС‚СЂРѕРµРє РёРЅС„РѕР±Р»РѕРєР°)
            "DISPLAY_BOTTOM_PAGER" => "Y",	// Р’С‹РІРѕРґРёС‚СЊ РїРѕРґ СЃРїРёСЃРєРѕРј
            "DISPLAY_DATE" => "Y",	// Р’С‹РІРѕРґРёС‚СЊ РґР°С‚Сѓ СЌР»РµРјРµРЅС‚Р°
            "DISPLAY_NAME" => "Y",	// Р’С‹РІРѕРґРёС‚СЊ РЅР°Р·РІР°РЅРёРµ СЌР»РµРјРµРЅС‚Р°
            "DISPLAY_PICTURE" => "Y",	// Р’С‹РІРѕРґРёС‚СЊ РёР·РѕР±СЂР°Р¶РµРЅРёРµ РґР»СЏ Р°РЅРѕРЅСЃР°
            "DISPLAY_PREVIEW_TEXT" => "Y",	// Р’С‹РІРѕРґРёС‚СЊ С‚РµРєСЃС‚ Р°РЅРѕРЅСЃР°
            "DISPLAY_TOP_PAGER" => "N",	// Р’С‹РІРѕРґРёС‚СЊ РЅР°Рґ СЃРїРёСЃРєРѕРј
            "FIELD_CODE" => array(	// РџРѕР»СЏ
                0 => "",
                1 => "",
            ),
            "FILTER_NAME" => "",	// Р¤РёР»СЊС‚СЂ
            "HIDE_LINK_WHEN_NO_DETAIL" => "N",	// РЎРєСЂС‹РІР°С‚СЊ СЃСЃС‹Р»РєСѓ, РµСЃР»Рё РЅРµС‚ РґРµС‚Р°Р»СЊРЅРѕРіРѕ РѕРїРёСЃР°РЅРёСЏ
            "IBLOCK_ID" => $arResult['IBLOCK_ID'],	// РљРѕРґ РёРЅС„РѕСЂРјР°С†РёРѕРЅРЅРѕРіРѕ Р±Р»РѕРєР°
            "IBLOCK_TYPE" => "1c_catalog",	// РўРёРї РёРЅС„РѕСЂРјР°С†РёРѕРЅРЅРѕРіРѕ Р±Р»РѕРєР° (РёСЃРїРѕР»СЊР·СѓРµС‚СЃСЏ С‚РѕР»СЊРєРѕ РґР»СЏ РїСЂРѕРІРµСЂРєРё)
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",	// Р’РєР»СЋС‡Р°С‚СЊ РёРЅС„РѕР±Р»РѕРє РІ С†РµРїРѕС‡РєСѓ РЅР°РІРёРіР°С†РёРё
            "INCLUDE_SUBSECTIONS" => "Y",	// РџРѕРєР°Р·С‹РІР°С‚СЊ СЌР»РµРјРµРЅС‚С‹ РїРѕРґСЂР°Р·РґРµР»РѕРІ СЂР°Р·РґРµР»Р°
            "MESSAGE_404" => "",	// РЎРѕРѕР±С‰РµРЅРёРµ РґР»СЏ РїРѕРєР°Р·Р° (РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ РёР· РєРѕРјРїРѕРЅРµРЅС‚Р°)
            "NEWS_COUNT" => "20",	// РљРѕР»РёС‡РµСЃС‚РІРѕ РЅРѕРІРѕСЃС‚РµР№ РЅР° СЃС‚СЂР°РЅРёС†Рµ
            "PAGER_BASE_LINK_ENABLE" => "N",	// Р’РєР»СЋС‡РёС‚СЊ РѕР±СЂР°Р±РѕС‚РєСѓ СЃСЃС‹Р»РѕРє
            "PAGER_DESC_NUMBERING" => "N",	// Р�СЃРїРѕР»СЊР·РѕРІР°С‚СЊ РѕР±СЂР°С‚РЅСѓСЋ РЅР°РІРёРіР°С†РёСЋ
            "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Р’СЂРµРјСЏ РєРµС€РёСЂРѕРІР°РЅРёСЏ СЃС‚СЂР°РЅРёС† РґР»СЏ РѕР±СЂР°С‚РЅРѕР№ РЅР°РІРёРіР°С†РёРё
            "PAGER_SHOW_ALL" => "N",	// РџРѕРєР°Р·С‹РІР°С‚СЊ СЃСЃС‹Р»РєСѓ "Р’СЃРµ"
            "PAGER_SHOW_ALWAYS" => "N",	// Р’С‹РІРѕРґРёС‚СЊ РІСЃРµРіРґР°
            "PAGER_TEMPLATE" => ".default",	// РЁР°Р±Р»РѕРЅ РїРѕСЃС‚СЂР°РЅРёС‡РЅРѕР№ РЅР°РІРёРіР°С†РёРё
            "PAGER_TITLE" => "Товары со схожими характеристиками",	// РќР°Р·РІР°РЅРёРµ РєР°С‚РµРіРѕСЂРёР№
            "PARENT_SECTION" => $arResult['IBLOCK_SECTION_ID'],	// ID СЂР°Р·РґРµР»Р°
            "PARENT_SECTION_CODE" => "",	// РљРѕРґ СЂР°Р·РґРµР»Р°
            "PREVIEW_TRUNCATE_LEN" => "",	// РњР°РєСЃРёРјР°Р»СЊРЅР°СЏ РґР»РёРЅР° Р°РЅРѕРЅСЃР° РґР»СЏ РІС‹РІРѕРґР° (С‚РѕР»СЊРєРѕ РґР»СЏ С‚РёРїР° С‚РµРєСЃС‚)
            "PROPERTY_CODE" => array(	// РЎРІРѕР№СЃС‚РІР°
                0 => "",
                1 => "CML2_BASE_UNIT",
            ),
            "SET_BROWSER_TITLE" => "Y",	// РЈСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊ Р·Р°РіРѕР»РѕРІРѕРє РѕРєРЅР° Р±СЂР°СѓР·РµСЂР°
            "SET_LAST_MODIFIED" => "N",	// РЈСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊ РІ Р·Р°РіРѕР»РѕРІРєР°С… РѕС‚РІРµС‚Р° РІСЂРµРјСЏ РјРѕРґРёС„РёРєР°С†РёРё СЃС‚СЂР°РЅРёС†С‹
            "SET_META_DESCRIPTION" => "N",	// РЈСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊ РѕРїРёСЃР°РЅРёРµ СЃС‚СЂР°РЅРёС†С‹
            "SET_META_KEYWORDS" => "N",	// РЈСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊ РєР»СЋС‡РµРІС‹Рµ СЃР»РѕРІР° СЃС‚СЂР°РЅРёС†С‹
            "SET_STATUS_404" => "N",	// РЈСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊ СЃС‚Р°С‚СѓСЃ 404
            "SET_TITLE" => "N",	// РЈСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊ Р·Р°РіРѕР»РѕРІРѕРє СЃС‚СЂР°РЅРёС†С‹
            "SHOW_404" => "N",	// РџРѕРєР°Р· СЃРїРµС†РёР°Р»СЊРЅРѕР№ СЃС‚СЂР°РЅРёС†С‹
            "SORT_BY1" => "ACTIVE_FROM",	// РџРѕР»Рµ РґР»СЏ РїРµСЂРІРѕР№ СЃРѕСЂС‚РёСЂРѕРІРєРё РЅРѕРІРѕСЃС‚РµР№
            "SORT_BY2" => "SORT",	// РџРѕР»Рµ РґР»СЏ РІС‚РѕСЂРѕР№ СЃРѕСЂС‚РёСЂРѕРІРєРё РЅРѕРІРѕСЃС‚РµР№
            "SORT_ORDER1" => "DESC",	// РќР°РїСЂР°РІР»РµРЅРёРµ РґР»СЏ РїРµСЂРІРѕР№ СЃРѕСЂС‚РёСЂРѕРІРєРё РЅРѕРІРѕСЃС‚РµР№
            "SORT_ORDER2" => "ASC",	// РќР°РїСЂР°РІР»РµРЅРёРµ РґР»СЏ РІС‚РѕСЂРѕР№ СЃРѕСЂС‚РёСЂРѕРІРєРё РЅРѕРІРѕСЃС‚РµР№
            "STRICT_SECTION_CHECK" => "N",	// РЎС‚СЂРѕРіР°СЏ РїСЂРѕРІРµСЂРєР° СЂР°Р·РґРµР»Р° РґР»СЏ РїРѕРєР°Р·Р° СЃРїРёСЃРєР°
        ),
            false
        );?>

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
			0 => "CML2_BASE_UNIT",
			1 => "",
		),
		"PROPERTY_CODE_MOBILE_21" => array(
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


    </div>


</div><!--end::prod_card-->


