<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>



<?
$cntBasketItems = CSaleBasket::GetList(
	array(),
	array(
		"FUSER_ID" => CSaleBasket::GetBasketUserID(),
		"LID" => SITE_ID,
		"ORDER_ID" => "NULL"
	),
	array()
);
if($cntBasketItems > 0):
?>

<div class="or__stages cl">
	<div class="stage s1 active"><span>1</span><div class="text">Контактная <br>информация</div></div>
	<div class="stage s2"><span>2</span><div class="text">Cпособ <br>получения</div></div>
	<div class="stage s3"><span>3</span><div class="text">Способ <br>оплаты</div></div>
	<div class="stage s4"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<? endif; ?>

<div class="or__content cl s1">
	<div class="column">
		<div class="title">Личный кабинет</div>
		<div class="form_login">

			<form method="post" action="<?= $arParams["PATH_TO_ORDER"] ?>" name="order_auth_form">
				<?=bitrix_sessid_post()?>

				<div class="line"><span>E-mail</span>
					<input type="text" name="USER_LOGIN" maxlength="30" size="30" value="<?=$arResult["USER_LOGIN"]?>">
				</div>

				<div class="line"><span>Пароль</span>
					<input type="password" name="USER_PASSWORD" maxlength="30" size="30">
				</div>

				<input type="submit" class="login_enter" value="Войти">
				<input type="hidden" name="do_authorize" value="Y">

				<a href="/personal/info.php?forgot_password=yes" class="remind_pass">Напомнить пароль</a>

<!--				<div class="login_social cl">-->
<!--					<span>Войти через социальные сети</span>-->
<!--					<a href="#" class="go"></a>-->
<!--					<a href="#" class="tw"></a>-->
<!--					<a href="#" class="vk"></a>-->
<!--					<a href="#" class="rs"></a>-->
<!--					<a href="#" class="fb"></a>-->
<!--					<a href="#" class="ok"></a>-->
<!--				</div>-->

			</form>

		</div>
	</div>




	<div class="column">
		<div class="title">Регистрация на сайте</div>
		<a href="#" class="back2enter">Авторизация</a>

		<? if($arResult["AUTH"]["new_user_registration"]=="Y"):?>
		<form method="post" action="<?= $arParams["PATH_TO_ORDER"]?>" name="order_reg_form">
			<?=bitrix_sessid_post()?>

		<div class="form_registration">
			<div class="face_type">
				<?
				foreach($arResult["PERSON_TYPE_INFO"] as $v)
				{
					?>
					<label>
						<input type="radio" id="PERSON_TYPE_<?= $v["ID"] ?>" name="PERSON_TYPE" value="<?= $v["ID"] ?>" <?if ($v["CHECKED"]=="Y") echo " checked";?> >
						<span><?= $v["NAME"] ?></span>
					</label>
					<?
				}
				?>
			</div>
			<div class="line"><span><?echo GetMessage("STOF_LASTNAME")?></span>
				<input type="text" name="NEW_LAST_NAME" placeholder="" size="40" value="<?=$arResult["POST"]["NEW_LAST_NAME"]?>">
			</div>
			<div class="line"><span><?echo GetMessage("STOF_NAME")?></span>
				<input type="text" name="NEW_NAME" size="40" value="<?=$arResult["POST"]["NEW_NAME"]?>">
			</div>
			<div class="line"><span>E-mail</span>
				<input type="text" name="NEW_EMAIL" size="40" value="<?=$arResult["POST"]["NEW_EMAIL"]?>">
			</div>
			<!--
			<div class="line">
				<span>Телефон</span>
				<span class="se7en">+7</span>
				<input type="text" maxlength="3" class="phone_code">
				<input type="text" class="phone_number">
				<span class="tip">Введите 10 цифр, например 987 123 45 67</span>
				<input type="hidden" name="WORK_PHONE" size="40" value="<?//=$arResult["POST"]["WORK_PHONE"]?>">
			</div>
			-->

<!--			<div class="line"><span>--><?//echo GetMessage("STOF_LOGIN")?><!--</span>-->
<!--				<input type="text" name="NEW_LOGIN" size="30" value="--><?//=$arResult["POST"]["NEW_LOGIN"]?><!--">-->
<!--			</div>-->
			<div class="line"><span><?echo GetMessage("STOF_PASSWORD")?></span>
				<input type="password" class="pass" name="NEW_PASSWORD" size="30">
			</div>
			<div class="line pass_rep">
				<span><?echo GetMessage("STOF_RE_PASSWORD")?></span>
				<input type="password" class="pass" name="NEW_PASSWORD_CONFIRM" size="30">
				<span class="req">Пароль должен содержать не менее 6 символов ,  кроме спец. символов и кириллицы</span>
			</div>
			<?
			if($arResult["AUTH"]["captcha_registration"] == "Y") //CAPTCHA
			{
				?>
				<tr>
					<td><br /><b><?=GetMessage("CAPTCHA_REGF_TITLE")?></b></td>
				</tr>
				<tr>
					<td>
						<input type="hidden" name="captcha_sid" value="<?=$arResult["AUTH"]["capCode"]?>">
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["AUTH"]["capCode"]?>" width="180" height="40" alt="CAPTCHA">
					</td>
				</tr>
				<tr valign="middle">
					<td>
						<span class="sof-req">*</span><?=GetMessage("CAPTCHA_REGF_PROMT")?>:<br />
						<input type="text" name="captcha_word" size="30" maxlength="50" value="">
					</td>
				</tr>
				<?
			}
			?>

			<div class="agent">
				<label>
					<input type="checkbox" name="WORK_POSITION" value="представитель юридического лица или ИП">
					<span>Я &mdash; представитель юридического лица или ИП</span>
				</label>
			</div>

			<div class="agent">
				<label>
					<input type="checkbox" name="rule" id="rule" value="Y" checked/>
					<span>Нажимая на эту кнопку, я даю свое согласие на <a href="/upload/compliance.pdf" target="_blank">обработку персональных данных</a> и соглашаюсь с условиями <a href="/upload/politics.pdf" target="_blank">политики конфиденциальности</a>.*</span>
				</label>
			</div>

			<input type="submit" class="registrate" value="<?echo GetMessage("STOF_NEXT_STEP")?>">
			<input type="hidden" name="do_register" value="Y">

		</div>

		</form>
		<?endif;?>


	</div>
</div>

<script>
	$(function(){

		$(function(){
			$('#rule').change(function(){
				$('.registrate').attr('disabled',$(this).prop('checked') ? false : true );
			});
		});

		$('.registrate').click(function(){
			var c = $('.phone_code').val();
			var p = $('.phone_number').val();
			var phone = c + p;
			$('input[name="WORK_PHONE"]').val(phone);
		});
	});
</script>

<br />
<br />


<?if($arResult["AUTH"]["new_user_registration"]=="Y"):?>
	<?echo GetMessage("STOF_EMAIL_NOTE")?><br /><br />
<?endif;?>
<?echo GetMessage("STOF_PRIVATE_NOTES")?>
