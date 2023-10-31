<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (!function_exists('PrintPropsForm'))
{
	function PrintPropsForm($arSource=Array(), $PRINT_TITLE = "", $arParams)
	{
		if (!empty($arSource))
		{

			foreach($arSource as $key => $group){
				$arSource["ITEM"][$group['PROPS_GROUP_ID']]['ROWS'][] = $group;
				$arSource["ITEM"][$group['PROPS_GROUP_ID']]['NAME'] = $group['GROUP_NAME'];
			}

			foreach($arSource["ITEM"] as $group) {

				if(
					$group['NAME'] != "date_time_fis" AND
					$group['NAME'] != "group_store"
				):
				?><div class="group <?=$group['NAME']?>"><?
				 endif;

				$line = 0;
				foreach ($group['ROWS'] as $arProperties) {


					switch ($group['NAME']) {
						case "date_time_fis":
							break;
						case "group_store":
							break;
						case "personal_data_fis":
							?>
							<div class="table <?if($line < 2){print 'lines';}?>">
							<span><?= $arProperties["NAME"];
								if ($arProperties["REQUIED_FORMATED"] == "Y") {
									print "*";
								} ?></span>

								<div class="inp">
									<input type="text" maxlength="250" size="<?= $arProperties["SIZE1"] ?>" value="<?= $arProperties["VALUE"] ?>" name="<?= $arProperties["FIELD_NAME"] ?>">
								</div>
							</div>
							<?
							$line++;
							break;
						case "phone_group_fis":
							?>
							<div class="line ph">
								<input type="hidden" class="phone_hidden" maxlength="250" size="<?= $arProperties["SIZE1"] ?>" value="<?= $arProperties["VALUE"] ?>" name="<?= $arProperties["FIELD_NAME"] ?>">
								<span>Контактный телефон:</span>
								<?
								global $USER;
								$order = array('sort' => 'asc');
								$tmp = 'sort'; // параметр проигнорируется методом, но обязан быть
								$rsUsers = CUser::GetList($order, $tmp,array("ID" => $USER->GetID()),array("WORK_PHONE"));
								if($user_phone = $rsUsers->NavNext()){
									if($user_phone['WORK_PHONE']){
										$phone_user = $user_phone['WORK_PHONE'];
									}else{
										if($arProperties["VALUE"]){
											$num = explode(';',$arProperties["VALUE"]);
											$phone_user = $num[0];
										}
									}
								}
								?>
								<div class="phone-add first">
									<div class="inp phone">
										<input class="phone" type="text" value="<?if($phone_user){print $phone_user;}?>">
									</div>

									<a href="#" class="addphone"><span></span><span></span>Добавить телефон</a>
								</div>

								<? foreach(array_slice($num, 1) as $number): ?>
									<? if(strlen($number) > 2): ?>
									<div class="phone-add first">
										<div class="inp phone">
											<input class="phone" type="text" value="<?=$number;?>">
										</div>
									</div>
									<? endif; ?>
								<? endforeach; ?>

							</div>
							<?
							break;
						default:

							if($arProperties['CODE'] == 'PHONE'){
								global $USER;
								$order = array('sort' => 'asc');
								$tmp = 'sort'; // параметр проигнорируется методом, но обязан быть
								$rsUsers = CUser::GetList($order, $tmp,array("ID" => $USER->GetID()),array("WORK_PHONE"));
								if($user_phone = $rsUsers->NavNext()){
									if($user_phone['WORK_PHONE']){
										$arProperties["VALUE"] = $user_phone['WORK_PHONE'];
									}
								}
							}
							if ($arProperties["TYPE"] == "TEXT") {
								?>
								<div class="line">
							<span><?= $arProperties["NAME"];
								if ($arProperties["REQUIED_FORMATED"] == "Y") {
									print "*";
								} ?></span>

									<div class="inp">
										<input type="text" maxlength="250" size="<?= $arProperties["SIZE1"] ?>"
											   value="<?= $arProperties["VALUE"] ?>" name="<?= $arProperties["FIELD_NAME"] ?>">
									</div>
								</div>
								<?
							}	elseif ($arProperties["TYPE"] == "TEXTAREA") {
								?>
								<div class="line">
							<span><?= $arProperties["NAME"];
								if ($arProperties["REQUIED_FORMATED"] == "Y") {
									print "*";
								} ?></span>

									<div class="tex">
								<textarea rows="<?= $arProperties["SIZE2"] ?>" cols="<?= $arProperties["SIZE1"] ?>"
										  name="<?= $arProperties["FIELD_NAME"] ?>"><?= $arProperties["VALUE"] ?></textarea>
									</div>
								</div>

								<?
							}
					}



					if (strlen($arProperties["DESCRIPTION"]) > 0) {
						?><br/>
						<small><? echo $arProperties["DESCRIPTION"] ?></small><?
					}
					?>


					<?
				}

				if(
					$group['NAME'] != "date_time_fis" AND
					$group['NAME'] != "group_store"
				):?>
				</div>
				<? endif; ?>

				<? if($group['NAME'] == 'date_time_fis'): ?>
					<div class="group tall date_time_fis">
						<div class="line dateline">
							<span class="g-title">Выбрать удобную дату и интервал времени доставки</span>
							<span><?= $group['ROWS'][0]["NAME"];
								if ($group['ROWS'][0]["REQUIED_FORMATED"] == "Y") {
									print "*";
								} ?>:</span>
							<div class="inp date"><input type="text" placeholder="Выбрать дату" value="<?= $group['ROWS'][0]["VALUE"] ?>" name="<?= $group['ROWS'][0]["FIELD_NAME"] ?>" id="date"></div>
							<span class="span_time"><?= $group['ROWS'][1]["NAME"];
								if ($group['ROWS'][1]["REQUIED_FORMATED"] == "Y") {
									print "*";
								} ?></span>
							<div class="inp time from">
								<input type="text" class="call_time" value="<?= $group['ROWS'][1]["VALUE"] ?>" name="<?= $group['ROWS'][1]["FIELD_NAME"] ?>">
							</div>
						</div>
					</div>
				<? endif; ?>

				<?
			}

			return true;
		}
		return false;
	}
}
?>


