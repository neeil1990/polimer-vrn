<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Кредит онлайн");
?><h2>
<div id="pos-credit-container">
</div>
 Как оформить?</h2>
<p>
	 Для того чтобы заказать товар в нашей компании требуется минимум действий, документов и несколько минут вашего времени. Алгоритм следующий:
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; делаете заказ в нашем интернет-магазине;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; с вами связывается наш менеджер и уточняет удобное время для визита в наш офис продаж;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Заполните простую форму для обратной связи (ФИО, телефон и сумму Вашей покупки) и нажмите «Отправить заявку»;
</p>
<p>
 <img alt="Анкета ЛОГО.jpg" src="/upload/medialibrary/652/652d8c9c19974c82295a50753910ec09.jpg" title="Анкета ЛОГО.jpg"><br>
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; После этого в течение 5-10 минут с Вами свяжется кредитный специалист «Почта Банка» для дальнейшего анкетирования и отправки заявки на одобрение;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; В случае одобрения банком Вашей заявки, необходимо позвонить в Наш магазин по телефону +7(473) 250-22-33 согласовать время и дату посещения Нашего магазина для подписания кредитного договора и получения товара;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; В момент обращения в Наш магазин с целью подписания договора, необходимо предоставить оригинал паспорта (разворот с фото и разворот с действующей регистрацией);
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; в течение 20 минут в офисе нашей компании оформляются все документы;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; забираете товар выбранным способом.
</p>
<p>
	 &nbsp;
</p>
<p>
	 Более подробную информацию можно уточнить у нашего менеджера.
</p>
<h2>Преимущества</h2>
<p>
	 Почему выгоднее заказать товар в кредит у нас, а не брать отдельно потребительский займ? Все очень просто:
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; минимальный пакет документов;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; не нужно брать страховку, которая обязательно оформляется в случае кредитования банком;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; можно досрочно выплатить все сумму и без процентов;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; минимальное количество потраченного времени – не нужно ездить в банк, ждать одобрения, предоставлять какие-либо дополнительные бумаги, оформлять кредитные карты;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; не нужны справки о доходах, поручители и залоги.
</p>
<p>
	 Другими словами, вы без лишних временных и финансовых затрат можете приобрести любой товар в нашем интернет-магазине в кредит, не выходя из дома или офиса! С более подробной информацией можно ознакомиться на нашем сайте или проконсультироваться у менеджера.
</p>
<h3>Преимущества кредита от Почта Банк:</h3>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Оформление и принятие решения за 15 минут;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Возможность частичного и полного досрочного погашения без комиссии;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Кредитные программы с переплатой от 1% в месяц.
</p>
<h3>Требования для оформления кредита:</h3>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Возраст от 18 лет;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Сумма кредита 3 000 – 300 000 рублей;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Кредит выдается на основании одного документа — паспорта гражданина РФ с пропиской в любом субъекте РФ.
</p>
<h3>Для анкетирования по телефону нужно подготовить:</h3>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; номер мобильного телефона;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; номер другого контактного телефона;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; номер рабочего телефона (возможно, указание мобильного телефона руководителя), если вы не пенсионер;
</p>
<p>
	 ·&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; паспортные данные.
</p>
<p>
</p>
<div id="pos-credit-container">
</div>
 <script src="https://my.pochtabank.ru/sdk/v1/pos-credit.js"></script> <script>
   var options = {
      ttCode: '0112001008863',
      ttName: 'г. Воронеж, Ильюшина, д.10 А',
      fullName: '',
      phone: '',
      category: '252',
      manualOrderInput: true     
};
window.PBSDK.posCredit.mount('#pos-credit-container', options);
 
   // подписка на событие завершения заполнения заявки
   window.PBSDK.posCredit.on('done', function(id){
      console.log('Id заявки: ' + id)
  });
 
   // При необходимости можно убрать виджет вызвать unmount
   // window.PBSDK.posCredit.unmount('#pos-credit-container');
</script><br>
<p>
</p>
<div id="pos-credit-container">
</div>
 &nbsp;&nbsp;
<div id="pos-credit-container">
</div>
<div id="pos-credit-container">
</div>
 &nbsp;<br><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>