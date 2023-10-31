<?
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Settings;
use \Arturgolubev\Ozon\Unitools as UTools;
use \Arturgolubev\Ozon\Tools;
use \Arturgolubev\Ozon\Admin\SettingsHelper as SHelper;

include $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/arturgolubev.ozon/lib/installation.php';

$module_id = 'arturgolubev.ozon';
$module_name = str_replace('.', '_', $module_id);

if(!Loader::includeModule($module_id)){
	include 'autoload.php';
}
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

CJSCore::Init(array("ag_ozon_options"));

global $APPLICATION;

$site_id = trim(htmlspecialcharsbx($_GET["sid"]));
$site_prefix = Tools::getPrefix($site_id);
$site_name = SHelper::checkSid($site_id);
if(!$site_name) return;

if(!Tools::checkRights($site_id, 'settings')) return;

$res = agInstaHelperOZON::checkMainStructure($site_id);

// button menu
$api_key = UTools::getSettingDB($site_id."_api_token");
if($api_key){
	$aMenu = array();
	$aMenu[] = array(
		"TEXT"=>Loc::getMessage("ARTURGOLUBEV_OZON_SMENU_SYSTEMS"),
		"TITLE"=>Loc::getMessage("ARTURGOLUBEV_OZON_SMENU_SYSTEMS"),
		"MENU" => array(
			array(
				"TEXT"=>Loc::getMessage("ARTURGOLUBEV_OZON_SMENU_GET_WAREHOUSE"),
				"LINK"=>"javascript: getWarehouseIDs('".$site_id."');",
				"TITLE"=>Loc::getMessage("ARTURGOLUBEV_OZON_SMENU_GET_WAREHOUSE"),
			),
			array(
				"TEXT"=>Loc::getMessage("ARTURGOLUBEV_OZON_SMENU_GET_LIMIT"),
				"LINK"=>"javascript: getAddCardLimit('".$site_id."');",
				"TITLE"=>Loc::getMessage("ARTURGOLUBEV_OZON_SMENU_GET_LIMIT"),
			),
		),
	);
	$context = new CAdminContextMenu($aMenu);
	$context->Show();
}


/* prepare settings */
$arRightsList = SHelper::getUserGroups();
$arCatalogInfo = SHelper::getCatalogs();
$arCatalogs = SHelper::getCatalogOptions($arCatalogInfo);
$arPrices = SHelper::getPrices();
$arCurrencyList = SHelper::getCurrencies();
$arStocks = SHelper::getStocksOptions();
$arStocksItems = SHelper::getStocksItems();

$arOrderOldTimesList = array(
	1 => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_1"),
	2 => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_2"),
	3 => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_3"),
	4 => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_4"),
	5 => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_5"),
	6 => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_6"),
);

$arAutosale = array(
	"" => Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT"),
	"ON" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_AUTOSALE_ON"),
	"OFF" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_AUTOSALE_OFF"),
	"PROP" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_AUTOSALE_PROP"),
);

$stores_count = UTools::getSettingDB($site_id.'_stores_count', 1);

$arOptions = array();
/* main tab */
$arOptions["main"] = array();
$arOptions["main"][] = array($site_prefix."admin_name", Loc::getMessage("ARTURGOLUBEV_OZON_ADMIN_NAME"), "", array("text"));
$arOptions["main"][] = Loc::getMessage("ARTURGOLUBEV_OZON_SELLER_BLOCK");
$arOptions["main"][] = array($site_id."_api_token", Loc::getMessage("ARTURGOLUBEV_OZON_API_TOKEN"), "", array("textarea"));
$arOptions["main"][] = array($site_id."_client_id", Loc::getMessage("ARTURGOLUBEV_OZON_CLIENT_ID"), "", array("text"));
$arOptions["main"][] = array($site_id."_stores_count", Loc::getMessage("ARTURGOLUBEV_OZON_STOCKS_COUNT"), "", array("text"));
for($i = 1; $i <= $stores_count; $i++){
	$arOptions["main"][] = array($site_id."_store_id_".$i, Loc::getMessage("ARTURGOLUBEV_OZON_SELLER_STOCK_ID", array("#num#" => $i)), "", array("text"));
}
$arOptions["main"][] = Loc::getMessage("ARTURGOLUBEV_OZON_SYSTEM_SETTINGS");
$arOptions["main"][] = array($site_id."_debug_ext", Loc::getMessage("ARTURGOLUBEV_OZON_SYSTEM_WRITE_SEND_DATA"), "", array("checkbox"));

