<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$normalCount = count($arResult["ITEMS"]["AnDelCanBuy"]);
if (strlen($arResult["ERROR_MESSAGE"]) <= 0)
{
	include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/basket_items.php");
}
else
{
	ShowError($arResult["ERROR_MESSAGE"]);
}?>