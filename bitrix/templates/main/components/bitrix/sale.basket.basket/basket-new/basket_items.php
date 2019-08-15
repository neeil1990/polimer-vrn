<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?echo ShowError($arResult["ERROR_MESSAGE"]);
if ($normalCount > 0)
{?>

	<div class="basket">
<!--		<a href="#" class="check">Проверка наличия на складе</a>-->
		<h1>Корзина</h1>
		<div class="list">
			<div class="l-head">
				<div class="lh">Наименование</div>
				<div class="lh">Цена</div>
				<div class="lh">Скидка</div>
				<div class="lh">Кол-во</div>
				<div class="lh">Стоимость</div>
			</div>
			<div class="l-block">

				<? foreach($arResult["GRID"]["ROWS"] as $k=>$arItem): ?>
				<div class="l-row cl">
					<div class="l-cell img"><span><img src="<?=$arItem['PREVIEW_PICTURE_SRC']?>" alt=""></span></div>
					<div class="l-cell name">
						<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="plink"><?=$arItem['NAME']?></a>
					</div>
					<div class="l-cell price"><div class="txt pr">Цена</div><span><?=$arItem['FULL_PRICE']?></span> &#8381;</div>
					<div class="l-cell sale"><div class="txt sl">Скидка</div><span><?=$arItem['DISCOUNT_PRICE_PERCENT_FORMATED']?></span></div>
					<div class="l-cell quan">
						<div class="txt qn">Кол-во</div>
						<div class="quantity" id="<?=$arItem['ID']?>">
							<a class="minus" href="javascript:void(0);" onclick="basketMinus(<?=$arItem['AVAILABLE_QUANTITY']?>,$('.quantity#<?=$arItem['ID']?> input').val(),<?=$arItem['ID']?>)"></a>
							<input type="text" onblur="inputQuntly(<?=$arItem['AVAILABLE_QUANTITY']?>,this.value,<?=$arItem['ID']?>)" limit-count="<?=$arItem['AVAILABLE_QUANTITY']?>" value="<?=$arItem['QUANTITY']?>"/>
							<a class="plus" href="javascript:void(0)" onclick="basketPlus(<?=$arItem['AVAILABLE_QUANTITY']?>,$('.quantity#<?=$arItem['ID']?> input').val(),<?=$arItem['ID']?>)"></a>
						</div>
					</div>
					<div class="l-cell cost"><div class="txt ct">Стоимость</div><span><?=$arItem["SUM"]?></span></div>
					<div class="l-cell del"><div class="txt dl">Удалить</div><a href="?basketAction=delete&id=<?=$arItem["ID"]?>"></a></div>
				</div>
				<? endforeach; ?>


			</div>
		</div>


		<div class="options">
			<a href="javascript:void(0)" onclick="deleteBasket()" class="clear_basket">Очистить корзину</a>
			<a href="/catalog/" class="continue_shopping">Продолжить покупки</a>
			<div class="promo cl">
				<a class="apply bx_bt_button bx_big" href="javascript:void(0)" onclick="setCupon()" title="Нажмите для применения нового купона">Применить</a>
				<input type="text" id="coupon" name="COUPON" value="">
				<span>Активировать промокод:</span>
			</div>
<!--			<a href="/check-out/step2.php" class="checkout_wr">Оформить заказ без регистрации</a>-->
			<a href="javascript:void(0)" class="checkout_wr show-popup" data-id="oneclickcart">Купить в один клик</a>
			<a href="<?=$arParams['PATH_TO_ORDER']?>" class="checkout">Оформить заказ</a>
			<div class="total">
				<div class="t-row t-sale cl">
					<div class="line"></div>
					<div class="name">Общая скидка:</div>
					<div class="value"><span><?=$arResult['DISCOUNT_PRICE_ALL_FORMATED']?></span></div>
				</div>
				<div class="t-row cl t-quan">
					<div class="line"></div>
					<div class="name">Общее кол-во товаров:</div>
					<div class="value"><span><?=count($arResult["GRID"]["ROWS"]);?></span> шт.</div>
				</div>
				<div class="t-row cl t-cost">
					<div class="line"></div>
					<div class="name">Общая стоимость:</div>
					<div class="value"><span><?=$arResult["allSum_FORMATED"]?></span></div>
				</div>
			</div>
		</div>
	</div><!--end::basket-->

<?}
else
{?>
	<div id="basket_items_list">
		<table>
			<tbody>
			<tr>
				<td colspan="<?=$numCells?>" style="text-align:center">
					<div class=""><?=GetMessage("SALE_NO_ITEMS");?></div>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
<?}?>