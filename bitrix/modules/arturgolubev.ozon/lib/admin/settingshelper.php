<?
namespace Arturgolubev\Ozon\Admin;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Bitrix\Main\Config\Option;

use \Arturgolubev\Ozon\Unitools as UTools;
// use \Arturgolubev\Ozon\Encoding;

class SettingsHelper {
	const MODULE_ID = 'arturgolubev.ozon';
	
	/* tests */
	static function agentsTestStart($sid){
		$timeMaps = array();
		$timeMaps['start'] = microtime(true);
		
		if(true){
			// stocks
			// \CArturgolubevOzon::agentStocksFull($sid);
			// \CArturgolubevOzon::agentStocksChanges($sid);
			// \CArturgolubevOzon::agentStocksEmpty($sid);
			
			// prices
			// \CArturgolubevOzon::agentPricesFull($sid);
			// \CArturgolubevOzon::agentPricesChanges($sid);
			
			// orders
			// \CArturgolubevOzon::agentOrdersGet($sid);
			// \CArturgolubevOzon::agentOrdersUpdate($sid);
		}
		
		$timeMaps['agentwork'] = round((microtime(true) - $timeMaps['start']), 4);
		
		// echo '<pre>'; print_r($timeMaps); echo '</pre>';
		
		self::queryTestStart($sid);
	}
	
	static function queryTestStart($sid){
		/* $api = \Arturgolubev\Ozon\Tools::getTokens($sid);
		
		$sendData = array(
			'filter' => array(
				'warehouse_id' => 23492939870000
			),
			'limit' => 100,
			'offset' => 0,
		);
		
		$data = \Arturgolubev\Ozon\Tools::sendRequest("https://api-seller.ozon.ru/v1/delivery-method/list", $sendData, $api, "POST");
		if($data["header"]["http_code"] == 200){
			$data["result"] = \Bitrix\Main\Web\Json::Decode($data["result"]);
		}
		
		echo '<pre>'; print_r($data); echo '</pre>'; */
	}
	
	
	
	/* */
	static function checkSid($sid){
		$siteList = array();
		$activeSite = 0;
		$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
		while($arRes = $rsSites->Fetch()){
			$key = \COption::GetOptionString(self::MODULE_ID, $arRes["ID"].'_api_token');
			
			if($key && !$activeSite){
				$activeSite = $arRes["ID"];
			}
			
			$siteList[] = array(
				"ID" => $arRes["ID"],
				"NAME" => $arRes["NAME"],
			);
		}
		
		foreach($siteList as $k=>$v){
			if($v['ID'] == $sid){
				$site_name = $v["NAME"];
			}
		}
		
		if(!$site_name){
			if($activeSite){
				LocalRedirect('/bitrix/admin/settings.php?lang='.LANG.'&mid='.self::MODULE_ID.'&sid='.$activeSite);
			}elseif($siteList[0]){
				LocalRedirect('/bitrix/admin/settings.php?lang='.LANG.'&mid='.self::MODULE_ID.'&sid='.$siteList[0]['ID']);
			}else{
				?><div class=""><?=Loc::getMessage("ARTURGOLUBEV_OZON_SITE_ID_INCORRECT")?></div><?
			}
		}
		
		return $site_name;
	}
	
	static function checkSaveAgents($site_id){
		$arCheck = array(
			'worker_stocks' => 'stocks_full',
			'worker_stocks_changes' => 'stocks_changes',
			'worker_stocks_empty' => 'stocks_empty',
			'worker_prices' => 'prices_full',
			'worker_prices_changes' => 'prices_changes',
			// 'worker_sales_changes' => 'sales_changes',
			'worker_fbs_orders' => 'orders_get',
			'worker_fbs_orders_update' => 'orders_update',
		);
		
		if((UTools::getSettingDB($site_id.'_worker_stocks') == 'Y' || UTools::getSettingDB($site_id.'_worker_stocks_changes') == 'Y') && UTools::getSettingDB($site_id.'_worker_stocks_empty') == 'Y'){
			UTools::setSetting("worker_stocks_empty", "N");
		}
		
		foreach($arCheck as $k=>$v){
			if(UTools::getSettingDB($site_id.'_'.$k) == 'Y'){
				\CArturgolubevOzon::agentWork($site_id, "add", $v);
			}else{
				\CArturgolubevOzon::agentWork($site_id, "del", $v);
			}
		}
	}
	
