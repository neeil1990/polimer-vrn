<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($normalCount > 0)
{
	?>

	<div class="basket-info">
		<div class="list-info">

			<ul>
				<li>
					<ol>
						<li>
							<div>Сумма:</div>
							<div></div>
							<div><span class="info-weight"><?=$arResult['PRICE_WITHOUT_DISCOUNT'];?></span> </div>
						</li>
						<li>
							<div>Товаров:</div>
							<div></div>
							<div><span class="info-weight"><?=count($arResult["GRID"]["ROWS"]);?></span> шт.</div>
						</li>

						<li>
							<div>Скидка:</div>
							<div></div>
							<div><?=$_SESSION['DISCOUNT_PRICE_PERCENT_FORMATED']?></div>
						</li>


<!--						<li>-->
<!--							<div>Доставка:</div>-->
<!--							<div></div>-->
<!--							<div>бесплатно</div>-->
<!--						</li>-->
					</ol>
				</li>
			</ul>

			<ul>
				<li>
					<ol>

						<li>
							<div><span class="info-weight">Итого:</span></div>
							<div></div>
							<div><span class="info-weight itog"><?=$_SESSION['DISCOUNT_PRICE_ALL_FORMATED']?></span> &#8381;</div>
						</li>

					</ol>
				</li>
			</ul>

			<a href="/personal/cart/" class="control prev">Редактировать заказ</a>

		</div>
	</div>



					<?//=$arResult['DISCOUNT_PRICE_ALL']?>

					<?//=count($arResult["GRID"]["ROWS"]);?>

					<?//=$arResult["allSum"]?>


					<?// var_dump($arResult); ?>



<?}?>

