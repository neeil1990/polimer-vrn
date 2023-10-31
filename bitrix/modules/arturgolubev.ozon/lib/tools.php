<?
namespace Arturgolubev\Ozon;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Unitools as UTools;
// use \Arturgolubev\Ozon\Encoding;

class Tools {
	const MODULE_ID = 'arturgolubev.ozon';
	
	/* helper */
	static function checkGlobalUser(){
		global $USER;
		if(!is_object($USER)){
			$USER = new \CUser();
		}
	}
	
	/* menu */
	static function getMenuIntegrationsList(){
		$items = [];
		
		$arSites = self::getSiteList();
		foreach($arSites as $k=>$site){
			$site['custom_name'] = UTools::getSetting($site["ID"].'_admin_name');
			if(!$site['custom_name']){
				$site['custom_name'] = Loc::getMessage("ARTURGOLUBEV_OZON_SUBMENU_LK").($k+1);
			}
			
			$site['token'] = UTools::getSetting($site["ID"].'_api_token');
			
			if(!$site['token'] && $site['ACTIVE'] == 'N'){
				continue;
			}
			
			$site['rights_settings'] = self::checkRights($site["ID"], 'settings');
			$site['rights_order'] = self::checkRights($site["ID"], 'order');
			
			$items[] = $site;
		}
		
		return $items;
	}
	
	/* getters */
	static function getSiteList(){
		$items = [];
		
		$rsSites = \CSite::GetList($by="sort", $order="asc", []);
		while($arRes = $rsSites->Fetch()){
			$items[] = ["ID" => $arRes["ID"], "NAME" => $arRes["NAME"], 'ACTIVE' => $arRes['ACTIVE']];
		}
		
		return $items;
	}
	
	/* rights */
	static function checkRights($sid, $dir){
		global $USER;
		self::checkGlobalUser();
		
		if($USER->IsAdmin()) return 1;

		if($dir == 'settings'){
			// only admin
		}else{
			$arGroups = $USER->GetUserGroupArray();
			$dirGroups = explode(',', UTools::getSetting($sid.'_rights_'.$dir));
			foreach($dirGroups as $dg){
				if(in_array($dg, $arGroups)){
					return 1;
				}
			}
		}
		
		return 0;
	}
	
	static function checkRightsAny($dir){
		$arSiteList = self::getSiteList(array('include_base' => 1));
		foreach($arSiteList as $site){
			if(self::checkRights($site['ID'], $dir)){
				return 1;
			}
		}
		
		return 0;
	}
	
	
	/* options */
	static function getPrefix($sid){
		return $sid.'_';
	}
	
	static function onDebug($sid){
		return (UTools::getSetting($sid.'_debug_ext') == 'Y');
	}
	
	static function getTokens($sid){
		return array(
			'key' => UTools::getSetting($sid.'_api_token'),
			'client_id' => UTools::getSetting($sid.'_client_id'),
		);
	}
	
