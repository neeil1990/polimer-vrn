<?
use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
// echo '<pre>'; print_r($arResult); echo '</pre>';
// echo '<pre>'; print_r($arParams); echo '</pre>';
?>

<?if($arResult['LINK']):?>
	<div class="buy-on-oz">
		<?=($arParams["TEXT_BEFORE_BTN"]) ? '<div class="buy-on-oz-text">'.$arParams["TEXT_BEFORE_BTN"].'</div>' : '';?>
		<a target="_blank" class="buy-on-oz-link" href="<?=$arResult['LINK']?>"><?=($arParams["TEXT_BTN"]) ? $arParams["TEXT_BTN"] : Loc::getMessage("ARTURGOLUBEV_OZON_TEMPLATE_BUTTON");?></a>
	</div>
<?endif;?>