	static function checkModuleRules(){
		$arSearchNoteSettings = array();

		if (!function_exists('curl_init')){
			$arSearchNoteSettings[] = Loc::getMessage("ARTURGOLUBEV_OZON_CURL_NOT_FOUND");
		}
		if (!Loader::includeModule("catalog") || !Loader::includeModule("sale")){
			$arSearchNoteSettings[] = Loc::getMessage("ARTURGOLUBEV_OZON_SALE_NOT_FOUND");
		}
		if (!(defined("BX_CRONTAB_SUPPORT") && BX_CRONTAB_SUPPORT===true)){
			$arSearchNoteSettings[] = Loc::getMessage("ARTURGOLUBEV_OZON_AGENTS_NOT_CRON");
		}
		
		$logDays = IntVal(Option::get("main", "event_log_cleanup_days"));
		if(!$logDays){
			$arSearchNoteSettings[] = Loc::getMessage("ARTURGOLUBEV_OZON_MAIN_CLEAR_LOG_NOSET");
		}

		if(count($arSearchNoteSettings)>0){
			\CAdminMessage::ShowMessage(array("DETAILS"=>implode('<br>', $arSearchNoteSettings), "MESSAGE" => Loc::getMessage("ARTURGOLUBEV_OZON_ERROS_SETTING_TITLE"), "HTML"=>true));
		}

		if(!Loader::includeModule(self::MODULE_ID)){
			\CAdminMessage::ShowMessage(array("DETAILS"=>Loc::getMessage("ARTURGOLUBEV_OZON_DEMO_IS_EXPIRED"), "HTML"=>true));
		}
	}
	
	
	/* simle get */
	static function getUserGroups(){
		$items = ['' => Loc::getMessage('ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT')];
		
		$arSkip = array(1, 2, 3, 4, 5);
		
		$rsGroups = \CGroup::GetList($by = "c_sort", $order = "asc", array());
		while($arGroups = $rsGroups->Fetch()){
			if(in_array($arGroups["ID"], $arSkip)) continue;
			$items[$arGroups["ID"]] = $arGroups["NAME"].' ['.$arGroups["ID"].']';
		}
		
		return $items;
	}
	static function getSiteList(){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT");
		$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
		while($arRes = $rsSites->Fetch()){
			$items[$arRes["ID"]] = $arRes["NAME"].' ['.$arRes["ID"].']';
		}
		return $items;
	}
	static function getSiteLang($sid){
		$rsSites = \CSite::GetList($by="sort", $order="asc", Array("ID"=>$sid));
		while($arRes = $rsSites->Fetch()){
			return $arRes["LANGUAGE_ID"];
		}
	}
	
	static function getCatalogs(){
		$arCatalogInfo = array();
		if(Loader::includeModule("catalog")){
			$res = \CCatalog::GetList(Array(), Array('IBLOCK_ACTIVE'=>'Y'), false, false, array("*", "OFFERS"));
			while($ar_res = $res->Fetch()){
				$arCatalogInfo[$ar_res["IBLOCK_ID"]] = $ar_res;
			}
		}
		return $arCatalogInfo;
	}
	static function getPrices(){
		$arPrices = array();
		if(Loader::includeModule("catalog")){
			$arPrices[] = Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT");
			$arPrices["OPTIMAL"] = Loc::getMessage("ARTURGOLUBEV_OZON_PRICE_OPTIMAL");
			$dbPriceType = \CCatalogGroup::GetList(array("SORT" => "ASC"), array());
			while ($arPriceType = $dbPriceType->Fetch())
			{
				$arPrices[$arPriceType["ID"]."_SALES"] = '['.$arPriceType["ID"].'] '.$arPriceType["NAME_LANG"].Loc::getMessage("ARTURGOLUBEV_OZON_PRICE_W_SALES");
				$arPrices[$arPriceType["ID"]."_WO_SALES"] = '['.$arPriceType["ID"].'] '.$arPriceType["NAME_LANG"].Loc::getMessage("ARTURGOLUBEV_OZON_PRICE_WO_SALES");
			}
		}
		return $arPrices;
	}
	
