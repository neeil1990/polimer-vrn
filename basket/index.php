<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Корзина");
?>

<div class="basket">
   <a href="#" class="check">Проверка наличия на складе</a>
   <h1>Корзина</h1>
   <div class="list">
      <div class="l-head">
         <div class="lh">Наименование</div>
         <div class="lh">Цена</div>
         <div class="lh">Скидка</div>
         <div class="lh">Кол-во</div>
         <div class="lh">Стоимость</div>
      </div>
      <div class="l-block">
         <div class="l-row cl">
            <div class="l-cell img"><span><img src="<?=SITE_TEMPLATE_PATH?>/img/basket/item1.png" alt=""></span></div>
            <div class="l-cell name">Радиатор алюминиевый TORRID<a href="/catalog/detail/" class="plink">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a></div>
            <div class="l-cell price"><div class="txt pr">Цена</div><span>12 500</span> Руб.</div>
            <div class="l-cell sale"><div class="txt sl">Скидка</div><span>5</span>%</div>
            <div class="l-cell quan">
               <div class="txt qn">Кол-во</div>
               <div class="quantity">
                  <a class="minus na" href="#"></a>
                  <input type="text" value="1"/>
                  <a class="plus" href="#"></a>
               </div>
            </div>
            <div class="l-cell cost"><div class="txt ct">Стоимость</div><span>11 875</span> Руб.</div>
            <div class="l-cell del"><div class="txt dl">Удалить</div><a href="#"></a></div>
         </div>

         <div class="l-row cl">
            <div class="l-cell img"><span><img src="<?=SITE_TEMPLATE_PATH?>/img/basket/item2.png" alt=""></span></div>
            <div class="l-cell name">Радиатор алюминиевый TORRID<a href="/catalog/detail/" class="plink">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a></div>
            <div class="l-cell price"><div class="txt pr">Цена</div><span>12 500</span> Руб.</div>
            <div class="l-cell sale"><div class="txt sl">Скидка</div><span>5</span>%</div>
            <div class="l-cell quan">
               <div class="txt qn">Кол-во</div>
               <div class="quantity">
                  <a class="minus na" href="#"></a>
                  <input type="text" value="1"/>
                  <a class="plus" href="#"></a>
               </div>
            </div>
            <div class="l-cell cost"><div class="txt ct">Стоимость</div><span>11 875</span> Руб.</div>
            <div class="l-cell del"><div class="txt dl">Удалить</div><a href="#"></a></div>
         </div>

         <div class="l-row cl">
            <div class="l-cell img"><span><img src="<?=SITE_TEMPLATE_PATH?>/img/basket/item3.png" alt=""></span></div>
            <div class="l-cell name">Радиатор алюминиевый TORRID<a href="/catalog/detail/" class="plink">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a></div>
            <div class="l-cell price"><div class="txt pr">Цена</div><span>12 500</span> Руб.</div>
            <div class="l-cell sale"><div class="txt sl">Скидка</div><span>5</span>%</div>
            <div class="l-cell quan">
               <div class="txt qn">Кол-во</div>
               <div class="quantity">
                  <a class="minus na" href="#"></a>
                  <input type="text" value="1"/>
                  <a class="plus" href="#"></a>
               </div>
            </div>
            <div class="l-cell cost"><div class="txt ct">Стоимость</div><span>11 875</span> Руб.</div>
            <div class="l-cell del"><div class="txt dl">Удалить</div><a href="#"></a></div>
         </div>
      </div>
   </div>

   <div class="options">
      <a href="#" class="clear_basket">Очистить корзину</a>
      <a href="/catalog/" class="continue_shopping">Продолжить покупки</a>
      <div class="promo cl">
         <a href="#" class="apply">Применить</a>
         <input type="text">
         <span>Активировать промокод:</span>
      </div>
      <a href="/check-out/step2.php" class="checkout_wr">Оформить заказ без регистрации</a>
      <a href="/check-out/" class="checkout">Оформить заказ</a>
      <div class="total">
         <div class="t-row t-sale cl">
            <div class="line"></div>
            <div class="name">Общая скидка:</div>
            <div class="value"><span>0</span> р.</div>
         </div>
         <div class="t-row cl t-quan">
            <div class="line"></div>
            <div class="name">Общее кол-во товаров:</div>
            <div class="value"><span>4</span> шт.</div>
         </div>
         <div class="t-row cl t-cost">
            <div class="line"></div>
            <div class="name">Общая стоимость:</div>
            <div class="value"><span>35 672.5</span> руб.</div>
         </div>
      </div>
   </div>
</div><!--end::basket-->
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>