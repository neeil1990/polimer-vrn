<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>

	<ul class="mm__category cl">

	<?
	foreach($arResult as $arItem):
		if($arParams["MAX_LEVEL"] == 1 && $arItem["DEPTH_LEVEL"] > 1)
			continue;
		?>
		<li class="maincategory maincategory--1 cl">
			<a href="<?=$arItem["LINK"]?>">
				<span class="img"></span>
				<span class="name"><?=$arItem["TEXT"]?></span>
			</a>
		</li>
	<?endforeach?>

	</ul>

<?endif?>


