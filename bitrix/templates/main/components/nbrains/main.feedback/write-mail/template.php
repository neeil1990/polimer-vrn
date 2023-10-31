<?
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
?>


<?if(!empty($arResult["ERROR_MESSAGE"])){?>
<div class="popup" id="<?=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"].'ERROR'?>" style="display: block;width: 650px;margin: 0 0 0 -325px;">
	<a href="#" class="close">&nbsp;</a>
	<div class="title"></div>
	<div class="subtitle">
		<?
		foreach ($arResult["ERROR_MESSAGE"] as $v)
			ShowError($v);
		?>
	</div>
</div>
<?}?>

<?if(strlen($arResult["OK_MESSAGE"]) > 0){?>
	<div class="popup" id="<?=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"].'OK'?>" style="display: block;width: 650px;margin: 0 0 0 -325px;">
		<a href="#" class="close">&nbsp;</a>
		<div class="title"></div>
		<div class="subtitle">
			<div class="mf-ok-text"><?=$arResult["OK_MESSAGE"]?></div>
		</div>
	</div>
<?}?>

<div class="popup" id="mailus">
	<a href="#" class="close">&nbsp;</a>
	<div class="title">Написать письмо</div>
	<div class="subtitle">Если у Вас возник вопрос или Вы хотите оставить комментарий, воспользуйтесь формой обратной связи. Наши специалисты свяжутся с Вами в ближайшее время.</div>

<form class="ym-goal-write-email" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">

<?=bitrix_sessid_post()?>


	<fieldset>

	<? foreach($arResult['USER_FIELD'] as $field):?>

		<?if($field['PROPERTY_TYPE'] == "S" AND $field['CODE'] != "DESC"):?>
			<span class="line cl">
     			<span class="label"><?=$field['NAME']?></span>
     			<span class="value">
					<input
						type="text"
						placeholder="<?if($field['CODE'] == "PHONE")
						{print "+7 (473) 234-03-01";}
						elseif($field['CODE'] == "FIO"){ print "Пример: Иванов Иван (на кириллице)";}
						else{print $field['NAME'];}?>"
						class="<?if($field['CODE'] == "PHONE"){print "phone";}elseif($field['CODE'] == "FIO"){print "name";}?>"
						name="<?=$field['CODE']?>"
						value="<?=$arResult[$field['CODE']]?>"
						<?=($field['CODE'] == "FIO") ? 'pattern="^[А-Яа-яЁё\s]+$"' : '';?> />
				</span>
     		</span>
		<? elseif($field['PROPERTY_TYPE'] == "L" AND $field['CODE'] != "DESC"):?>
			<div class="rule">
				<input type="checkbox" class="fio" name="<?=$field['CODE']?>" value="Y" checked>
				<span>
					Нажимая на эту кнопку, я даю свое согласие на <a href="/upload/compliance.pdf" target="_blank">обработку персональных данных</a> и соглашаюсь с условиями <a href="/upload/politics.pdf" target="_blank">политики конфиденциальности</a>.
<!--					Я прочитал правила-->
<!--					<a href="#" class="show-popup" data-id="--><?//=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"]?><!--">Правила</a>-->
<!--					и даю свое согласие на обработку персональных данных-->
				</span>
			</div>
		<? elseif($field['CODE'] == "DESC"):?>
			<span class="line cl wide">
     			<span class="label"><?=$field['NAME']?></span>
     			<span class="value">
     				<textarea name="<?=$field['CODE']?>"><?=$arResult[$field['CODE']]?></textarea>
     			</span>
     		</span>
		<? endif; ?>

	<? endforeach; ?>



		<?if($arParams["USE_CAPTCHA"] == "Y"):?>
			<div class="mf-captcha">
				<div class="g-recaptcha" data-sitekey="6LfZ8kgUAAAAAJWtIx1_4_pUvd1Xk_VfdMhpqT4P"></div>
			</div>
		<?endif;?>


		<span class="line submit">
			<input type="hidden" name="PARAMS_HASH" value="<?=$arResult["PARAMS_HASH"]?>">
			<input type="submit" name="submit" value="<?=GetMessage("MFT_SUBMIT")?>">
		</span>





	</fieldset>

</form>


</div>




<div class="popup" id="<?=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"]?>" style="display: none;width: 650px;margin: 0 0 0 -325px;">
	<a href="#" class="close">&nbsp;</a>
	<div class="title"></div>
	<div class="subtitle">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.include",
			"",
			Array(
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "inc",
				"EDIT_TEMPLATE" => "",
				"PATH" => "/include/rule.php"
			)
		);?>
	</div>
</div>