	static function getIbProps($ibId, $arTypes = array()){
		$arDopProps = array();
		
		$excludeProp = \Arturgolubev\Ozon\Maps::getCatalogSystemPropMap();
		
		$properties = \CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "!CODE"=>false, "MULTIPLE"=>"N", "IBLOCK_ID"=>$ibId));
		while ($prop_fields = $properties->GetNext()){
			$prop_fields["CODE"] = strtoupper($prop_fields["CODE"]);
			
			if(!in_array($prop_fields["PROPERTY_TYPE"], $arTypes)) continue;
			if($prop_fields["USER_TYPE"] || !$prop_fields["CODE"]) continue;
			if(in_array($prop_fields["CODE"], $excludeProp)) continue;
			
			if($prop_fields["CODE"]){
				$arDopProps["PROPERTY_".$prop_fields["CODE"]] = '['.$prop_fields["ID"].'] '.$prop_fields["NAME"];
			}
		}
		return $arDopProps;
	}
	static function getIblockProperty($ibId, $arTypes = array()){
		$arDopProps = array();
		$properties = \CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "!CODE"=>false, "IBLOCK_ID"=>$ibId));
		while ($prop_fields = $properties->GetNext()){
			if(!in_array($prop_fields["PROPERTY_TYPE"], $arTypes)) continue;
			if($prop_fields["USER_TYPE"]) continue;
			
			$arDopProps[$prop_fields["ID"]] = '['.$prop_fields["ID"].'] '.$prop_fields["NAME"];
		}
		return $arDopProps;
	}
	static function getIbPropVals($ib, $pid){
		$arDopProps = array();
		$property_enums = \CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$ib, "CODE"=>str_replace('PROPERTY_', '', $pid)));
		while($enum_fields = $property_enums->GetNext())
		{
			$arDopProps[$enum_fields["ID"]] = $enum_fields["VALUE"];
		}
		return $arDopProps;
	}
	/* get catalog */
	static function getCatalogOptions($catalogList){
		$items = array();
		foreach($catalogList as $catalog){
			$items[$catalog["IBLOCK_ID"]] = '['.$catalog["ID"].'] '.$catalog["NAME"];
		}
		return $items;
	}
	static function getStocksOptions(){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT");
		$items["CATALOG_QUANTITY"] = Loc::getMessage("ARTURGOLUBEV_OZON_STOCKS_CATALOG_QUANTITY");

		if(Loader::includeModule("catalog")){
			$dbStores = \CCatalogStore::GetList(
			   array('ID' => 'ASC'),
			   array('ACTIVE' => 'Y'),
			   false,
			   false,
			   array("ID", "TITLE", "*")
			);
			while ($arStore = $dbStores->Fetch()){
				$items["CATALOG_STORE_AMOUNT_".$arStore["ID"]] = Loc::getMessage("ARTURGOLUBEV_OZON_STOCKS_CATALOG_STORE_AMOUNT", array("#NAME#" => $arStore["TITLE"]));
			}
			
			if(count($items) > 3){
				$items["CATALOG_STORE_SUM"] = Loc::getMessage("ARTURGOLUBEV_OZON_STOCKS_CATALOG_STORE_SUM");
			}
		}
		
		return $items;
	}
	
	static function getStocksItems(){
		$items = array();
		if(Loader::includeModule("catalog")){
			$dbStores = \CCatalogStore::GetList(
			   array('ID' => 'ASC'),
			   array('ACTIVE' => 'Y'),
			   false,
			   false,
			   array("ID", "TITLE", "*")
			);
			while ($arStore = $dbStores->Fetch()){
				$items[$arStore["ID"]] = $arStore["TITLE"].' ['.$arStore["ID"].']';
			}
		}
		
		return $items;
	}
	
	/* sale */
	static function getStatusList($sid){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_NULL");
		if(Loader::includeModule("sale")){
			$rsStatus = \CSaleStatus::GetList(array("SORT" => "ASC"),array("LID"=>$sid, "TYPE"=>"O"),false,false,array("ID", "TYPE", "SORT", "LID", "NAME", "*"));
			while($arRes = $rsStatus->Fetch()){
				$items[$arRes["ID"]] = $arRes["NAME"].' ['.$arRes["ID"].']';
			}
		}
		return $items;
	}
	static function getFlagList(){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_NULL");
		$items["CANCELED"] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_CANCELED");
		$items["DEDUCTED"] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_DEDUCTED");
		$items["PAYED"] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_PAYED");
		return $items;
	}
	static function getCurrencies(){
		$arCurrencyList = array();
		$arCurrencyList[] = Loc::getMessage("ARTURGOLUBEV_OZON_CATALOG_PRICES_CONVERT_NO_CONVERT");
		$arCurrencyAllowed = array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');
		if(Loader::includeModule("sale")){
			$dbRes = \CCurrency::GetList($by = 'sort', $order = 'asc');
			while ($arRes = $dbRes->GetNext())
			{
				if (in_array($arRes['CURRENCY'], $arCurrencyAllowed))
					$arCurrencyList[$arRes['CURRENCY']] = $arRes['FULL_NAME'];
			}
		}
		return $arCurrencyList;
	}
	static function getPersonTypeList($siteId){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT");
		if(Loader::includeModule("sale")){
			$db_ptype = \CSalePersonType::GetList(Array("SORT" => "ASC"), Array("LID"=>$siteId));
			while ($ptype = $db_ptype->Fetch()){
				$items[$ptype["ID"]] = $ptype["NAME"];
			}
		}
		return $items;
	}
	static function getDeliveryList($personType){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT");
		if(Loader::includeModule("sale")){
			$db_ptype = \CSaleDelivery::GetList(Array("SORT" => "ASC"), Array("ACTIVE" => "Y", "PERSON_TYPE_ID"=>$personType));
			while ($ptype = $db_ptype->Fetch()){
				$items[$ptype["ID"]] = $ptype["NAME"];
			}
		}
		return $items;
	}
	static function getPaysystemList($personType){
		$items = array();
		$items[] = Loc::getMessage("ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT");
		if(Loader::includeModule("sale")){
			$db_ptype = \CSalePaySystem::GetList(Array("SORT" => "ASC"), Array("ACTIVE" => "Y", "PERSON_TYPE_ID"=>$personType));
			while ($ptype = $db_ptype->Fetch()){
				$items[$ptype["ID"]] = $ptype["NAME"];
			}
		}
		return $items;
	}
	static function getPropertyList($person){
		$arSystemProps = \Arturgolubev\Ozon\Maps::getOrderSystemProps();
		
		$items = array();
		$filter = array("ACTIVE"=>"Y", "PERSON_TYPE_ID" => $person);
		$db_props = \CSaleOrderProps::GetList(array("SORT" => "ASC"), $filter, false, false, array());
		while ($props = $db_props->Fetch()){
			if($props['TYPE'] == 'LOCATION' || !$props['CODE']) continue;
			if(in_array($props['CODE'], $arSystemProps)) continue;
			
			// echo '<pre>'; print_r($props); echo '</pre>';
			$items[$props["CODE"]] = $props["NAME"];
		}
		return $items;
	}
}