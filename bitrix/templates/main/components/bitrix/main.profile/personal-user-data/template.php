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
			<a href="/?logout=yes" class="exit">Выйти</a>
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
					<a href="/personal/info.php" class="menu-item pd active">Персональные<br>данные</a>
					<a href="/personal/security.php" class="menu-item ss">Настройки<br>безопасности</a>
				</div>
			</div>
			<div class="lk_content">
				<div class="header">Персональные данные</div>
				<div class="pd__block">
					<div class="face_type">
						<?
						$db_ptype = CSalePersonType::GetList(Array("SORT" => "ASC"), Array("LID"=>SITE_ID));

						while ($ptype = $db_ptype->Fetch())
						{
							?>
							<label>
								<input type="radio" name="WORK_DEPARTMENT" value="<?echo $ptype["ID"] ?>"<?if ($ptype['ID'] == $arResult["arUser"]["WORK_DEPARTMENT"]) echo " checked";?>>
								<span><?=$ptype['NAME']?></span>
							</label>
							<?

						}
						?>


					</div>
					<div class="line"><span><?=GetMessage('NAME')?></span><input type="text" name="NAME" maxlength="50" value="<?=$arResult["arUser"]["NAME"]?>" /></div>
					<div class="line"><span><?=GetMessage('LAST_NAME')?></span><input type="text" name="LAST_NAME" maxlength="50" value="<?=$arResult["arUser"]["LAST_NAME"]?>" /></div>
					<div class="line"><span><?=GetMessage('EMAIL')?></span><input type="text" name="EMAIL" maxlength="50" value="<? echo $arResult["arUser"]["EMAIL"]?>" /></div>
					<div class="line"><span><?=GetMessage('USER_PHONE')?></span><input type="text" name="WORK_PHONE" maxlength="255" class="phone_number" value="<?=$arResult["arUser"]["WORK_PHONE"]?>" /></div>

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


