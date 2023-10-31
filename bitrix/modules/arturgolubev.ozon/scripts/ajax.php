<?
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Encoding;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('PERFMON_STOP', true);

set_time_limit(300); 
@ignore_user_abort(true);
define("LANG", "ru"); 

$module_id = 'arturgolubev.ozon';
if(!Loader::includeModule($module_id)) die("It's no working");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");

$_REQUEST = Encoding::correctAjaxEncoding($_REQUEST);

$result = array(
	"sid" => htmlspecialcharsbx($_REQUEST["sid"]),
	"action" => htmlspecialcharsbx($_REQUEST["action"]),
	"error" => 0
);

/* small in settings */
if($result["action"] == 'get_warehouse_ids'){
	$result = CArturgolubevOzon::getOzWarehouses($result);
}
if($result["action"] == 'get_card_limit'){
	$result = CArturgolubevOzon::getOzCardLimit($result);
}

/* element work */
if($result["action"] == 'get_order_tab_html'){
	$result['order_id'] = IntVal($_REQUEST['oid']);
	
	if($result['order_id']){
		$orderInfo = CArturgolubevOzon::getOzonOrderInfo($result['order_id']);
		// echo '<pre>'; print_r($orderInfo); echo '</pre>';
		if($orderInfo['error_message']){
			$result['error_message'] = $orderInfo['error_message'];
		}else{
			ob_start();
				if($orderInfo['order']['status'] == 'awaiting_packaging'):
				?>
					<h3 class=""><?=Loc::getMessage("ARTURGOLUBEV_OZON_ORDERINFO_SHIP_DATA_TITLE")?></h3>
					<?
					$ind = 0;
					$order = \Bitrix\Sale\Order::load($result['order_id']);
					$collection = $order->getShipmentCollection()->getNotSystemItems();
					foreach ($collection as $shipment){
						$ind++;
						?>
							<?=Loc::getMessage("ARTURGOLUBEV_OZON_ORDERINFO_SHIP_BOX")?><?=$ind?>:<br/><br/>
							<table class="agoz_basetable">
								<tr>
									<td><?=Loc::getMessage("ARTURGOLUBEV_OZON_ORDERINFO_SHIP_PROD_NAME")?></td>
									<td><?=Loc::getMessage("ARTURGOLUBEV_OZON_ORDERINFO_SHIP_PROD_QUANTITY")?></td>
								</tr>
								<?$collectionBS = $shipment->getShipmentItemCollection();
								foreach ($collectionBS as $shipmentItem):?>
									<tr>
										<td><?=$shipmentItem->getBasketItem()->getField('NAME')?></td>
										<td><?=$shipmentItem->getField("QUANTITY")*1?></td>
									</tr>
								<?endforeach;?>
							</table>
						<?
					}
				endif;
				
				?>
					
					
					<h3 class=""><?=Loc::getMessage("ARTURGOLUBEV_OZON_ORDERINFO_DATA_TITLE")?></h3>
					<table>
						<tr>
							<td></td>
						</tr>
					</table>
				<?
				// echo '<pre>'; print_r($orderInfo['order']); echo '</pre>';
				$result['html'] = ob_get_contents();
			ob_end_clean();
		}
	}
}
if($result["action"] == 'get_price_table'){
	$result['ozonid'] = $_REQUEST['ozonid'];
	$result['ibid'] = IntVal($_REQUEST['ibid']);
	$result['eid'] = IntVal($_REQUEST['eid']);
	
	if($result['eid'] && $result['sid']){
		$result = CArturgolubevOzon::getOzPricetable($result);
		
		if(!$result['error_message']){
			$row = $result['element_prices']['items'][0];
			
			if(is_array($row)){
				foreach(array('price', 'old_price', 'recommended_price', 'retail_price', 'premium_price', 'vat', 'min_ozon_price', 'marketing_price', 'marketing_seller_price', 'min_price') as $kname){
					if(strlen($row['price'][$kname]))
						$row['price'][$kname] = $row['price'][$kname] * 1;
				}
			}
			
			$result['html'] .= '<table class="agoz_basetable">';
				$result['html'] .= '<tr>
					<td colspan="2">'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_COL_NAME").'</td><td>'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_COL_VALUE").'</td>
				</tr>';
				$result['html'] .= '<tr>
					<td colspan="2">'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_offer_id").'</td><td>'.$row['offer_id'].'</td>
				</tr>';
				$result['html'] .= '<tr>
					<td colspan="2">'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_price_index").'</td><td>'.$row['price_index'].'</td>
				</tr>';
				$result['html'] .= '<tr>
					<td colspan="2">'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_volume_weight").'</td><td>'.$row['volume_weight'].'</td>
				</tr>';
				
				$f = 1;
				foreach($row['price'] as $k=>$v){
					$result['html'] .= '<tr>';
						if($f){
							$result['html'] .= '<td rowspan="'.count($row['price']).'">'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_prices").'</td>';
							$f = 0;
						}
						$result['html'] .= '<td>'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_".$k).' ('.$k.')</td>';
						$result['html'] .= '<td>'.$v.'</td>';
					$result['html'] .= '</tr>';
				}
				
				$f = 1;
				foreach($row['commissions'] as $k=>$v){
					$result['html'] .= '<tr>';
						if($f){
							$result['html'] .= '<td rowspan="'.count($row['commissions']).'">'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_commissions").'</td>';
							$f = 0;
						}
						$result['html'] .= '<td>'.Loc::getMessage("ARTURGOLUBEV_OZON_GET_PRICES_TABLE_".$k).' ('.$k.')</td>';
						$result['html'] .= '<td>'.$v.'</td>';
					$result['html'] .= '</tr>';
				}
					
			$result['html'] .= '</table>';
		}
		
		// echo '<pre>'; print_r($row); echo '</pre>';
		// echo '<pre>'; print_r($result['html']); echo '</pre>';
		
	}
}

