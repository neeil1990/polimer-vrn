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
$this->setFrameMode(true);
?>

<??>
<? global $USER; ?>
<? if($USER->IsAuthorized()): ?>
<h1>Уважаемый, <? echo $USER->GetFullName(); ?>!</h1>
<?else:?>
<h1>Здравствуйте!</h1>
<?endif;?>

<p>
	Спасибо, что выбрали именно наш магазин! Мы постоянно стремимся улучшить качество сервиса для удобства клиентов. И нам важно знать Ваше мнение. Пожалуйста, уделите несколько минут, чтобы оценить работу нашего магазина. Если у вас есть какие-то пожелания или замечания обязательно сообщите – это поможет нам стать лучше для Вас и других покупателей!
</p>

<a href="#" class="reviews-btn show-popup" data-id="reviews"></a>

<div class="reviews_page">

<?foreach($arResult["ITEMS"] as $arItem):?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	?>

	<div class="item">
		<div class="author_block">
			<div class="name"><?=$arItem['PROPERTIES']['FIO']['VALUE'];?></div>
			<div class="clear"></div>
			<div class="about">
				<?=$arItem['PROPERTIES']['SITY']['VALUE'];?>
				<br>
				Добавлен: <?=$arItem['ACTIVE_FROM'];?>
			</div>
		</div>
		<div class="text_block"><?=$arItem['PROPERTIES']['MESSAGE']['VALUE'];?></div>
		<div class="clear"></div>

	</div>


<?endforeach;?>
<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
	<br /><?=$arResult["NAV_STRING"]?>
<?endif;?>
</div>
