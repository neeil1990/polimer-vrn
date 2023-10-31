<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

use Twinpx\Yadelivery\TwinpxOfferTable;
use Twinpx\Yadelivery\TwinpxConfigTable;

use Bitrix\Main;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Grid\Options as GridOptions;

use \Bitrix\Sale\Delivery\Services\Table;

global $APPLICATION, $USER;

IncludeModuleLangFile(__DIR__."../../options.php");

$request = Main\Application::getInstance()->getContext()->getRequest();
CModule::IncludeModule("currency");

$module_id = 'twinpx.yadelivery';
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($MODULE_RIGHT < "R") {
	$optionsNotSetMessage = new CAdminMessage([
        'MESSAGE' => GetMessage("TWINPX_ACCESS_DENIED"), 
        'TYPE' => 'ERROR', 
        'DETAILS' => GetMessage("TWINPX_ACCESS_DENIED"), 
        'HTML' => true
    ]);
    echo $optionsNotSetMessage->Show();
    return;
}

CUtil::InitJSCore( array('jquery2', 'ajax' ,'popup', 'twinpx_admin_lib') );
//$scheme = $request->isHttps() ? 'https' : 'http';
$ya_key = htmlspecialcharsbx(\Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''));
if($ya_key == '') $APPLICATION->AddHeadString('<script>window.twinpxYadeliveryYmapsAPI = false;</script>');
$APPLICATION->SetTitle(GetMessage("TWINPX_ACTIVE_DELIVERY"));
$APPLICATION->AddHeadString('<script src="//api-maps.yandex.ru/2.1.50/?apikey='.$ya_key.'&load=package.full&lang=ru-RU"></script>', true, false, 'BODY_END'); //подключаем карту

IncludeModuleLangFile(__FILE__);

//вывод блока с кнопками
$updateState = TwinpxConfigTable::getByCode('AgentUpdate');
$buttons = array(
    array(
        'CAPTION'=> GetMessage("TWINPX_CREATE_DELIVERY"),
        'TYPE'   => 'custom',
        'LAYOUT' => '<button class="ui-btn ui-btn-primary" name="newdelivery" onclick="this.blur();BX.adminShowMenu(this, [{\'TEXT\':\''.GetMessage('TWINPX_DELIVERY_CURIER').'\',\'ONCLICK\':\'newDelivery()\'},{\'TEXT\':\''.GetMessage('TWINPX_DELIVERY_PICKUP').'\',\'ONCLICK\':\'newDeliveryPvz()\'}]); return false;">'.GetMessage('TWINPX_CREATE_DELIVERY').'</button>'
    ),
    array(
        'CAPTION'=> GetMessage("TWINPX_SETTINGS"),
        'TYPE'   => 'custom',
        'LAYOUT' => '<button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting" name="setting" onclick="window.location=\'/bitrix/admin/settings.php?lang=ru&mid=twinpx.yadelivery\'"></button>'
    ),
    array(
        'CAPTION'=> GetMessage("TWINPX_OBNOVITQ_STATUSY"),
        'TYPE'   => 'button',
        'ONCLICK'=> 'updateAll()'
    ),
    array(
        'CAPTION'=> "",
        'TYPE'   => 'custom',
        'LAYOUT' => "<span style='margin-left: var(--ui-btn-margin-left)'>".GetMessage('TWINPX_OBNOVLENO')." ". FormatDate('x', $updateState, time()) ."</span>"
    )
);
if (!empty($buttons) && $MODULE_RIGHT > "R") {
    $APPLICATION->IncludeComponent('bitrix:ui.button.panel', '.default', [
        'ALIGN'   => 'left',
        'BUTTONS' => $buttons,
    ]);
}

//
$list_id   = 'offers_list';
$grid_options = new Bitrix\Main\Grid\Options($list_id);
$nav_params   = $grid_options->GetNavParams();
$sort         = $grid_options->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);

$nav          = new PageNavigation($list_id);
$nav->allowAllRecords(true)->setPageSize($nav_params['nPageSize'])->initFromUri();

$arFilter = array();
$filterOption = new Bitrix\Main\UI\Filter\Options($list_id);
$filterData = $filterOption->getFilter([]);
$filterable = array("REQUEST_ID", "STATUS_DESCRIPTION", "ADDRESS", "DELIVERY_INTERVAL");//поиск в полях