/* catalog tab */
$arOptions["catalog"] = array();
$arOptions["catalog"][] = array($site_id."_catalog_id", Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_ID"), "", array("multiselectbox", $arCatalogs), false, Loc::getMessage('ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE'));
$iblocks = UTools::getSettingDB($site_id."_catalog_id");
if($iblocks){
	$iblocks = explode(',', $iblocks);
	
	foreach($iblocks as $ibId){
		agInstaHelperOZON::checkCatalogStructure($ibId, $site_id);
		
		$arOptions["catalog"][] = Loc::getMessage("ARTURGOLUBEV_OZON_PRODUCT_CATALOG_INDIVIDUAL").' "'.$arCatalogInfo[$ibId]["NAME"].'"';
		
		$arPropsSN = SHelper::getIbProps($ibId, array("S", "N"));
		$arPropsSN_F = array_replace(array(""=>Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_PROPERTY_TITLE")), SHelper::getIbProps($ibId, array("S", "N")));
		
		/* filter */
		$filterMess1 = Loc::getMessage('ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE');
		$filterMess2 = Loc::getMessage('ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT_REQUIRED').'! '.Loc::getMessage('ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE');
		
		$selectedCatalogFilter = UTools::getSettingDB($site_id."_catalog_filter_".$ibId);
		if($selectedCatalogFilter){
			if($selectedCatalogFilter == 'FILLED_IDS'){
				$res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $ibId, "!".UTools::getSettingDB($site_id."_catalog_ozonid_".$ibId) => false), false, array("nPageSize"=>1), array("ID"));
				$filterMess1 = $res->SelectedRowsCount() . Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_FILTER_COUNT") .' '. Loc::getMessage('ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE');
			}else{
				$selectedCatalogFilterValue = UTools::getSettingDB($site_id."_catalog_filter_value_".$ibId);
				if($selectedCatalogFilterValue){
					$res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $ibId, $selectedCatalogFilter => $selectedCatalogFilterValue), false, array("nPageSize"=>1), array("ID"));
					$filterMess2 = $res->SelectedRowsCount() . Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_FILTER_COUNT");
				}
			}
		}
		
		$arDopPropsFilter = array_replace(array(
			""=>Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT_PROP"),
			"FILLED_IDS"=>Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_FILTER_MAIN_IDS"),
		), SHelper::getIbProps($ibId, array("L")));
		
		$arOptions["catalog"][] = array($site_id."_catalog_filter_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_FILTER"), "", array("selectbox", $arDopPropsFilter), false, $filterMess1);
				
		if($selectedCatalogFilter && $selectedCatalogFilter != 'FILLED_IDS'){
			$arFilterVals = SHelper::getIbPropVals($ibId, $selectedCatalogFilter);
			$arOptions["catalog"][] = array($site_id."_catalog_filter_value_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_FILTER_VALUE"), "", array("selectbox", array_replace(array(""=>Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT_REQUIRED")), $arFilterVals)), false, $filterMess2);
		}
		/* end filter */
		
		$variantOzonIDProp = array_replace(
			array(
				""=> Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT"),
				"ID" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_OZON_ID_FORM_ID"),
				"XML_ID" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_OZON_ID_FORM_XML_ID")
			),
			$arPropsSN
		);
		
		$arOptions["catalog"][] = array($site_id."_catalog_ozonid_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_OZON_ID"), "", array("selectbox", $variantOzonIDProp));
		
		for($i = 1; $i <= $stores_count; $i++){
			$arOptions["catalog"][] = array($site_id."_catalog_stocks_".$ibId."_".$i, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_STOCKS", array("#num#"=>$i)), "", array("selectbox", array_replace($arStocks, $arPropsSN_F)));
			
			if(UTools::getSettingDB($site_id."_catalog_stocks_".$ibId."_".$i) == 'CATALOG_STORE_SUM'){
				$arOptions["catalog"][] = array($site_id."_catalog_stocks_sum_".$ibId."_".$i, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_STOCKS_STORE_SUM", array("#num#"=>$i)), "", array("multiselectbox", $arStocksItems));
			}
		}
		
		$arOptions["catalog"][] = array($site_id."_catalog_price_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE"), "", array("selectbox", array_replace($arPrices, $arPropsSN_F)));
		$arOptions["catalog"][] = array($site_id."_catalog_old_price_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_OLD"), "", array("selectbox", array_replace($arPrices, $arPropsSN_F)));
		$arOptions["catalog"][] = array($site_id."_catalog_min_price_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_MIN"), "", array("selectbox", array_replace($arPrices, $arPropsSN_F)));
		$arOptions["catalog"][] = array($site_id."_catalog_auto_sale_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_AUTOSALE"), "", array("selectbox", $arAutosale));
				
		$useConvertation = (!strstr(UTools::getSettingDB($site_id."_catalog_price_".$ibId), "PROPERTY_") || !strstr(UTools::getSettingDB($site_id."_catalog_old_price_".$ibId), "PROPERTY_") || !strstr(UTools::getSettingDB($site_id."_catalog_min_price_".$ibId), "PROPERTY_"));
		if($useConvertation){
			$arOptions["catalog"][] = array($site_id."_catalog_prices_convert_".$ibId, Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICES_CONVERT"), "", array("selectbox", $arCurrencyList));
		}
	}
}
$arOptions["catalog"][] = Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_OUR_SETTINGS");
$arOptions["catalog"][] = array($site_id."_catalog_stocks_deviation", Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_STOCKS_DEVIATION"), "0", array("text"));

/*
$arOptions["catalog"][] = array($site_id."_catalog_price_correction", Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION"), "", array("selectbox", array(
	"" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_NO"),
	"up_percent" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_UP_PERCENT"),
	"down_percent" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_DOWN_PERCENT"),
	"up_constant" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_UP_CONSTANT"),
	"down_constant" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_DOWN_CONSTANT"),
	// "formula" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_FORMULA"),
)));
$arOptions["catalog"][] = array($site_id."_catalog_price_correction_value", Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_VALUE"), "", array("text"));
*/


/* orders tab */
$arOptions["orders"] = array();
$arOptions["orders"][] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_CREATE_VARIANTS");
	if(!UTools::getSettingDB($site_id."_fbs_orders_get_time")){
		$arOptions["orders"][] = array($site_id."_orders_first_load", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FIRST_LOAD"), date('d.m.Y'), array("calendar"));
	}elseif(UTools::getSettingDB($site_id."_worker_fbs_orders") != 'Y' && UTools::getSettingDB($site_id."_fbs_orders_get_time")){
		
		if(!UTools::getSettingDB($site_id."_orders_next_load")){
			UTools::setSetting($site_id."_orders_next_load", date('d.m.Y H:i', UTools::getSettingDB($site_id."_fbs_orders_get_time")));
		}else{
			$tmp = abs(strtotime(UTools::getSettingDB($site_id."_orders_next_load")) - UTools::getSettingDB($site_id."_fbs_orders_get_time"));
			if($tmp > 60){
				UTools::setSetting($site_id."_orders_next_load", date('d.m.Y H:i', UTools::getSettingDB($site_id."_fbs_orders_get_time")));
			}
		}
		
		$arOptions["orders"][] = array($site_id."_orders_next_load", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NEXT_LOAD"), "", array("calendar", "Y"));
		
		/*
		echo '<pre>'; print_r(strtotime(UTools::getSettingDB($site_id."_orders_next_load"))); echo '</pre>';
		echo '<pre>next load '; print_r(UTools::getSettingDB($site_id."_orders_next_load")); echo '</pre>';
		echo '<pre>'; print_r(UTools::getSettingDB($site_id."_fbs_orders_get_time")); echo '</pre>';
		echo '<pre> last load '; print_r(date('d.m.Y H:i:s', UTools::getSettingDB($site_id."_fbs_orders_get_time"))); echo '</pre>';
		*/
	}
	$arOptions["orders"][] = array($site_id."_orders_noload_canceled", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOLOAD_CANCELED"), "", array("checkbox"));
	$arOptions["orders"][] = array($site_id."_orders_nofind_products", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS"), "", array("selectbox", array(
		"" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS_ERROR"),
		"S" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS_SKIP"),
		"N" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS_NONAME"),
	)));
	$arOptions["orders"][] = array($site_id."_orders_nostock_products", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOSTOCK_PRODUCTS"), "", array("selectbox", array(
		"" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOSTOCK_PRODUCTS_ERROR"),
		"N" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NOSTOCK_PRODUCTS_NONAME"),
	)));
	
