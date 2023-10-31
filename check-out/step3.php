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
   <div class="stage s3 active"><span>3</span><div class="text">Способ <br>оплаты</div></div>
   <div class="stage s4"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<div class="or__content cl s3">
   <div class="title">Укажите удобный способ оплаты заказа</div>
   <div class="payment_types cl">
      <a href="#" class="type t1">ОПЛАТА<br>НАЛИЧНЫМИ</a>
      <a href="#" class="type t2">БЕЗНАЛИЧНЫЙ<br>РАСЧЕТ</a>
      <a href="#" class="type t3">БАНКОВСКИЕ<br>КАРТЫ</a>
      <a href="#" class="type t4">ПЕРЕВОД НА<br>КАРТУ СБЕРБАНКА</a>
   </div>
   <a href="/check-out/step4.php" class="issue" onclick="">Оформить заказ</a>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>