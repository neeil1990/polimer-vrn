<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Корзина");
?>

<div class="or__back2shopping">
   <a href="/basket/">Вернуться к покупкам</a>
</div>
<div class="or__stages cl">
   <div class="stage s1 active"><span>1</span><div class="text">Контактная <br>информация</div></div>
   <div class="stage s2"><span>2</span><div class="text">Cпособ <br>получения</div></div>
   <div class="stage s3"><span>3</span><div class="text">Способ <br>оплаты</div></div>
   <div class="stage s4"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<div class="or__content cl s1">
   <div class="column">
      <div class="title">Личный кабинет</div>
      <div class="form_login">
         <div class="line"><span>Логин</span><input type="text"></div>
         <div class="line"><span>Пароль</span><input type="text"></div>
         <a href="step2.php" class="login_enter">Войти</a>
         <a href="#" class="login_reg ord" onclick="return false">Регистрация</a>
         <a href="#" class="remind_pass" onclick="return false">Напомнить пароль</a>
         <div class="login_social cl">
            <span>Войти через социальные сети</span>
            <a href="#" class="go"></a>
            <a href="#" class="tw"></a>
            <a href="#" class="vk"></a>
            <a href="#" class="rs"></a>
            <a href="#" class="fb"></a>
            <a href="#" class="ok"></a>
         </div>
      </div>
   </div>

   <div class="column">
      <div class="title">Регистрация на сайте</div>
      <a href="#" class="back2enter">Авторизация</a>
      <div class="form_registration">
         <div class="face_type">
            <label>
               <input type="radio" name="face" checked>
               <span>Физическое лицо</span>
            </label>
            <label>
               <input type="radio" name="face">
               <span>Юридическое лицо</span>
            </label>
         </div>
         <div class="line"><span>Фамилия</span><input type="text"></div>
         <div class="line"><span>Имя</span><input type="text"></div>
         <div class="line"><span>E-mail</span><input type="text"></div>
         <div class="line"><span>Телефон</span><span class="se7en">+7</span><input type="text" class="phone_code"><input type="text" class="phone_number"><span class="tip">Введите 10 цифр, например 987 123 45 67</span></div>
         <div class="line"><span>Пароль</span><input type="text" class="pass"></div>
         <div class="line pass_rep"><span>Повтор <br>пароля</span><input type="text" class="pass"><span class="req">Пароль должен содержать не менее 6 символов ,  кроме спец. символов и кириллицы</span></div>
         <div class="agent">
            <label>
               <input type="checkbox" name="face">
               <span>Я &mdash; представитель юридического лица или ИП</span>
            </label>
         </div>
         <a href="step2.php" class="registrate" onclick="return false">Зарегистрироваться</a>
      </div>
   </div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>