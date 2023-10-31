<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оформление заказа");
?>
<div class="or__back2shopping">
   <a href="#">Вернуться к покупкам</a>
</div>
<div class="or__stages cl">
   <div class="stage s1 complite"><span>1</span><div class="text">Контактная <br>информация</div></div>
   <div class="stage s2 complite"><span>2</span><div class="text">Cпособ <br>получения</div></div>
   <div class="stage s3 complite"><span>3</span><div class="text">Способ <br>оплаты</div></div>
   <div class="stage s4 active"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<div class="or__content cl s4">
   <div class="title">Подтверждение заказа</div>
   <div class="confirm">
      <div class="block purchase_data">
         <div class="title">Данные заказа</div>
         <div class="line">
            <div class="name">Ваш заказ:</div>
            <div class="val"><span>1 товар</span> на сумму <span>40 6890.2 руб.</span></div>
         </div>
         <div class="line">
            <div class="name">Способ <br class="translate">получения:</div>
            <div class="val">Самовывоз из офиса, г. Воронеж, Ильюшина, д.10А <a href="#">Подробнее</a></div>
         </div>
         <div class="line">
            <div class="name">Дата получения:</div>
            <div class="val">20 марта, после 10:00</div>
         </div>
         <div class="line">
            <div class="name">Способ оплаты:</div>
            <div class="val">Банковский перевод</div>
         </div>
      </div>

      <div class="block purchase_receiver">
         <div class="title">Получатель заказа</div>
         <a href="" class="receiver byself active">Заберу сам</a>
         <a href="" class="receiver byother">Заберет другой человек</a>
         <div class="line">
            <div class="name">ФИО</div>
            <div class="val">Иванов Иван Иванович</div>
         </div>
         <div class="line">
            <div class="name">Телефон:</div>
            <div class="val">+7 (960) 123-40-50 <a href="#">Изменить</a></div>
         </div>
         <div class="line">
            <div class="name">E-mail:</div>
            <div class="val">ivanovivan@gmail.com</div>
         </div>
      </div>
      <div class="block">
         <div class="rogerdat">
            <label>
               <input type="checkbox" name="face">
               <span>Заказ подтверждаю, мне можно не перезванитьвать</span>
            </label>
         </div>
         <div class="phonetime unactive">
            <span class="label">Укажите удобное время для звонка</span>Время<span class="fromto">с</span><input type="text"><span class="fromto">до</span><input type="text">
         </div>
         <div class="note">Примечание к заказу</div>
         <textarea name="" id="" cols="50" rows="10" placeholder="Введите краткий текст"></textarea>
         <div class="controls">
            <a href="#" class="btn-back">Вернуться к способу оплаты</a>
            <a href="#" class="btn-confirm">Подтвердить заказ</a>
         </div>
      </div>
   </div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>