$arFilter = TwinpxOfferTable::prepareFilter($filterData, $filterable);
$arFilter += ['DIVIDE' => 1]; //только активные

$ui_filter = array(
    array('id' => 'ID', 'name' => 'ID', 'type'=>'number'),
    array('id' => 'ORDER_ID', 'name' => GetMessage("TWINPX_NOMER_ZAKAZA"), 'type'=>'number', 'default' => true),
    array('id' => 'ORDER_DATE', 'name' => GetMessage("TWINPX_DATA_ZAAVKI"), 'type'=>'date', 'default' => true),
    array('id' => 'PICKUPDATE', 'name' => GetMessage("TWINPX_PICKUPDATE"), 'type'=>'date', 'default' => true),
    //array('id' => 'REQUEST_ID', 'name' => GetMessage("TWINPX_REQUEST_ID"), 'type'=>'text'),
    array('id' => 'ADDRESS', 'name' => GetMessage("TWINPX_ADRES_DOSTAVKI"), 'type'=>'text'),
    array('id' => 'BARCODE', 'name' => GetMessage("TWINPX_BARCODE"), 'type'=>'text'),
    array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage("TWINPX_STATUS"), 'type'=>'text'),
    array('id' => 'PVZ_ID', 'name' => GetMessage("TWINPX_DOSTAVKA"), 'type'=>'list', 'items' => ['' => GetMessage("TWINPX_ALL"), 'P' => GetMessage("TWINPX_PVZ"), 'C' => GetMessage("TWINPX_CURIER")]),
    array('id' => 'PAYMENT', 'name' => GetMessage("TWINPX_PAYMENT"), 'type'=>'list', 'items' => ['already_paid' => GetMessage("TWINPX_ALREADY_PAID"), 'cash_on_receipt' => GetMessage("TWINPX_CASH_ON_RECEIPT"),  'card_on_receipt' => GetMessage("TWINPX_CARD_ON_RECEIPT")], 'params' => ['multiple' => 'Y']),
    array('id' => 'PRICE', 'name' => GetMessage("TWINPX_PRICE"), 'type'=>'number', 'default' => true),
    array('id' => 'CANCEL', 'name' => GetMessage("TWINPX_CANCELED"), 'type'=>'list', 'items' => ['' => GetMessage("TWINPX_ALL"), '0' => GetMessage("TWINPX_NO"), '1' => GetMessage("TWINPX_YES")]),
);

