<?
use \Bitrix\Main\Loader;

use \Arturgolubev\Ozon\Tools;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


if(!Loader::includeModule('arturgolubev.ozon') || !Loader::includeModule('iblock')){
	return false;
}

$arParams["ELEMENT_ID"] = IntVal($arParams["ELEMENT_ID"]);
if($arParams["ELEMENT_ID"] < 1) {
	global $USER; if($USER->IsAdmin()){
		ShowError("ELEMENT_ID IS NOT DEFINED");
	}
	return false;
}

if(!$arParams['OZON_ACCOUNT']){
	$arParams['OZON_ACCOUNT'] = '';
}

if(!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 360000;
}

if($this->StartResultCache(false, [])){	
	$sett = Tools::getCatalogSettings($arParams['OZON_ACCOUNT']);
	
	$arSelect = array("ID", "IBLOCK_ID");
	$rsElement = CIBlockElement::GetList(["ID" => "ASC"], ["ID" => $arParams["ELEMENT_ID"]], false, false, $arSelect);
	while($arElement = $rsElement->Fetch()) {
		$iblockID = $arElement['IBLOCK_ID'];
	}
	
	if($iblockID){
		$currentIblockSettings = $sett['IBLOCKS'][$iblockID];
		// echo '<pre>'; print_r($currentIblockSettings); echo '</pre>';
		
		$arSelect = array("ID", "IBLOCK_ID", $currentIblockSettings['OZONID_PROP'], 'PROPERTY_'.$currentIblockSettings['OZON_SKU_PROP']);
		$rsElement = CIBlockElement::GetList(["ID" => "ASC"], ["ID" => $arParams["ELEMENT_ID"]], false, false, $arSelect);
		while($arElement = $rsElement->Fetch()) {			
			$arResult['OZON_ARTICLE'] = $arElement[$currentIblockSettings['OZONID_PROP_VALUE']];
			$arResult['OZON_SKU'] = $arElement['PROPERTY_'.$currentIblockSettings['OZON_SKU_PROP'].'_VALUE'];
			
			$arResult['PRODUCT'] = $arElement;
		}
		
		if($arResult['OZON_ARTICLE'] && !$arResult['OZON_SKU']){
			$arResult['OZON_SKU'] = CArturgolubevOzon::getCardLink($arParams['OZON_ACCOUNT'], $arParams["ELEMENT_ID"]);
		}
	} 
	 
	if($arResult['OZON_SKU']){
		$arResult["LINK"] = 'https://www.ozon.ru/context/detail/id/'.$arResult['OZON_SKU'];
	}

	$this->SetResultCacheKeys(array(
		"PRODUCT",
		"LINK",
	));

	$this->IncludeComponentTemplate();
}
?>