	static function _getCatalogParams($sid){
		$result = array();
		
		$arIblocks = UTools::getSetting($sid.'_catalog_id');
		if($arIblocks){
			$arIblocks = explode(',', $arIblocks);
			foreach($arIblocks as $ibID){
				$tmp = array(
					'IBLOCK_ID' => $ibID,
					'FILTER_PROP' => UTools::getSetting($sid.'_catalog_filter_'.$ibID),
					'FILTER_PROP_VALUE' => UTools::getSetting($sid.'_catalog_filter_value_'.$ibID),
					'PRICES' => array(
						'MAIN' => UTools::getSetting($sid.'_catalog_price_'.$ibID),
						'OLD' => UTools::getSetting($sid.'_catalog_old_price_'.$ibID),
						'MIN' => UTools::getSetting($sid.'_catalog_min_price_'.$ibID),
						'AUTOSALE' => UTools::getSetting($sid.'_catalog_auto_sale_'.$ibID),
						'CONVERT' => UTools::getSetting($sid.'_catalog_prices_convert_'.$ibID),
					),
				);
				
				$tmp['OZONID_PROP'] = UTools::getSetting($sid.'_catalog_ozonid_'.$ibID);
				$tmp['OZONID_PROP_VALUE'] = (strpos($tmp['OZONID_PROP'], 'PROPERTY_') !== false) ? $tmp['OZONID_PROP'].'_VALUE' : $tmp['OZONID_PROP'];
				
				$tmp['OZON_SKU_PROP'] = "OZ_SKU_".strtoupper($sid);
				
				foreach(array('MAIN', 'OLD', 'MIN') as $price){
					if($tmp['PRICES'][$price]){
						$type = 'STANDART';
						
						if(strstr($tmp['PRICES'][$price], "PROPERTY_")){
							$type = 'PROP';
						}elseif(strstr($tmp['PRICES'][$price], "_WO_SALES")){
							$type = 'NO_SALE';
						}elseif($tmp['PRICES'][$price] == "OPTIMAL"){
							$type = 'OPTIMAL';
						}
						
						$tmp['PRICES'][$price.'_TYPE'] = $type;
					}
				}
				
				$tmp["stock_stores_map"] = array();
				for($i = 1; $i <= IntVal(UTools::getSetting($sid.'_stores_count')); $i++){
					if(UTools::getSetting($sid."_store_id_".$i) && UTools::getSetting($sid."_catalog_stocks_".$ibID."_".$i)){
						$tmp["stock_stores_map"][$i] = array(
							'store' => UTools::getSetting($sid."_store_id_".$i),
							'source' => UTools::getSetting($sid."_catalog_stocks_".$ibID."_".$i)
						);
						
						if(UTools::getSetting($sid."_catalog_stocks_".$ibID."_".$i) == 'CATALOG_STORE_SUM'){
							$tmp["stock_stores_map"][$i]['stores_sum'] = explode(',', UTools::getSetting($sid."_catalog_stocks_sum_".$ibID."_".$i));
						}
					}
				}
				
				$tmp['FILTER_CHECK'] = 0;
				if($tmp['FILTER_PROP'] == 'FILLED_IDS'){
					if($tmp["OZONID_PROP"]){
						$tmp['FILTER_BASE'] = array('IBLOCK_ID'=>$tmp['IBLOCK_ID'], '!'.$tmp["OZONID_PROP"] => false);
						$tmp['FILTER_CHECK'] = 1;
					}
				}elseif($tmp['FILTER_PROP'] && $tmp['FILTER_PROP_VALUE']){
					$tmp['FILTER_BASE'] = array('IBLOCK_ID'=>$tmp['IBLOCK_ID'], $tmp['FILTER_PROP']=>$tmp['FILTER_PROP_VALUE']);
					$tmp['FILTER_CHECK'] = 1;
				}
				
				$res = \CIBlock::GetList(Array('SORT'=>'ASC'), array('ID'=>$ibID, 'CHECK_PERMISSIONS' => 'N'));
				if($ar_res = $res->Fetch()){
					$tmp['IBLOCK_LID'] = $tmp['PRICES']['IBLOCK_LID'] = $ar_res['LID'];
				}
				
				$tmp['PRICES_PROP_INT'] = IntVal($tmp['PRICES_PROP']);
				$tmp['PRICES_USE_FULL'] = (strstr($tmp['PRICES_PROP'], '_WO_SALES')) ? 1 : 0;
				$tmp['PRICES_TYPE_PROP'] = (strstr($tmp['PRICES_PROP'], 'PROPERTY_')) ? 1 : 0;
				
				$result[$ibID] = $tmp;
			}
		}
		
		
		return $result;
	}
	
	static function getStoresSettings($sid){
		$sett = UTools::getStorage($sid.'_settings_cache', 'stores');
		if(!$sett){
			$sett = array(
				'stores_count' => IntVal(UTools::getSetting($sid.'_stores_count')),
				'stores_map' => array(),
			);
			
			for($i = 1; $i <= $sett['stores_count']; $i++){
				$sett['stores_map'][] = UTools::getSetting($sid."_store_id_".$i);
			}
			
			UTools::setStorage($sid.'_settings_cache', 'stores', $sett);
		}
		
		return $sett;
	}
	
	static function getCatalogSettings($sid){
		$sett = UTools::getStorage($sid.'_settings_cache', 'catalog');
		if(!$sett && Loader::includeModule("iblock")){
			$sett = array(
				'api' => self::getTokens($sid),
				'main' => array(
					// 'api_token' => self::getApiToken($sid),
					'stores_count' => IntVal(UTools::getSetting($sid.'_stores_count')),
					'stocks_deviation' => abs(IntVal(UTools::getSetting($sid.'_catalog_stocks_deviation'))),
					'price_correction' => UTools::getSetting($sid.'_catalog_price_correction'),
					'price_correction_value' => abs(IntVal(UTools::getSetting($sid.'_catalog_price_correction_value'))),
				)
			);
			
			if($sett["main"]["price_correction"] && $sett["main"]["price_correction_value"] && strstr($sett["main"]["price_correction"], 'percent')){
				$sett["main"]["price_correction_value"] = $sett["main"]["price_correction_value"] / 100;
			}
			
			$sett['IBLOCKS'] = self::_getCatalogParams($sid);
			
			
			UTools::setStorage($sid.'_settings_cache', 'catalog', $sett);
		}
		
		return $sett;
	}
	
