<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?if (!empty($arResult)):?>
<div class="cl">
<?
$count  = sizeof($arResult);

foreach($arResult as $key => $arItem):
	if($key == 0){
	?>
	<ul class="footer__list footer__list--50">
	<?}?>
	<?if(($key) == round($count/2)){?>
	</ul>
	<ul class="footer__list footer__list--50">
	<?}?>
	<?if($arParams["MAX_LEVEL"] == 1 && $arItem["DEPTH_LEVEL"] > 1) 
		continue;
?>
	<?if($arItem["SELECTED"]):?>
		<li class="active"><a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a></li>
	<?else:?>
		<li><a href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a></li>
	<?endif?>
<?endforeach?>
	</ul>
</div>
<?endif?>