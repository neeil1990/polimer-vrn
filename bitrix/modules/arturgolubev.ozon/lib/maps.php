<?
namespace Arturgolubev\Ozon;

use \Bitrix\Main\Localization\Loc;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.ozon/include.php");

class Maps {
	static function getAgentMap($sid){
		return array(
			"stocks_full" => array(
				"NAME" => "CArturgolubevOzon::agentStocksFull('".$sid."');",
				"TIME" => "86400",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STOCK_FULL_NAME"),
			),
			"stocks_changes" => array(
				"NAME" => "CArturgolubevOzon::agentStocksChanges('".$sid."');",
				"TIME" => "300",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STOCK_CHANGES_NAME"),
			),
			"stocks_empty" => array(
				"NAME" => "CArturgolubevOzon::agentStocksEmpty('".$sid."');",
				"TIME" => "8400",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_STOCK_EMPTY_NAME"),
			),
			"prices_full" => array(
				"NAME" => "CArturgolubevOzon::agentPricesFull('".$sid."');",
				"TIME" => "86400",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_PRICES_FULL_NAME"),
			),
			"prices_changes" => array(
				"NAME" => "CArturgolubevOzon::agentPricesChanges('".$sid."');",
				"TIME" => "1200",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_PRICES_CHANGES_NAME"),
			),
			"orders_get" => array(
				"NAME" => "CArturgolubevOzon::agentOrdersGet('".$sid."');",
				"TIME" => "300",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_FBS_ORDERS_NAME"),
			),
			"orders_update" => array(
				"NAME" => "CArturgolubevOzon::agentOrdersUpdate('".$sid."');",
				"TIME" => "1200",
				"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_AGENT_FBS_ORDERS_UPDATE_NAME"),
			),
		);
	}
	
	static function getOrderSystemProps(){
		return array(
			"AWB_SID", "AWB_ORDERID", "AWB_WBWHID", "AWB_STOREID", "AWB_DATECREATED", "AWB_USERID", "AWB_FIO", "AWB_PHONE", "AWB_CHRTID", "AWB_BARCODE", "AWB_RID", "AWB_PID", "AWB_UID", "AWB_STATUS", "AWB_USERSTATUS", "AWB_OFFICE_ADDRESS", "AWB_DELIVERY_ADDRESS", "AWB_DELIVERY_TYPE", "AWB_STICKER_ENCODE_VALUE", "AWB_STICKER_ID_PART_A", "AWB_STICKER_ID_PART_B",
			"AOZ_POSTING_NUMBER", "AOZ_STATUS", "AOZ_SHIPMENT_DATA", "AOZ_WAREHOUSE_ID", "AOZ_SID",
		);
	}
	
	static function getCatalogSystemPropMap($type = 'skip'){
		if($type == 'skip'){
			$tmp = array('WB_IMTID', 'WB_NMID', 'WB_CHRTID', 'WB_DISCOUNT', 'WB_PROMOCODE', 'WB_SPECIFICATIONS');
		}elseif($type == 'full'){
			$tmp = array('WB_IMTID', 'WB_NMID', 'WB_CHRTID', 'WB_DISCOUNT', 'WB_PROMOCODE', 'WB_SPECIFICATIONS', 'WB_EXPORT', 'WB_EAN');
		}
		
		$result = $tmp;
		
		$rsSites = \CSite::GetList($by="sort", $order="asc", Array());
		while($arRes = $rsSites->Fetch()){
			foreach($tmp as $code){
				$result[] = $code.'_'.strtoupper($arRes["ID"]);
			}
		}
		
		return $result;
	}
	
	static function getLogTypes($sid){
		return array(
			array(
				"NAME" => Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_CHANGES"),
				"TYPE" => "AGOZ_".$sid."_PRODUCTS_",
			),
			array(
				"NAME" => Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_STOCKS"),
				"TYPE" => "AGOZ_".$sid."_STOCKS_",
			),
			array(
				"NAME" => Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_PRICES"),
				"TYPE" => "AGOZ_".$sid."_PRICES_",
			),
			array(
				"NAME" => Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_ORDERS"),
				"TYPE" => "AGOZ_".$sid."_ORDERS_",
			),
			array(
				"NAME" => Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_ACTUALIZE"),
				"TYPE" => "AGOZ_".$sid."_ACTUALIZE_",
			),
			// array(
				// "NAME" => Loc::getMessage("ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_CARD"),
				// "TYPE" => "AGOZ_".$sid."_CARD_",
			// ),
		);
	}
	
	static function getEventMap(){
		return array(
			array(
				'event' => 'onAfterCalculateStocks',
				'name' => Loc::getMessage("ARTURGOLUBEV_OZON_EVENT_CALCULATE_STOCKS_NAME"),
			),
			array(
				'event' => 'onAfterCalculatePrices',
				'name' => Loc::getMessage("ARTURGOLUBEV_OZON_EVENT_CALCULATE_PRICES_NAME"),
			),
			array(
				'event' => 'onAfterGetPricetable',
				'name' => Loc::getMessage("ARTURGOLUBEV_OZON_EVENT_CALCULATE_GET_PRICES_NAME"),
			),
		);
	}
}