	static function getOrderSettings($sid){
		$sett = UTools::getStorage($sid.'_settings_cache', 'order');
		if(!$sett && Loader::includeModule("iblock")){
			$sett = array(
				'api' => self::getTokens($sid),
			);
			
			$uid = IntVal(UTools::getSetting($sid.'_orders_user_id'));
			
			$sett["main"]["sid"] = $sid;
			$sett["main"]["cache_ib"] = UTools::getSetting('changes_ord_iblock_id');
			
			$sett['create'] = array(
				'user_id' => ($uid) ? $uid : \CSaleUser::GetAnonymousUserID(),
				'default_user_id' => $uid,
				'site_id' => UTools::getSetting($sid.'_orders_site_id'),
				'person' => UTools::getSetting($sid.'_orders_person_type'),
				'payment' => UTools::getSetting($sid.'_orders_paysystem'),
				'delivery' => UTools::getSetting($sid.'_orders_delivery'),
				
				'prop_ozonid' => UTools::getSetting($sid.'_prop_ozon_id'),
				'prop_send_data' => UTools::getSetting($sid.'_prop_send_data'),
				
				'skip_cancelled' => (UTools::getSetting($sid.'_orders_noload_canceled') == 'Y'),
				'product_no_find' => UTools::getSetting($sid.'_orders_nofind_products'),
				'product_no_stock' => UTools::getSetting($sid.'_orders_nostock_products'),
			);
			
			$sett['create']['delivery_map'] = array();
			
			for($i = 1; $i <= IntVal(UTools::getSetting($sid.'_stores_count')); $i++){
				if(UTools::getSetting($sid."_store_id_".$i) && UTools::getSetting($sid.'_orders_delivery_'.$i)){
					$sett['create']['delivery_map'][UTools::getSetting($sid."_store_id_".$i)] = UTools::getSetting($sid.'_orders_delivery_'.$i);
				}
			}
			
			$sett['update'] = [
				'use_returns' => (UTools::getSetting($sid.'_order_update_retuns') == 'Y')
			];
			
			$sett['status_map'] = array(
				'awaiting_packaging' => array(
					'index' => 0,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_awaiting_packaging"),
					'flag' => UTools::getSetting($sid."_order_autoflag_awaiting_packaging"),
				),
				'awaiting_deliver' => array(
					'index' => 1,
					'send' => 1,
					'status' => UTools::getSetting($sid."_order_autostat_awaiting_deliver"),
					'flag' => UTools::getSetting($sid."_order_autoflag_awaiting_deliver"),
				),
				'cancelled' => array(
					'index' => 1,
					'send' => 1,
					'status' => UTools::getSetting($sid."_order_autostat_cancelled"),
					'flag' => UTools::getSetting($sid."_order_autoflag_cancelled"),
				),
				'delivering' => array(
					'index' => 3,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_delivering"),
					'flag' => UTools::getSetting($sid."_order_autoflag_delivering"),
				),
				'delivered' => array(
					'index' => 3,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_delivered"),
					'flag' => UTools::getSetting($sid."_order_autoflag_delivered"),
				),
			);
			
			if($sett['update']['use_returns']){
				$sett['status_map']['returned_to_seller'] = [
					'index' => 4,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_returned_to_seller"),
					'flag' => UTools::getSetting($sid."_order_autoflag_returned_to_seller"),
				];
				
				$sett['status_map']['waiting_for_seller'] = [
					'index' => 4,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_waiting_for_seller"),
					'flag' => UTools::getSetting($sid."_order_autoflag_waiting_for_seller"),
				];
				
				$sett['status_map']['accepted_from_customer'] = [
					'index' => 4,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_accepted_from_customer"),
					'flag' => UTools::getSetting($sid."_order_autoflag_accepted_from_customer"),
				];
				
				$sett['status_map']['cancelled_with_compensation'] = [
					'index' => 4,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_cancelled_with_compensation"),
					'flag' => UTools::getSetting($sid."_order_autoflag_cancelled_with_compensation"),
				];
				
				$sett['status_map']['ready_for_shipment'] = [
					'index' => 4,
					'send' => 0,
					'status' => UTools::getSetting($sid."_order_autostat_ready_for_shipment"),
					'flag' => UTools::getSetting($sid."_order_autoflag_ready_for_shipment"),
				];
			}
			
			
			$sett["crm"]["responsible"] = IntVal(UTools::getSetting($sid."_orders_crm_responsible"));
			
			$sett["rfbs"]["fio"] = UTools::getSetting($sid."_prop_rfbs_fio");
			$sett["rfbs"]["phone"] = UTools::getSetting($sid."_prop_rfbs_phone");
			$sett["rfbs"]["address"] = UTools::getSetting($sid."_prop_rfbs_address");
			
			$sett['catalogs'] = self::_getCatalogParams($sid);
			
			UTools::setStorage($sid.'_settings_cache', 'order', $sett);
		}
		
		return $sett;
	}
		static function getDefaultUserData($userID){
			if(!$userID) return [];
			
			$result = UTools::getStorage('default_user_info', $userID);
			if(!$result){
				$result = [];
				
				$rsUsers = \CUser::GetList(($b="LOGIN"), ($o="desc"), ["ID" => $userID], ["FIELDS" => ["ID", "NAME", "EMAIL", "PERSONAL_PHONE"]]);
				if($arUserDefaults = $rsUsers->Fetch()){
					$result = $arUserDefaults;
				}
				
				UTools::setStorage('default_user_info', $userID, $result);
			}
			
			return $result;
		}
	
	
		static function getPropertyXmlidMap($ibid, $code){
			$result = array();
			$property_enums = \CIBlockPropertyEnum::GetList(Array("ID"=>"DESC"), Array("IBLOCK_ID"=>$ibid, "CODE"=>$code));
			while($item = $property_enums->GetNext()){
				$result[$item['ID']] = $item['EXTERNAL_ID'];
			}
			
			return $result;
		}
	
