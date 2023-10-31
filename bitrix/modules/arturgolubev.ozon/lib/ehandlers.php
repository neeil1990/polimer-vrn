<?
namespace Arturgolubev\Ozon;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Tools;
use \Arturgolubev\Ozon\Unitools as UTools;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.ozon/include.php");

class Ehandlers {
	const MODULE_ID = 'arturgolubev.ozon';
	
	
	static function onEpilog(){
		if(!Loader::IncludeModule(self::MODULE_ID)) return 0;
		
		$cur = UTools::GetCurPage();
		// echo '<pre>'; print_r($cur); echo '</pre>';
		// echo '<pre>'; print_r($_POST); echo '</pre>';
		
		if(defined("ADMIN_SECTION")){
			if($cur == '/bitrix/admin/sale_order.php'){
				\CJSCore::Init(array("ag_ozon_order_tab"));
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid() && is_array($_POST["ID"])){
				if($cur == "/bitrix/admin/sale_order.php"){
					?>
					<script>
						var arIds = <?=\CUtil::PhpToJSObject($_POST["ID"])?>;
						// console.log(arIds);
					</script>
					
					<?
					if($_POST["action"] == "agoz_print_stickers"){?>
						<script>
							top.agozOrderTab.getOrderStickers(arIds);
						</script>
					<?}
					
					if($_POST["action"] == "agoz_status_awaiting_deliver"){?>
						<script>
							top.agozOrderTab.setOrderAwaitingDeliver(arIds);
						</script>
					<?}
					
					if($_POST["action"] == "agoz_status_cancelled"){?>
						<script>
							top.agozOrderTab.setOrderCancelledConfirm(arIds);
						</script>
					<?}
				}
			}
		}elseif(Loader::IncludeModule("crm") && strstr($cur, '/shop/orders/details/')){
			\CJSCore::Init(array("ag_ozon_order_tab"));
			$orderId = IntVal(str_replace(array('/shop/orders/details/', '/'),'',$cur));
			if($orderId){
				$orderInfo = \CArturgolubevOzon::_getOrderOzonInfo($orderId);		
				echo '<pre>'; print_r($orderInfo); echo '</pre>';	//todo			
				if($orderInfo["oz_posting_number"] && !$orderInfo["bx_canceled"] && Tools::checkRights($orderInfo['bx_sid'], 'order')){
					global $APPLICATION;
					$APPLICATION->AddHeadString('
						<script>
							BX.agozOrderTab.initCrmScripts('.\CUtil::PhpToJSObject($orderInfo).');
						</script>
					');
				}
			}
		}
	}
	
	static function massActionMenu(&$list){
		if(!Loader::IncludeModule(self::MODULE_ID)) return 0;
		
		// echo '<pre>'; print_r($list); echo '</pre>';
		
		if($list->table_id == "tbl_sale_order"){
			if(Tools::checkRightsAny('order')){
				$tmp = $list->arActions;
				$list->arActions = array();
				$list->arActions["agoz_print_stickers"] = Loc::getMessage("ARTURGOLUBEV_OZON_ORDER_PRINT_STICKER_MASS");
				$list->arActions["agoz_status_awaiting_deliver"] = Loc::getMessage("ARTURGOLUBEV_OZON_MASS_STATUS_AWAITING_DELIVER");
				$list->arActions["agoz_status_cancelled"] = Loc::getMessage("ARTURGOLUBEV_OZON_MASS_STATUS_CANCELLED");
				
				foreach($tmp as $k=>$v){
					$list->arActions[$k] = $v;
				}
			}
		}
	}
	
	static function orderDetailMenu(&$items){
		if(!Loader::IncludeModule(self::MODULE_ID)) return 0;
		
		if(UTools::GetCurPage() == '/bitrix/admin/sale_order_view.php'){
			$orderID = IntVal($_GET["ID"]);
			if(!$orderID) return 0;
			
			$orderInfo = \CArturgolubevOzon::_getOrderOzonInfo($orderID);
			if($orderInfo['oz_posting_number'] && Tools::checkRights($orderInfo['bx_sid'], 'order')){
				// print
				if($orderInfo['oz_status'] == 'awaiting_deliver'){
					$arReports[] = array(
						"TEXT" => Loc::getMessage("ARTURGOLUBEV_OZON_GET_STICKER"),
						"LINK"=>"javascript:agozOrderTab.getOrderStickers([".$orderID."]);"
					);
				}
				
				// status
				if($orderInfo['oz_status'] == 'awaiting_packaging'){
					$arReports[] = array(
						"TEXT" => Loc::getMessage("ARTURGOLUBEV_OZON_SET_ORDER_STATUS_AWAITING_DELIVER"),
						"LINK"=>"javascript:agozOrderTab.setOrderAwaitingDeliver([".$orderID."]);"
					);
				}
				
				if(in_array($orderInfo['oz_status'], array('awaiting_deliver', 'awaiting_packaging'))){
					$arReports[] = array(
						"TEXT" => Loc::getMessage("ARTURGOLUBEV_OZON_SET_ORDER_STATUS_CANCELLED"),
						"LINK"=>"javascript:agozOrderTab.setOrderCancelledConfirm([".$orderID."]);"
					);
				}
				
				if(count($arReports)){
					$items[] = array(
						"TEXT" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDER_BUTTON_MAIN"),
						"TITLE" => Loc::getMessage("ARTURGOLUBEV_OZON_ORDER_BUTTON_MAIN"),  
						"ICON" => "btn_green",  
						"MENU" => $arReports                  
					);
				}
			}
		}
	}
}