<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!isset($arParams["CACHE_TIME"])) {
	$arParams["CACHE_TIME"] = 3600;
}

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if($this->StartResultCache(false, array()))
{
	if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("arturgolubev.ecommerce")) {
		$this->AbortResultCache();
		return false;
	}
	
	if($arParams["PRODUCT_ID"])
	{
		$arResult["JS_CODE"] = CArturgolubevEcommerce::getDetailCode($arParams["PRODUCT_ID"], $arParams["OFFERS_CART_PROPERTIES"]);
	}

	$this->SetResultCacheKeys(array("JS_CODE"));
	$this->IncludeComponentTemplate();
}
?>