<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Sale\PriceMaths;
?>




<div class="or__stages cl">
	<div class="stage s1 complite"><span>1</span><div class="text">Контактная <br>информация</div></div>
	<div class="stage s2 complite"><span>2</span><div class="text">Cпособ <br>получения</div></div>
	<div class="stage s3 complite"><span>3</span><div class="text">Способ <br>оплаты</div></div>
	<div class="stage s4 active"><span>4</span><div class="text">Подтверждение <br>заказа</div></div>
</div>

<div class="or__content cl s4">
	<div class="title"><?echo GetMessage("STOF_ORDER_PARAMS")?></div>
	<div class="confirm">

		<div class="block purchase_data">
			<div class="title">Данные заказа</div>

			<?
            $address = '';
			foreach($arResult["ORDER_PROPS_PRINT"] as $key => $arProperties)
			{
				if(strLen($arProperties["VALUE_FORMATED"])>0)
				{
				    if($arProperties['GROUP_NAME'] == "group_store")
                        $address = $arProperties["VALUE_FORMATED"];
					?>
					<div class="line">
						<div class="name"><?= $arProperties["NAME"] ?>:</div>
						<div class="val"><span><?=$arProperties["VALUE_FORMATED"]?></span></div>
					</div>
					<?
				}
			}
			?>

		</div>

		<div class="block purchase_data">
			<div class="title"><?echo GetMessage("STOF_PAY_DELIV")?></div>


			<?
			if(count($arResult["BASKET_ITEMS"]) == 1){
				$prod = 'товар';
			}else{
				$prod = 'товара';
			}
			?>

			<div class="line">
				<div class="name">Ваш заказ:</div>
				<div class="val">&nbsp;</div>
			</div>

			<div class="line">
				<div class="name"></div>
				<div class="val"></div>
			</div>

			<table cellpadding="10" cellspacing="0" class="order-confirm">
				<tr>
					<th></th>
					<th></th>
					<th>Наименование</th>
					<th>Цена</th>
					<th>Скидка</th>
					<th>Кол-во</th>
					<th>Стоимость</th>
					<th></th>
				</tr>
				<?
                foreach($arResult["BASKET_ITEMS"] as $item):
					$arImg = CIBlockElement::GetByID($item['PRODUCT_ID']);
					$img = ($id_image = $arImg->GetNext()['PREVIEW_PICTURE']) ? CFile::GetPath($id_image) : $this->GetFolder().'/images/no_photo.png';

					$price = ($arParams['DISCOUNT_PRICE_D7'][$item['ID']]['PRICE']) ?: $item['PRICE'];
					$total[] = PriceMaths::roundPrecision($price*$item['QUANTITY']);

                    if($item['PRICE'] && $price)
					    $percent = round(((float)$item['PRICE'] / (float)$price - 1) * 100);
					?>
					<tr>
						<td></td>
						<td><a href="<?=$item['DETAIL_PAGE_URL'];?>" target="_blank"><img src="<?=$img;?>" style="max-height: 60px;max-width: 60px"></a></td>
						<td><a href="<?=$item['DETAIL_PAGE_URL'];?>" target="_blank"><?=$item['NAME']?></a></td>
						<td style="white-space: nowrap;"><?=$item['BASE_PRICE']?></td>
						<td><?=$percent?>%</td>
						<td><?=$item['QUANTITY']?> <?=$item['MEASURE_NAME']?></td>
						<td style="white-space: nowrap;"><?=CurrencyFormat($price*$item['QUANTITY'], 'RUB');?></td>
						<td></td>
					</tr>
				<?endforeach;?>
			</table>

			<div class="line">
				<div class="name">Итого:</div>
				<div class="val"><span><?=count($arResult["BASKET_ITEMS"])?> <?=$prod?> на сумму <?=CurrencyFormat(array_sum($total), 'RUB')?></span></div>
			</div>
			<?
			//echo "<pre>"; print_r($arResult); echo "</pre>";$arBasketItems["DISCOUNT_PRICE"] = CCatalogProduct::GetOptimalPrice($arBasketItems["PRODUCT_ID"], 1, $USER->GetUserGroupArray(), 'N')['RESULT_PRICE']['PERCENT'];
			if (is_array($arResult["DELIVERY"]))
			{
				?>

				<div class="line">
					<div class="name"><?= GetMessage("SALE_DELIV_SUBTITLE")?>:</div>
					<?if (is_array($arResult["DELIVERY_ID"])):?>
					<div class="val"><span><? echo " (".$arResult["DELIVERY"]["PROFILES"][$arResult["DELIVERY_PROFILE"]]["TITLE"].")"; ?></span></div>
					<?else:?>
					<div class="val">
                        <span>
                            <?=$arResult["DELIVERY"]["NAME"];?><?if($arResult["DELIVERY"]["NAME"] == 'Самовывоз')
                                print ", " . $address;
                                ?>
                        </span>
                    </div>
					<?endif;?>
				</div>
				<?
			}
			elseif ($arResult["DELIVERY"]=="ERROR")
			{
				echo ShowError(GetMessage("SALE_ERROR_DELIVERY"));
			}
			else
			{
				echo GetMessage("SALE_NO_DELIVERY");
			}
			?>

			<?
			//echo "<pre>"; print_r($arResult); echo "</pre>";
			if (is_array($arResult["PAY_SYSTEM"]))
			{
				?>
				<div class="line">
					<div class="name"><?= GetMessage("SALE_PAY_SUBTITLE")?>:</div>
					<div class="val"><span><? echo $arResult["PAY_SYSTEM"]['NAME']; ?></span></div>
				</div>
				<?
			}
			?>
		</div>

		<div class="block">
			<div class="note"><?= GetMessage("SALE_ADDIT_INFO_PROMT")?></div>
			<textarea rows="10" cols="50" name="ORDER_DESCRIPTION" placeholder="Введите краткий текст"><?=$arResult["ORDER_DESCRIPTION"]?></textarea>
			<div class="controls">

				<?if(!($arResult["SKIP_FIRST_STEP"] == "Y" && $arResult["SKIP_SECOND_STEP"] == "Y" && $arResult["SKIP_THIRD_STEP"] == "Y" && $arResult["SKIP_FORTH_STEP"] == "Y"))
				{
					?>
					<input type="submit" style="right: 480px" class="btn-confirm" name="backButton" value="<?echo GetMessage("SALE_BACK_BUTTON")?>">
					<?
				}
				?>
				<input type="submit" class="btn-confirm" name="contButton" value="<?= GetMessage("SALE_CONFIRM")?>">
			</div>
		</div>
	</div>
</div>



