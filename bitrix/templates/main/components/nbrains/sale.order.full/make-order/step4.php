<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>



<div class="or__stages cl">
	<div class="stage s1 complite"><span>1</span><div class="text">Контактная <br>информация</div></div>
	<div class="stage s2 complite"><span>2</span><div class="text">Cпособ <br>получения</div></div>
	<div class="stage s3 active"><span>3</span><div class="text">Способ <br>оплаты</div></div>
	<div class="stage s4"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>


<div class="or__content cl s3">
	<div class="title">Укажите удобный способ оплаты заказа</div>
	<div class="payment_types cl">

		<? foreach($arResult["PAY_SYSTEM"] as $key => $arPaySystem): ?>
		<a href="#" class="type t<?=$key+1;?>" <? if($key == 0){print 'style="border:5px solid #000"';}?>>
			<?= str_replace(' ','<br>',$arPaySystem["PSA_NAME"]) ?>
			<input type="radio" hidden id="ID_PAY_SYSTEM_ID_<?= $arPaySystem["ID"] ?>" name="PAY_SYSTEM_ID" value="<?= $arPaySystem["ID"] ?>"<?if ($arPaySystem["CHECKED"]=="Y") echo " checked";?>>
		</a>
		<? endforeach; ?>

	</div>
	<input type="submit" name="contButton" class="issue" value="<?= GetMessage("SALE_CONTINUE")?>">

</div>

<div class="clear"></div>