//получаем данные из БД
$res = TwinpxOfferTable::getList([
    'filter'     => $arFilter,
    'select'     => array("*"),
    'offset'    => $nav->getOffset(),
    'limit'     => $nav->getLimit(),
    'order'     => $sort['sort'],
    'count_total' => true
]);
$nav->setRecordCount($res->getCount()); //задаем количество записи
foreach ($res->fetchAll() as $row) 
{
    $newOffer = ($row['REQUEST_ID'] == '' || $row['CANCEL'] == '1') ? 'newOffer('.$row['ID'].')' : 'if(confirm("'.GetMessage("TWINPX_DOSTAVKA_OFORMLENO").'")){}' ;
	$type = isset($row['PVZ_ID']) ? GetMessage("TWINPX_PVZ") : GetMessage("TWINPX_CURIER");
	$payment = array('already_paid' => GetMessage("TWINPX_ALREADY_PAID"), 'cash_on_receipt' => GetMessage("TWINPX_CASH_ON_RECEIPT"), 'card_on_receipt' => GetMessage("TWINPX_CARD_ON_RECEIPT"));
	$arPickup = $row['PICKUPDATE'];
	$actions = array();
	if($MODULE_RIGHT > "R") {
		$actions = array(
	        array(
	            'text'    => GetMessage("TWINPX_REORDER"),
	            'default' => false,
	            'onclick' => $newOffer,
	        ),
	        array(
	            'text'    => GetMessage("TWINPX_RELOAD_STATE"),
	            'default' => false,
	            'onclick' => 'updateOffer('.$row['ID'].')',
	        ),
	        array(
	            'text'    => GetMessage("TWINPX_CANCEL"),
	            'default' => false,
	            'onclick' => 'if(confirm("'.GetMessage("TWINPX_REMOVE").'")){cancelOffer('.$row['ID'].')}'
	        ),
	        array(
	            'text'    => GetMessage("TWINPX_BARKOD"),
	            'default' => false,
	            'onclick' => 'printBarcode('.$row['ID'].')'
	        ),
	        array(
                'text'    => GetMessage("TWINPX_ACT"),
                'default' => false,
                'onclick' => 'printDocument('.$row['ID'].')',
            ),
	        array(
	            'delimiter' => true,
	        ),
	        array(
	            'text'    => GetMessage("TWINPX_ARCHIVE"),
	            'default' => false,
	            'onclick' => 'if(confirm("'.GetMessage("TWINPX_HOTITE_PERENESTQ").'")){archiveOffer('.$row['ID'].')}'
	        )
	    );
	}
      
	
	$state  = GetMessage($row['STATUS']); //код статуса
	$textState = ($state <> '') ? $state : $row['STATUS_DESCRIPTION']; //если есть перевод
	
    $list[] = array(
        'data' => array(
            "ID" 					=> $row['ID'],
            "ORDER_ID" 				=> '<a href="/bitrix/admin/sale_order_view.php?lang=ru&ID='.$row['ORDER_ID'].'">'.$row['ORDER_ID'].'</a>',
            //"REQUEST_ID" 			=> $row['REQUEST_ID'],
            "PVZ_ID" 				=> $type,
            "ORDER_DATE" 			=> $row['ORDER_DATE'],
            "ADDRESS" 				=> $row['ADDRESS'],
            "PAYMENT"				=> $payment[$row['PAYMENT']],
            "PICKUPDATE" 			=> $arPickup,
            "PRICE" 				=> CCurrencyLang::CurrencyFormat($row['PRICE'], "RUB"),
            "PRICE_FIX" 			=> CCurrencyLang::CurrencyFormat($row['PRICE_FIX'], "RUB"),
            "PRICE_DELIVERY" 		=> CCurrencyLang::CurrencyFormat($row['PRICE_DELIVERY'], "RUB"),
            "CANCEL" 				=> ($row['CANCEL'] == 1) ? GetMessage("TWINPX_YES") : GetMessage("TWINPX_NO"),
            "DELIVERY_INTERVAL" 	=> $row['DELIVERY_INTERVAL'],
            "BARCODE" 				=> $row['BARCODE'],
            "PICKUP" 				=> $row['PICKUP'],
            "STATUS_DESCRIPTION"	=> $textState . '<br><small>['. $row['STATUS'] . ']</small>',
        ),
        'actions' => $actions
    );
}
?>
<div>
    <?$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', 
    	array(
	        'FILTER_ID' 		=> $list_id,
	        'GRID_ID' 			=> $list_id,
	        'FILTER' 			=> $ui_filter,
	        'ENABLE_LIVE_SEARCH'=> false,
	        'ENABLE_LABEL' 		=> true
    	));
    ?>