<div class="or__stages cl">
	<div class="stage s1"><span>1</span><div class="text">Контактная <br>информация</div></div>
	<div class="stage s2 active"><span>2</span><div class="text">Cпособ <br>получения</div></div>
	<div class="stage s3"><span>3</span><div class="text">Способ <br>оплаты</div></div>
	<div class="stage s4"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<div class="or__content cl s2">

	<div class="title">Выберите удобный способ получения заказа</div>
	<div class="methods cl">
		<?
		foreach($arResult['PRINT_PROPS_FORM']['USER_PROPS_Y'] as $group){
			if($group['GROUP_NAME'] == 'group_store'){
				$name_field_store = $group['FIELD_NAME'];
			}
		}
		foreach($arResult["DELIVERY"] as $arDelivery):?>
		<a href="#" class="meth <?if ($arDelivery["CHECKED"]=="Y") echo "active";?>">
			<div class="inner">
				<?=$arDelivery['NAME']?><span><?=$arDelivery['DESCRIPTION']?></span>
			</div>
			<input style="display:none" type="radio" id-field="<?=$name_field_store;?>" id-store="<?=$arDelivery['STORE']?>" class="<?if ($arDelivery["CHECKED"]=="Y") echo "active";?>" id="ID_DELIERY_ID_<?= $arDelivery["ID"] ?>" name="<?=$arDelivery["FIELD_NAME"]?>" value="<?= $arDelivery["ID"] ?>" <?if ($arDelivery["CHECKED"]=="Y") echo "checked";?>>
		</a>
		<?endforeach;?>
	</div>

	<div class="methods_detail">
			<?
			$bPropsPrinted = PrintPropsForm($arResult["PRINT_PROPS_FORM"]["USER_PROPS_N"], GetMessage("SALE_INFO2ORDER"), $arParams);

			if(!empty($arResult["USER_PROFILES"]))
			{

			}
			else
			{
				?><input type="hidden" name="PROFILE_ID" value="0"><?
			}
			?>



			<?
			PrintPropsForm($arResult["PRINT_PROPS_FORM"]["USER_PROPS_Y"], GetMessage("SALE_NEW_PROFILE_TITLE"), $arParams);
			?>

									<div class="controls cl">
										<a href="/personal/order/make/?logout=yes" class="control prev">Предыдущий шаг</a>
										<a href="#" id="order_form_btn" class="control next">Следующий шаг</a>
<!--										<input type="submit" style="padding-left: 75px;" name="contButton" class="control next" value="--><?//= GetMessage("SALE_CONTINUE")?><!--">-->
									</div>
	</div>
</div>



<div class="clear"></div>