<?
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Arturgolubev\Ozon\Unitools as UTools;
use \Arturgolubev\Ozon\Tools;
use \Arturgolubev\Ozon\Admin\SettingsHelper as SHelper;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

$module_id = 'arturgolubev.ozon';
Loader::IncludeModule($module_id);
CJSCore::Init(array("ag_ozon_options", "ag_ozon_order_tab"));

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

$APPLICATION->SetTitle(Loc::getMessage("ARTURGOLUBEV_OZON_ACTS_PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$sid = trim(htmlspecialcharsbx($_GET["sid"]));
$site_name = SHelper::checkSid($sid);

if(Tools::checkRights($sid, 'order') && $site_name):
	$sett = Tools::getStoresSettings($sid);
	// echo '<pre>'; print_r($sett); echo '</pre>';
	
	// $rz = CArturgolubevOzon::getOzonActCreate($sid, 23492939870000);
	// $rz = CArturgolubevOzon::getOzonActCheck($sid, '3480931');
	// $rz = CArturgolubevOzon::getOzonActGet($sid, '3480931');
	// echo '<pre>'; print_r($rz); echo '</pre>';
?>
	<div class="agwb_adm_page">
		<?=Loc::getMessage("ARTURGOLUBEV_OZON_ACTS_NOTIFICATION")?>
		
		<div class="">
			<?if(Loader::includeModule("sale")):
				$orderCounter = 0;
				$orderShipmentMap = array();
			
				$typeSelect = array(
					"ID", 
					"AOZ_POSTING_NUMBER.VALUE",
					"AOZ_STATUS.VALUE",
					"AOZ_SHIPMENT_DATA.VALUE"
				);
				
				$typeRuntume = array(
					new \Bitrix\Main\Entity\ReferenceField(
						'AOZ_POSTING_NUMBER',
						'\Bitrix\sale\Internals\OrderPropsValueTable',
						array("=this.ID" => "ref.ORDER_ID"),
						array("join_type"=>"left")
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'AOZ_STATUS',
						'\Bitrix\sale\Internals\OrderPropsValueTable',
						array("=this.ID" => "ref.ORDER_ID"),
						array("join_type"=>"left")
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'AOZ_SHIPMENT_DATA',
						'\Bitrix\sale\Internals\OrderPropsValueTable',
						array("=this.ID" => "ref.ORDER_ID"),
						array("join_type"=>"left")
					),
				);
			
				$dbRes = \Bitrix\Sale\Order::getList(array(
					'select' => $typeSelect,
					'filter' => array(
						'=AOZ_POSTING_NUMBER.CODE' => 'AOZ_POSTING_NUMBER',
						'=AOZ_STATUS.CODE' => 'AOZ_STATUS',
						'=AOZ_SHIPMENT_DATA.CODE' => 'AOZ_SHIPMENT_DATA',
						'!AOZ_POSTING_NUMBER.VALUE' => false,
						'=AOZ_STATUS.VALUE' => 'awaiting_packaging'
					),
					'runtime' => $typeRuntume
				));
				
				while($order = $dbRes->fetch()){
					$orderCounter++;
					$orderShipmentMap[$order["SALE_INTERNALS_ORDER_AOZ_SHIPMENT_DATA_VALUE"]]++;
					
					// echo '<pre>'; print_r($order); echo '</pre>';
					
				}
				
				$agent_date = UTools::getSetting($sid.'_fbs_orders_get_time');
				if($agent_date){
					$agent_date = date('d.m.Y H:i:s', $agent_date);
				}else{
					$agent_date = '';
				}
			?>
			
				<?=Loc::getMessage("ARTURGOLUBEV_OZON_ACTS_INFORMATION", array('#oc1#'=>$orderCounter, '#agent_date#' => $agent_date))?>
				<?if(count($orderShipmentMap)):?>
					<?foreach($orderShipmentMap as $date=>$count):?>
						<div class=""><?=Loc::getMessage("ARTURGOLUBEV_OZON_ACTS_SHIPMENT_LINE_NAME", array("#date#" => $date, '#count#' => $count))?></div>
					<?endforeach;?>
				<?endif;?>
			<?endif;?>
		</div><br/><br/>
		
		<div class="hand_worker_buttons">
			<span <?if(count($sett['stores_map']) == 1) echo 'style="display: none;"'?>>
				<?=Loc::getMessage("ARTURGOLUBEV_OZON_ACTS_SELECT_STORE")?>
				<select id="select_get_act_request">
					<?foreach($sett['stores_map'] as $storeId):?>
						<option value="<?=$storeId?>"><?=$storeId?></option>
					<?endforeach;?>
				</select>
			</span>
			<span class="worker_button adm-btn" id="button_get_act_request"><?=Loc::getMessage("ARTURGOLUBEV_OZON_ACTS_BUTTON_ACT_CREATE")?></span>
		</div>
		<br/><br/>
		
		<div class="agoz_debug_area_wrap">
			<div class="agoz_debug_area">
			
			</div>
		</div>
	</div>
	
	<script>
		agOzWindows.initTools();
		agozOrderTab.initActs('<?=$sid?>');
	</script>
<?else:?>
	<?=Loc::getMessage('ARTURGOLUBEV_OZON_RIGHTS_ERROR')?>
<?endif;?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');?>