$arOptions["orders"][] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_CREATE_SETTINGS");
	$arOptions["orders"][] = array($site_id."_orders_user_id", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_USER_ID"), "", array("text"));
	$arOptions["orders"][] = array($site_id."_orders_site_id", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_SITE_ID"), "", array("selectbox", SHelper::getSiteList()), false, Loc::getMessage('ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE'));

	$sid = UTools::getSettingDB($site_id."_orders_site_id");
	if($sid){
		$arOptions["orders"][] = array($site_id."_orders_person_type", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_PERSON_TYPE"), "", array("selectbox", SHelper::getPersonTypeList($sid)), false, Loc::getMessage('ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE'));
		
		$pid = UTools::getSettingDB($site_id."_orders_person_type");
		if($pid){
			for($i = 1; $i <= $stores_count; $i++){
				$storesDeliveryName = ($stores_count > 1) ? Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_DELIVERY_M", array("#store_id#" => $i)) : Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_DELIVERY");
				$arOptions["orders"][] = array($site_id."_orders_delivery_".$i, $storesDeliveryName, "", array("selectbox", SHelper::getDeliveryList($pid)));
			}
			
			$arOptions["orders"][] = array($site_id."_orders_paysystem", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_PAYSYSTEM"), "", array("selectbox", SHelper::getPaysystemList($pid)));
			
			agInstaHelperOZON::checkOrderStructure($pid);
			
			$arPropertyList = array_replace(array(""=>Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT")), SHelper::getPropertyList($pid));
			$arOptions["orders"][] = array($site_id."_prop_ozon_id", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_PROP_OZON_ID"), "", array("selectbox", $arPropertyList));
			$arOptions["orders"][] = array($site_id."_prop_send_data", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_PROP_SEND_DATA"), "", array("selectbox", $arPropertyList));
			
			$arOptions["orders"][] = GetMessage("ARTURGOLUBEV_OZON_ORDERS_RFBS_SETTINGS");
			$arOptions["orders"][] = array($site_id."_prop_rfbs_fio", GetMessage("ARTURGOLUBEV_OZON_ORDERS_PROP_RFBS_FIO"), "", array("selectbox", $arPropertyList));
			$arOptions["orders"][] = array($site_id."_prop_rfbs_phone", GetMessage("ARTURGOLUBEV_OZON_ORDERS_PROP_RFBS_PHONE"), "", array("selectbox", $arPropertyList));
			$arOptions["orders"][] = array($site_id."_prop_rfbs_address", GetMessage("ARTURGOLUBEV_OZON_ORDERS_PROP_RFBS_ADDRES"), "", array("selectbox", $arPropertyList));
		}
	}
	$arOptions["orders"][] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_UPDATE_SETTINGS");
	// $arOptions["orders"][] = array($site_id."_orders_final_userstatus", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_USERSTATUS"), "1,2,3,5", array("multiselectbox", $arUserStatusList));
	$arOptions["orders"][] = array($site_id."_orders_final_time", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME"), "3", array("selectbox", $arOrderOldTimesList));
	
	
	if(Loader::includeModule("crm")){
		$arOptions["orders"][] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_CRM_SETTINGS");
		$arOptions["orders"][] = array($site_id."_orders_crm_responsible", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_CRM_RESPONSIBLE"), "", array("text"));
	}

/* workers tab */
$arOptions["work"] = array();
$arOptions["work"][] = Loc::getMessage("ARTURGOLUBEV_OZON_API_STOCKS");
if(UTools::getSettingDB($site_id."_worker_stocks_empty") != 'Y'){
	$arOptions["work"][] = array($site_id."_worker_stocks", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS"), "", array("checkbox"));
	$arOptions["work"][] = array($site_id."_worker_stocks_changes", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_CHANGES"), "", array("checkbox"));
}else{
	$arOptions["work"][] = array($site_id."_worker_stocks", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS"), Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_LOCK"), array("statictext"));
	$arOptions["work"][] = array($site_id."_worker_stocks_changes", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_CHANGES"), Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_LOCK"), array("statictext"));
}
if(UTools::getSettingDB($site_id."_worker_stocks") != 'Y' && UTools::getSettingDB($site_id."_worker_stocks_changes") != 'Y'){
	$arOptions["work"][] = array($site_id."_worker_stocks_empty", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_EMPTY"), "", array("checkbox"));
}else{
	$arOptions["work"][] = array($site_id."_worker_stocks_empty", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_EMPTY"), Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_STOCKS_EMPTY_LOCK"), array("statictext"));
}

$arOptions["work"][] = Loc::getMessage("ARTURGOLUBEV_OZON_API_PRICES");
$arOptions["work"][] = array($site_id."_worker_prices", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_PRICES"), "", array("checkbox"));
$arOptions["work"][] = array($site_id."_worker_prices_changes", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_PRICES_CHANGES"), "", array("checkbox"));

$arOptions["work"][] = Loc::getMessage("ARTURGOLUBEV_OZON_API_ORDERS");
$arOptions["work"][] = array($site_id."_worker_fbs_orders", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_FBS_ORDERS"), "", array("checkbox"));
$arOptions["work"][] = array($site_id."_worker_fbs_orders_update", Loc::getMessage("ARTURGOLUBEV_OZON_WORKER_FBS_ORDERS_UPDATE"), "", array("checkbox"));

if(UTools::getSettingDB($site_id."_worker_fbs_orders_update") == 'Y'){
	$arOptions["work"][] = array($site_id."_order_update_retuns", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_UPDATE_RETURNS"), "", array("checkbox"));
}

if($sid){
	$arStatusList = SHelper::getStatusList(SHelper::getSiteLang($sid));
	$arFlagList = SHelper::getFlagList();
	
	$arOptions["status"][] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_MAP");
	
	$arOptions["status"][] = array($site_id."_order_autostat_awaiting_packaging", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_AWAITING_PACKAGING"), "", array("selectbox", $arStatusList));
	$arOptions["status"][] = array($site_id."_order_autostat_awaiting_deliver", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_AWAITING_DELIVER"), "", array("selectbox", $arStatusList));
	$arOptions["status"][] = array($site_id."_order_autostat_cancelled", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_CANCELLED"), "", array("selectbox", $arStatusList));
	$arOptions["status"][] = array($site_id."_order_autostat_delivering", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_DELIVERING"), "", array("selectbox", $arStatusList));
	$arOptions["status"][] = array($site_id."_order_autostat_delivered", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_DELIVERED"), "", array("selectbox", $arStatusList));
	
	if(UTools::getSettingDB($site_id."_worker_fbs_orders_update") == 'Y' && UTools::getSettingDB($site_id."_order_update_retuns") == 'Y'){
		$arOptions["status"][] = array($site_id."_order_autostat_returned_to_seller", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_RETURNED_TO_SELLER"), "", array("selectbox", $arStatusList));
		$arOptions["status"][] = array($site_id."_order_autostat_waiting_for_seller", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_WAITING_FOR_SELLER"), "", array("selectbox", $arStatusList));
		$arOptions["status"][] = array($site_id."_order_autostat_accepted_from_customer", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_ACCEPTED_FROM_CUSTOMER"), "", array("selectbox", $arStatusList));
		$arOptions["status"][] = array($site_id."_order_autostat_cancelled_with_compensation", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_CANCELLED_WITH_COMPENSATION"), "", array("selectbox", $arStatusList));
		$arOptions["status"][] = array($site_id."_order_autostat_ready_for_shipment", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_READY_FOR_SHIPMENT"), "", array("selectbox", $arStatusList));
	}
	
	$arOptions["status"][] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_FLAG_MAP");
	$arOptions["status"][] = array($site_id."_order_autoflag_awaiting_packaging", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_AWAITING_PACKAGING"), "", array("selectbox", $arFlagList));
	$arOptions["status"][] = array($site_id."_order_autoflag_awaiting_deliver", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_AWAITING_DELIVER"), "", array("selectbox", $arFlagList));
	$arOptions["status"][] = array($site_id."_order_autoflag_cancelled", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_CANCELLED"), "", array("selectbox", $arFlagList));
	$arOptions["status"][] = array($site_id."_order_autoflag_delivering", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_DELIVERING"), "", array("selectbox", $arFlagList));
	$arOptions["status"][] = array($site_id."_order_autoflag_delivered", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_DELIVERED"), "", array("selectbox", $arFlagList));
	
	if(UTools::getSettingDB($site_id."_worker_fbs_orders_update") == 'Y' && UTools::getSettingDB($site_id."_order_update_retuns") == 'Y'){
		$arOptions["status"][] = array($site_id."_order_autoflag_returned_to_seller", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_RETURNED_TO_SELLER"), "", array("selectbox", $arFlagList));
		$arOptions["status"][] = array($site_id."_order_autoflag_waiting_for_seller", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_WAITING_FOR_SELLER"), "", array("selectbox", $arFlagList));
		$arOptions["status"][] = array($site_id."_order_autoflag_accepted_from_customer", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_ACCEPTED_FROM_CUSTOMER"), "", array("selectbox", $arFlagList));
		$arOptions["status"][] = array($site_id."_order_autoflag_cancelled_with_compensation", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_CANCELLED_WITH_COMPENSATION"), "", array("selectbox", $arFlagList));
		$arOptions["status"][] = array($site_id."_order_autoflag_ready_for_shipment", Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_READY_FOR_SHIPMENT"), "", array("selectbox", $arFlagList));
	}
}


$arOptions["rights"][] = array($site_id."_rights_settings", Loc::getMessage("ARTURGOLUBEV_OZON_RIGHTS_SETTINGS"), Loc::getMessage("ARTURGOLUBEV_OZON_RIGHTS_SETTINGS_TEXT"), array("statictext"));
$arOptions["rights"][] = array($site_id."_rights_order", Loc::getMessage("ARTURGOLUBEV_OZON_RIGHTS_ORDER"), "", array("multiselectbox", $arRightsList));

$arTabs = array(
	array("DIV" => "settings_tab", "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_MAIN_TAB"), "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_MAIN_TAB"), "OPTIONS"=>"main"),
	array("DIV" => "catalog_tab", "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_TAB"), "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_TITLE_TAB"), "OPTIONS"=>"catalog"),
	array("DIV" => "orders_tab", "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_TAB"), "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_TITLE_TAB"), "OPTIONS"=>"orders"),
	array("DIV" => "work_tab", "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_EXCHANGES_TAB"), "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_EXCHANGES_TAB"), "OPTIONS"=>"work"),
	array("DIV" => "status_tab", "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_AUTO_STATUS_TAB"), "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AUTO_STATUS_TAB_TITLE"), "OPTIONS"=>"status"),
	array("DIV" => "rights_tab", "TAB" => Loc::getMessage("ARTURGOLUBEV_OZON_RIGHTS_TAB"), "TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_RIGHTS_TAB"), "OPTIONS"=>"rights")
);
$tabControl = new CAdminTabControl("tabControl", $arTabs);

// ****** SaveBlock
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply)>0 && check_bitrix_sessid())
{
	if(true){
		AddMessage2Log($_REQUEST, 'AGOZ save options REQUEST', 0);
	}
	
	// echo '<pre>'; print_r($site_id); echo '</pre>';
	// echo '<pre>'; print_r($_REQUEST); echo '</pre>';
	
	if($_REQUEST[$site_id.'_orders_next_load']){
		$tmp = strtotime($_REQUEST[$site_id.'_orders_next_load']);
		$tmp2 = abs($tmp - UTools::getSettingDB($site_id.'_fbs_orders_get_time'));
		
		// echo '<pre>tmp '; print_r($tmp); echo '</pre>';
		// echo '<pre>tmp2 '; print_r($tmp2); echo '</pre>';
		
		if(intval($tmp) > 1600000000 && $tmp2 > 60){
			Tools::toDbLog(
				$site_id,
				"ORDERS_NEW_GET_TIME",
				Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_NEW_GET_TIME", array("#date#" => $_REQUEST[$site_id.'_orders_next_load'], '#time#' => $tmp))
			);
				
			UTools::setSetting($site_id.'_fbs_orders_get_time', $tmp);
		}
	}
	// return;
	
	if(isset($_REQUEST[$site_id."_stores_count"])){
		$_REQUEST[$site_id."_stores_count"] = intval($_REQUEST[$site_id."_stores_count"]);
		if($_REQUEST[$site_id."_stores_count"] < 1) $_REQUEST[$site_id."_stores_count"] = 1;
	}
	
	foreach ($arOptions as $aOptGroup) {
		foreach ($aOptGroup as $option) {
			Settings::AdmSettingsSaveOption($module_id, $option);
		}
	}
	
	SHelper::checkSaveAgents($site_id);
	
    if (strlen($Update) > 0 && strlen($_REQUEST["back_url_settings"]) > 0)
        LocalRedirect($_REQUEST["back_url_settings"]);
    else
        LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&sid=" . htmlspecialchars($_REQUEST['sid']) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $tabControl->ActiveTabParam());
}

SHelper::agentsTestStart($site_id);
?>

<div class="ag_options">
	<?
	SHelper::checkModuleRules();
	$name = UTools::getSettingDB($site_prefix.'admin_name');
	?>
	
	<div class="ag_options_account_title"><?=Loc::getMessage("ARTURGOLUBEV_OZON_SITE_SETTING_TITLE", array("#sid#" => ($name) ? $name : $site_id))?></div>


	<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&lang=<?=LANGUAGE_ID?>&sid=<?=htmlspecialchars($_REQUEST['sid'])?>">
		<?$tabControl->Begin();?>
		
		<?foreach($arTabs as $key=>$tab):
			$tabControl->BeginNextTab();
				Settings::showSettingsList($module_id, $arOptions, $tab);
		endforeach;?>
		
		<?$tabControl->Buttons();?>
			<input type="submit" name="Update" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
			<input type="hidden" name="Update" value="Y">
			
			<?if(strlen($_REQUEST["back_url_settings"])>0):?>
				<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
			<?endif?>
			
			<?=bitrix_sessid_post();?>
		<?$tabControl->End();?>
	</form>

	<?Settings::showInitUI();?>
</div>