<?
namespace Arturgolubev\Ozon\Order;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use Arturgolubev\Ozon\Tools;
use Arturgolubev\Ozon\Unitools as UTools;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.ozon/orders_tab.php");

class Tab
{
	const MODULE_ID = 'arturgolubev.ozon';
	
	public static function orderTabControl(&$form){
		if(Loader::includeModule(self::MODULE_ID) && UTools::getCurPage() == '/bitrix/admin/sale_order_view.php'){
			$orderID = IntVal($_GET["ID"]);
			if(!$orderID) return 0;
			
			\CJSCore::Init(array("ag_ozon_order_tab"));
			
			$orderInfo = \CArturgolubevOzon::_getOrderOzonInfo($orderID);
			
			if($orderInfo['oz_posting_number'] && Tools::checkRights($orderInfo['bx_sid'], 'order')){
				// echo '<pre>'; print_r($orderInfo); echo '</pre>';
				$content = '<tr><td>';
					$content .= '<div id="ag_oz_order_tab_content_paste">
						
					</div>';
					$content .= '<script>BX.ready(function(){
						BX.agozOrderTab.init("'.$orderInfo['bx_sid'].'", '.$orderID.');
					});</script>';
				$content .= '</td></tr>';
				
				$form->tabs[] = array('DIV' => 'agoz_tab_order_info', 'TAB' => Loc::getMessage("ARTURGOLUBEV_OZON_TAB_ORDER_TAB_NAME"), 'TITLE' => Loc::getMessage("ARTURGOLUBEV_OZON_TAB_ORDER_TAB_TITLE", array('#posting_number#' => $orderInfo['oz_posting_number'], '#order_id#' => $orderID, '#sid#' => $orderInfo['bx_sid'])), 'CONTENT' => $content);
			}
		}
	}
}