</div>
<div style="clear: both;"></div>
<?
$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '.default', array(
    'GRID_ID'                  	=> $list_id,
    'ROWS'                     	=> $list,
    'COLUMNS'                  	=> array(
        array('id' => 'ID', 'name' => GetMessage("TWINPX_ID"), 'sort' => 'ID', 'default' => true),
        array('id' => 'ORDER_ID', 'name' => GetMessage("TWINPX_NOMER_ZAKAZA"), 'sort' => 'ORDER_ID', 'default' => true),
        array('id' => 'PVZ_ID', 'name' => GetMessage("TWINPX_DOSTAVKA"), 'sort' => 'PVZ_ID', 'default' => true),
        //array('id' => 'REQUEST_ID', 'name' => GetMessage("TWINPX_REQUEST_ID"), 'sort' => 'REQUEST_ID', 'default' => true),
        array('id' => 'ORDER_DATE', 'name' => GetMessage("TWINPX_DATA_ZAAVKI"), 'sort' => 'ORDER_DATE', 'type' => 'custom', 'default' => true),
        array('id' => 'ADDRESS', 'name' => GetMessage("TWINPX_ADRES_DOSTAVKI"), 'sort' => 'ADDRESS',  'default' => true),
        array('id' => 'PAYMENT', 'name' => GetMessage("TWINPX_PAYMENT"), 'sort' => 'PAYMENT', 'default' => true),
        array('id' => 'PICKUPDATE', 'name' => GetMessage("TWINPX_PICKUPDATE"), 'sort' => 'PICKUPDATE', 'default' => true),
        array('id' => 'PRICE', 'name' => GetMessage("TWINPX_PRICE"), 'sort' => 'PRICE', 'default' => true),
        array('id' => 'PRICE_FIX', 'name' => GetMessage("TWINPX_PRICE_FIX"), 'sort' => 'PRICE_FIX', 'default' => true),
        array('id' => 'PRICE_DELIVERY', 'name' => GetMessage("TWINPX_PRICE_DELIVERY"), 'sort' => 'PRICE_DELIVERY', 'default' => true),
        array('id' => 'CANCEL', 'name' => GetMessage("TWINPX_CANCELED"), 'sort' => 'CANCEL', 'default' => true),
        array('id' => 'DELIVERY_INTERVAL', 'name' => GetMessage("TWINPX_INTERVAL_DOSTAVKI"), 'sort' => 'DELIVERY_INTERVAL', 'default' => true),
        array('id' => 'BARCODE', 'name' => GetMessage("TWINPX_BARCODE"), 'sort' => 'BARCODE', 'default' => true),
        array('id' => 'PICKUP', 'name' => GetMessage("TWINPX_INTERVAL_PICKUP"), 'sort' => 'PICKUPDATE', 'default' => true),
        array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage("TWINPX_STATUS"), 'sort' => 'STATUS_DESCRIPTION', 'default' => true),
    ),
    'PAGE_SIZES'               	=>  array(
        array('NAME' => '20', 'VALUE' => '20'),
        array('NAME' => '50', 'VALUE' => '50'),
        array('NAME' => '100', 'VALUE' => '100'),
        array('NAME' => '200', 'VALUE' => '200')
    ),
    'AJAX_MODE'                	=> 'Y',
    'AJAX_ID'                  	=> \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'AJAX_OPTION_JUMP'         	=> 'N',
    'AJAX_OPTION_HISTORY'      	=> 'N',
    'SHOW_CHECK_ALL_CHECKBOXES'	=> false,
    'SHOW_ROW_ACTIONS_MENU'    	=> true,
    'SHOW_GRID_SETTINGS_MENU'  	=> true,
    'SHOW_NAVIGATION_PANEL'    	=> true,
    'SHOW_PAGINATION'         	=> true,
    'SHOW_SELECTED_COUNTER'    	=> true,
    'SHOW_TOTAL_COUNTER'       	=> true,
    'SHOW_PAGESIZE'            	=> true,
    'SHOW_ACTION_PANEL'        	=> true,
    'ALLOW_COLUMNS_SORT'       	=> true,
    'ALLOW_COLUMNS_RESIZE'     	=> true,
    'ALLOW_HORIZONTAL_SCROLL'  	=> true,
    'ALLOW_SORT'               	=> true,
    'ALLOW_PIN_HEADER'         	=> true,
    'ENABLE_COLLAPSIBLE_ROWS'	=> true,
    'ENABLE_FIELDS_SEARCH'		=> true,
    'NAV_OBJECT'               	=> $nav,
    'TOTAL_ROWS_COUNT'         	=> $nav->getRecordCount(),
    'SHOW_ROW_CHECKBOXES'		=> false, //групповые действие, отключены, влияет на ACTION_PANEL
));

?>
<script>
    window.twinpxYadeliveryFetchURL = '/bitrix/tools/<?=$module_id?>/admin/ajax.php';
</script>
<style>
	.yd-popup-offers__item{opacity:1}
	.yd-popup-offers__btn{width:114px;-ms-flex-negative:0;flex-shrink:0;border-radius:7px;line-height:40px;vertical-align:middle;padding:0 17px;color:#fff;text-decoration:none;font-family:Arial,sans-serif;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;-webkit-box-sizing:border-box;box-sizing:border-box;margin:0 0 0 20px;outline:none;height:40px;background-color:#FC3F1D;cursor:pointer;text-align:center;text-decoration:none!important}
</style>
<?
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
