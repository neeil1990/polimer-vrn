<?
/**
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>


	<form method="post" name="form1" action="<?=$arResult["FORM_TARGET"]?>" enctype="multipart/form-data">
		<?=$arResult["BX_SESSION_CHECK"]?>
		<input type="hidden" name="lang" value="<?=LANG?>" />
		<input type="hidden" name="ID" value=<?=$arResult["ID"]?> />
		<input type="hidden" id="i_login" name="LOGIN" value="<? echo $arResult["arUser"]["LOGIN"]?>" />

		<div class="lk_block cl">
			<a href="?logout=yes" class="exit">Выйти</a>
			<div class="lk_leftbar">
				<a href="#" class="lk_sandwich">
					<span></span>
					<span></span>
					<span></span>
				</a>
				<h1 class="h1-lk">Личный кабинет</h1>
				<div class="welcome">
					Добро пожаловать,
					<? global $USER; ?>
					<span class="username"><? echo $USER->GetFullName(); ?></span>
					<span class="usermail"><? echo $USER->GetEmail(); ?></span>
				</div>
				<div class="block-menu cl">
					<a href="/personal/orders-list.php" class="menu-item ph">История<br>заказов</a>
					<a href="/personal/info.php" class="menu-item pd">Персональные<br>данные</a>
					<a href="/personal/security.php" class="menu-item ss active">Настройки<br>безопасности</a>
				</div>
			</div>
			<div class="lk_content">
				<div class="header">Настройки безопасности</div>
				<div class="ss__block">
					<div class="line"><span><?=GetMessage('EMAIL')?></span><input type="text" class="pass" name="EMAIL" maxlength="50" value="<? echo $arResult["arUser"]["EMAIL"]?>" /></div>
					<div class="line"><span><?=GetMessage('NEW_PASSWORD_REQ')?></span><input type="password" class="pass" name="NEW_PASSWORD" value="" class="text" /></div>
					<div class="line"><span>Повтор пароля</span><input type="password" class="pass" name="NEW_PASSWORD_CONFIRM" value="" class="text" /><span class="req">Пароль должен содержать не менее 6 символов ,  кроме спец. символов и кириллицы</span></div>

					<? if($arResult["DATA_SAVED"] == "Y"):?>
						<div class="line"><span>Успешно сохранено!</span></div>
					<? endif; ?>
					<? if($arResult["strProfileError"]):?>
						<div class="line"><span><?=$arResult["strProfileError"]?></span></div>
					<? endif; ?>

					<input type="submit" class="save" name="save" value="<?=(($arResult["ID"]>0) ? GetMessage("MAIN_SAVE") : GetMessage("MAIN_ADD"))?>">
				</div>
			</div>
		</div>

	</form>