/* order work */
if($result["action"] == 'get_order_stickers'){
	$result['orders'] = $_REQUEST['orders'];
	
	$stickerResult = CArturgolubevOzon::getOzonStickers($result['orders']);
	if($stickerResult["error_message"]){
		$result['error_message'] = $stickerResult["error_message"];
	}else{
		$result['file_name'] = $stickerResult["file_name"];
	}
	
	// echo '<pre>'; print_r($stickerResult); echo '</pre>';
	// echo '<pre>'; print_r($result); echo '</pre>';
}

if($result["action"] == 'set_status_awaiting_deliver'){
	$result['orders'] = $_REQUEST['orders'];
	$fResult = CArturgolubevOzon::setOzonOrderStatusAD($result);
	$result['result_orders'] = $fResult['result_orders'];
	
	$result['result_html'] = '';
	foreach($result['result_orders'] as $orderID=>$rz){
		$result['result_html'] .= '<div>';
			$result['result_html'] .= Loc::getMessage("ARTURGOLUBEV_OZON_ORDER_SETSTATUS_RESULT_WINDOW_INFO", array('#orderid#' => (count($result['result_orders']) > 1) ? ' '.$orderID : ''));
			
			if($rz['error_message'])
				$result['result_html'] .= $rz['error_message'];
			else
				$result['result_html'] .= Loc::getMessage("ARTURGOLUBEV_OZON_MAIN_SUCCESS");
			
		$result['result_html'] .= '</div>';
	}
	
	// echo '<pre>'; print_r($statusResult); echo '</pre>';
	
}

if($result["action"] == 'set_status_cancelled'){
	$result['orders'] = $_REQUEST['orders'];
	$result['cancel_reason_id'] = $_REQUEST['cancel_reason_id'];
	$result['cancel_reason_message'] = $_REQUEST['cancel_reason_message'];
	$fResult = CArturgolubevOzon::setOzonOrderStatusCL($result);
	$result['result_orders'] = $fResult['result_orders'];
	
	$result['result_html'] = '';
	foreach($result['result_orders'] as $orderID=>$rz){
		$result['result_html'] .= '<div>';
			$result['result_html'] .= Loc::getMessage("ARTURGOLUBEV_OZON_ORDER_SETSTATUS_RESULT_WINDOW_INFO", array('#orderid#' => (count($result['result_orders']) > 1) ? ' '.$orderID : ''));
			
			if($rz['error_message'])
				$result['result_html'] .= $rz['error_message'];
			else
				$result['result_html'] .= Loc::getMessage("ARTURGOLUBEV_OZON_MAIN_SUCCESS");
			
		$result['result_html'] .= '</div>';
	}
}



/* act works */
if($result["action"] == 'get_act_request'){
	$result['storeid'] = $_REQUEST['storeid'];
	$rz = CArturgolubevOzon::getOzonActCreate($result['sid'], $result['storeid']);
	if($rz["error_message"]){
		$result["error_message"] = $rz["error_message"];
	}else{
		$result['process_id'] = $rz['process_id'];
	}
}

if($result["action"] == 'get_act_check'){
	$result['process_id'] = $_REQUEST['process_id'];
	$rz = CArturgolubevOzon::getOzonActCheck($result['sid'], $result['process_id']);
	if($rz["error_message"]){
		$result["error_message"] = $rz["error_message"];
	}else{
		$result['act_type'] = $rz['act_type'];
		$result['status'] = $rz['status'];
		$result['added_to_act'] = $rz['added_to_act'];
	}
}

if($result["action"] == 'get_act_save'){
	$result['process_id'] = $_REQUEST['process_id'];
	$rz = CArturgolubevOzon::getOzonActSave($result['sid'], $result['process_id']);
	if($rz["error_message"]){
		$result["error_message"] = $rz["error_message"];
	}else{
		$result['file_name'] = $rz['file_name'];
	}
}


/* base */
if($result["action"] == 'sleep'){
	$sleep_time = intval($_REQUEST['sec']);
	if($sleep_time){
		sleep($sleep_time);
	}
}

if($result['error_message']){
	$result['error'] = 1;
}

echo \Bitrix\Main\Web\Json::encode($result);