	static function getChagesSettings($sid){
		$sett = UTools::getStorage('settings_cache', 'catalog_changes');
		if(!$sett && Loader::includeModule("iblock")){
			$sett = array(
				'sid' => $sid,
				'ib_changes' => UTools::getSetting('changes_iblock_id'),
				'stores_count' => UTools::getSetting($sid.'_stores_count'),
			);
			
			UTools::setStorage('settings_cache', 'catalog_changes', $sett);
		}
		
		return $sett;
	}
	
	/* workers */
	static function showNotify($message, $tag, $type = ''){
		$arNotify = array('MESSAGE' => $message,  'TAG' => 'agoz_'.$tag, 'MODULE_ID' => self::MODULE_ID, 'ENABLE_CLOSE' => 'Y');
		if($type){
			$arNotify["NOTIFY_TYPE"] = $type;
		}
		
		\CAdminNotify::Add($arNotify);
	}
	
	static function toDbLog($sid, $type, $description, $item = 1){
		$rType = 'AGOZ_'.$sid.'_'.$type;
		
		if(strlen($item) > 1){
			global $DB;
			$queryEventLogs = \CEventLog::GetList(array("ID" => "ASC"), array("ITEM_ID" => $item, "AUDIT_TYPE_ID" => $rType), array("nPageSize" => 25));
			while ($eventLog = $queryEventLogs -> fetch()) {
				$delid = IntVal($eventLog["ID"]);
				if($delid){
					$q = "DELETE FROM b_event_log WHERE ID = '".$delid."';";
					$DB->Query($q);
				}
			}
		}
		
		\CEventLog::Add(array(
			"SEVERITY" => "DEBUG",
			"AUDIT_TYPE_ID" => $rType,
			"MODULE_ID" => self::MODULE_ID,
			"ITEM_ID" => $item,
			"DESCRIPTION" => $description,
		));
	}
	
	static function sendRequest($url, $arData = false, $token, $method = false, $params = array()){
		$result = array();
		
		if(self::onDebug($params['sid'])){
			AddMessage2Log($arData, self::MODULE_ID.' '.$url.' IN', 0);
		}
		
		// echo 'sendRequest url <pre>'; print_r($url); echo '</pre>';
		// echo 'sendRequest arData <pre>'; print_r($arData); echo '</pre>';
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Api-Key: '.$token['key'],
			'Client-Id: '.$token['client_id'],
		));
		
		if($method){
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		}
		
		if($arData){
			$jData = \Bitrix\Main\Web\Json::Encode($arData);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $jData);
		}
		
		$result["result"] = curl_exec($curl);
		$result["error"] = curl_error($curl);
		$result["error_no"] = curl_errno($curl);
		$result["header"] = curl_getinfo($curl);
		
		curl_close($curl);
		
		if(self::onDebug($params['sid'])){
			$debugInfo = array(
				'http_code' => $result["header"]['http_code'],
				'result' => $result['result']
			);
			
			if($debugInfo["http_code"] == 200 && $debugInfo["result"]){
				try {
					$debugInfo["result"] = \Bitrix\Main\Web\Json::Decode($debugInfo["result"]);
				} catch (\Exception $e) {}
			}
			
			AddMessage2Log($debugInfo, self::MODULE_ID.' '.$url.' OUT', 0);
		}
		
		return $result;
	}
}