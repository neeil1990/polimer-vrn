<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оформление заказа");
?>

<div class="or__back2shopping">
   <a href="#">Вернуться к покупкам</a>
</div>
<div class="or__stages cl">
   <div class="stage s1 complite"><span>1</span><div class="text">Контактная <br>информация</div></div>
   <div class="stage s2 active"><span>2</span><div class="text">Cпособ <br>получения</div></div>
   <div class="stage s3"><span>3</span><div class="text">Способ <br>оплаты</div></div>
   <div class="stage s4"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<div class="or__content cl s2">
   <div class="title">Выберите удобный способ получения заказа</div>
   <div class="methods cl">
      <a href="#" class="meth"><div class="inner">Самовывоз <br class="translate">из 3 пунктов<span>сегодня, <br class="translate">бесплатно</span></div></a>
      <a href="#" class="meth active"><div class="inner">Доставка <br class="translate">по адресу<span>сегодня, <br class="translate">бесплатно</span></div></a>
      <a href="#" class="meth"><div class="inner"><div class="txt-main">Доставка транспортной компанией</div><div class="txt-mobile">Транспортная<br>компания</div><span>в удобное <br class="translate">для вас время</span></div></a>
   </div>

   <div class="methods_detail">
      <div class="group">
         <div class="line">
            <span>Населенный пункт: *</span>
            <div class="inp"><input type="text"></div>
         </div>
         <div class="line">
            <span>Улица: *</span>
            <div class="inp"><input type="text"></div>
         </div>
      </div>
      <div class="group">
         <div class="line">
            <span>Контактный телефон:</span>
            <div class="phone-add first">
               <div class="inp phone"><input type="text"></div>
               <a href="#" class="addphone"><span></span><span></span>Добавить телефон</a>
            </div>
         </div>
      </div>
      <div class="group tall">
         <div class="line dateline">
            <span class="g-title">Выбрать удобную дату и интервал времени доставки</span>
            <span>Дата:</span>
            <div class="inp date"><input type="text" placeholder="Выбрать дату" name="date" id="date"></div>
            <span class="span_time">Время</span><span class="span_from">с</span><div class="inp time from"><input type="text"></div><span class="span_to">до</span><div class="inp time to"><input type="text"></div>
         </div>
      </div>
      <div class="controls cl">
         <a href="/check-out/" class="control prev">Предыдущий шаг</a>
         <a href="/check-out/step3.php" class="control next">Следующий шаг</a>
      </div>
   </div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>