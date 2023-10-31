<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Авторизация");
LocalRedirect("/personal/order/make/",false,301);
?>

<div class="row cl login">
   <div class="col2 pr50 lk">
      <h1>Личный кабинет</h1>
      <div class="form_login">
         <div class="line"><span>Логин</span><input type="text"></div>
         <div class="line"><span>Пароль</span><input type="text"></div>
         <a href="orders-list.php" class="login_enter" onclick="">Войти</a>
         <a href="orders-list.php" class="login_reg log" onclick="return false;">Регистрация</a>
         <a href="#" class="remind_pass" onclick="">Напомнить пароль</a>
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
   </div><!--end::col2-->

   <div class="col2 reg">
      <div class="reg_title">Регистрация на сайте</div>
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
         <a href="orders-list.php" class="registrate" onclick="">Зарегистрироваться</a>
      </div>
   </div><!--end::col2-->
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>