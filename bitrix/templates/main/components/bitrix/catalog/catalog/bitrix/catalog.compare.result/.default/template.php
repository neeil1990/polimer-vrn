<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$isAjax = ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["ajax_action"]) && $_POST["ajax_action"] == "Y");

$templateData = array(
	'TEMPLATE_THEME' => $this->GetFolder().'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css',
	'TEMPLATE_CLASS' => 'bx_'.$arParams['TEMPLATE_THEME']
);
$arrPropertyCode = array();
?>
<div class="compare-page cl">
	<div class="inn">
		<a href="?action=DELETE_FROM_COMPARE_LIST&id=0" class="delete-all">Удалить все товары из сравнения</a>
		<div class="params-name">
            <div class="action-btn">
                <a class="sortbutton<? echo (!$arResult["DIFFERENT"] ? ' current' : ''); ?>" href="<? echo $arResult['COMPARE_URL_TEMPLATE'].'DIFFERENT=N'; ?>" rel="nofollow"><?=GetMessage("CATALOG_ALL_CHARACTERISTICS")?></a>
                <a class="sortbutton<? echo ($arResult["DIFFERENT"] ? ' current' : ''); ?>" href="<? echo $arResult['COMPARE_URL_TEMPLATE'].'DIFFERENT=Y'; ?>" rel="nofollow"><?=GetMessage("CATALOG_ONLY_DIFFERENT")?></a>
            </div>
			<div class="values">
				<div class="val">Цена</div>
				<? if (!empty($arResult["SHOW_PROPERTIES"])):
					foreach ($arResult["SHOW_PROPERTIES"] as $code => $arProperty):

                        $showRow = true;
                        if ($arResult['DIFFERENT'])
                        {
                            $arCompare = array();
                            foreach($arResult["ITEMS"] as $arElement)
                            {
                                $arPropertyValue = $arElement["DISPLAY_PROPERTIES"][$code]["VALUE"];
                                if (is_array($arPropertyValue))
                                {
                                    sort($arPropertyValue);
                                    $arPropertyValue = implode(" / ", $arPropertyValue);
                                }
                                $arCompare[] = $arPropertyValue;
                            }
                            unset($arElement);
                            $showRow = (count(array_unique($arCompare)) > 1);
                        }

                        if($showRow):
						$arrPropertyCode[] = $code;
						?>
				            <div class="val"><?=$arProperty["NAME"]?></div>
                        <?endif;?>
				<? 	endforeach;
				endif;
				?>
				<div class="val basket">&nbsp;</div>
			</div>
		</div><!--end::params-name-->

		<div class="compare-items cl">
			<div class="items">

				<?foreach($arResult["ITEMS"] as $arElement):?>
				<div class="item">
					<a href="?action=DELETE_FROM_COMPARE_LIST&id=<?=$arElement['ID']?>" class="delete"></a>
					<div class="info">
						<div class="img">
							<a href="#"><img src="<?=$arElement['PREVIEW_PICTURE']['SRC']?>" style="max-height: 170px;" alt="<?=$arElement["NAME"]?>" /></a>
						</div>
						<div class="name" style="min-height: 80px">
							<a href="<?=$arElement['DETAIL_PAGE_URL']?>"><?=$arElement["NAME"]?></a>
						</div>
					</div>
					<div class="values">
						<div class="val"><b>
								<?
								$ar_res = CCatalogProduct::GetOptimalPrice($arElement['ID'], 1, $USER->GetUserGroupArray(), 'N');
								echo $ar_res['DISCOUNT_PRICE'];
								?>
							</b> руб</div>
						<? for($i = 0;$i < count($arrPropertyCode);$i++): ?>
						<div class="val"><?=(is_array($arElement["DISPLAY_PROPERTIES"][$arrPropertyCode[$i]]["DISPLAY_VALUE"])? implode("/ ", $arElement["DISPLAY_PROPERTIES"][$arrPropertyCode[$i]]["DISPLAY_VALUE"]): $arElement["DISPLAY_PROPERTIES"][$arrPropertyCode[$i]]["DISPLAY_VALUE"])?></div>
						<? endfor; ?>

						<div class="val"><a class="add2basket" href="javascript:void(0)" onclick="addToBasket2(<?=$arElement['ID']?>, 1);">&nbsp;</a></div>
					</div>
				</div><div class="br"></div>
				<? endforeach; ?>

			</div>
		</div><!--end::compare-items-->
	</div><!--end::inn-->
</div><!--end::compare-page-->




<script type="text/javascript">
	var height = [];
	$('.compare-page .values .val').each(function(el,v){
		height[el] = $(v).height();
	});
	$('.compare-page .values .val').height(Math.max.apply(null, height));

	var CatalogCompareObj = new BX.Iblock.Catalog.CompareClass("bx_catalog_compare_block");
</script>
