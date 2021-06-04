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


<div class="popup" id="oneclickcart" style="width: 650px;margin: 0 0 0 -325px;">
	<a href="#" class="close">&nbsp;</a>
	<div class="title">Купить в 1 клик</div>
	<div class="subtitle">Укажите ваши данные и наши менеджеры свяжуться с вами для оформления заказа</div>
	<form action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
		<?=bitrix_sessid_post()?>
		<fieldset>

			<? foreach($arResult['USER_FIELD'] as $field):?>

				<?if($field['CODE'] == 'PRODUCT_CART'):?>

					<?

					$arBasketItems = array();

					$dbBasketItems = CSaleBasket::GetList(
						array(
							"NAME" => "ASC",
							"ID" => "ASC"
						),
						array(
							"FUSER_ID" => CSaleBasket::GetBasketUserID(),
							"LID" => SITE_ID,
							"ORDER_ID" => "NULL"
						),
						false,
						false,
						array("ID", "CALLBACK_FUNC", "MODULE",
							"PRODUCT_ID","NAME", "QUANTITY", "DELAY",
							"CAN_BUY", "PRICE", "WEIGHT")
					);
					$allPrice = array();
					ob_start();
					?>
					<table width="100%" align="center" cellspacing="0" cellpadding="0" border="0" class="">
						<tbody>
						<!-- title -->
						<tr style="background: #F5F6F8;border: 1px solid #DADADA;font-size: 13px;">
							<td style="border-top: 1px solid #DADADA;border-bottom: 1px solid #DADADA;border-left: 1px solid #DADADA;padding: 20px 10px;" width="" height=""></td>
							<td style="border-top: 1px solid #DADADA;border-bottom: 1px solid #DADADA;padding: 20px 10px;" width="" height="">Наименование</td>
							<td style="border-top: 1px solid #DADADA;border-bottom: 1px solid #DADADA;padding: 20px 10px;white-space: nowrap;" width="" height="">Кол-во</td>
							<td style="border-right: 1px solid #DADADA;border-top: 1px solid #DADADA;border-bottom: 1px solid #DADADA;padding: 20px 10px;" width="" height="">Стоимость</td>
						</tr>
						<?
						while ($arBasketItems = $dbBasketItems->Fetch())
						{

							$res = CIBlockElement::GetByID($arBasketItems["PRODUCT_ID"]);
							$ar_res = $res->GetNext();
							$img = CFile::GetPath($ar_res['PREVIEW_PICTURE']);
							$link_img = $_SERVER['SERVER_NAME'].$img;
							$link_prod = 'http://'.$_SERVER['SERVER_NAME'].$ar_res['DETAIL_PAGE_URL'];
							$ar_res = CCatalogProduct::GetOptimalPrice($arBasketItems["PRODUCT_ID"], 1, $USER->GetUserGroupArray(), 'N');
							$format_price = SaleFormatCurrency($ar_res['DISCOUNT_PRICE'], 'RUB',true);
							$price = SaleFormatCurrency($ar_res['DISCOUNT_PRICE']*$arBasketItems["QUANTITY"], 'RUB',true);
							$allPrice[] = $ar_res['DISCOUNT_PRICE']*$arBasketItems["QUANTITY"];
							?>
							<tr style="font-size: 13px;">
								<td style="padding: 20px 10px;" width="" height="">
									<img src="http://<?=$link_img;?>" style="max-width: 80px">
								</td>
								<td style="padding: 20px 10px;" width="" height="">
									<a href="<?=$link_prod;?>" style="text-decoration: none;color: #0464bb"><?=$arBasketItems["NAME"];?></a>
								</td>
								<td style="padding: 20px 10px;text-align: center;" width="" height=""><?=$arBasketItems["QUANTITY"];?></td>
								<td style="padding: 20px 10px;text-align: right;" width="" height="">
									<p style="white-space: nowrap;font-weight: bold;font-size: 16px;"><?=$price;?> <i style="font-weight: 100;font-style: initial;">&#8381;</i></p>
									<p style="white-space: nowrap;font-size: 12px;color: #939191;"><?=$arBasketItems["QUANTITY"];?> шт. X <?=$format_price?> &#8381;</i></p>
								</td>
							</tr>
							<?
						}

						?>
						<!-- Spacing -->
						</tbody>
					</table>
					<?
					$strOrderList = ob_get_contents();
					ob_end_clean();
					?>
					<textarea hidden name="<?=$field['CODE']?>">
						<?=htmlspecialchars($strOrderList);?>
					</textarea>

				<? else: ?>

				<?if($field['PROPERTY_TYPE'] == "S"):?>

						<?if($field['CODE'] != "STORE"):?>
							<span class="line cl">
								<span class="label"><?=$field['NAME']?></span>
								<span class="value"><input type="text" placeholder="<?if($field['CODE'] == "PHONE"){print "+7 (473) 234-03-01";}elseif($field['CODE'] == "FIO"){print "Пример: Иванов Иван (на кириллице)";}else{print $field['NAME'];}?>" class="<?if($field['CODE'] == "PHONE"){print "phone";}elseif($field['CODE'] == "FIO"){print "name";}?>" name="<?=$field['CODE']?>" value="<?=$arResult[$field['CODE']]?>"/></span>
							</span>
						<?else:?>
							<span class="line cl">
							<span class="label">Точка самовывоза</span>
							<span class="value">
								<select name="<?=$field['CODE']?>">
									<option value="">Не выбрано</option>
									<?
									$storeResult = CCatalogStore::GetList(
										array('SORT' => 'ASC'),
										array(
											'ACTIVE' => 'Y',
											'ID' => array(8,10,12,9,4,7,19)
										),
										false,
										false,
										array("*")
									);
									while ($arProp = $storeResult->GetNext()){
										?>
										<option value="<?=$arProp['ADDRESS']?>"><?=$arProp['ADDRESS']?></option>
										<?
									}
									?>
								</select>
							</span>
						</span>
						<? endif;?>

				<? elseif($field['PROPERTY_TYPE'] == "L"):?>
					<div class="rule">
						<input type="checkbox" class="fio" name="<?=$field['CODE']?>" value="Y" checked>
				<span>
					Нажимая на эту кнопку, я даю свое согласие на <a href="/upload/compliance.pdf" target="_blank">обработку персональных данных</a> и соглашаюсь с условиями <a href="/upload/politics.pdf" target="_blank">политики конфиденциальности</a>.
<!--					Я прочитал правила-->
<!--					<a href="#" class="show-popup" data-id="--><?//=$arParams["IBLOCK_TYPE"].$arParams["IBLOCK_ID"]?><!--">Правила</a>-->
<!--					и даю свое согласие на обработку персональных данных-->
				</span>
					</div>
				<? endif; ?>
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