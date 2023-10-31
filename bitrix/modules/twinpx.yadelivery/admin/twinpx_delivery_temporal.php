<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

use Twinpx\Yadelivery\TwinpxOfferTempTable;

use Bitrix\Main\Context, 
Bitrix\Main\Request,
Bitrix\Main\UI\PageNavigation,
Bitrix\Iblock\PropertyEnumerationTable,
Bitrix\Main\Grid\Options as GridOptions;

global $APPLICATION, $USER;

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

CUtil::InitJSCore( array('jquery2', 'ajax', 'twinpx_admin_lib'));

$APPLICATION->SetTitle(GetMessage("TWINPX_TEMPORAL_TITLE"));
$APPLICATION->SetAdditionalCSS('/bitrix/css/main/grid/webform-button.css');

$request = Context::getCurrent()->getRequest();
$scheme = $request->isHttps() ? 'https' : 'http';
$ya_key = htmlspecialcharsbx(\Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''));
if($ya_key == '') $APPLICATION->AddHeadString('<script>window.twinpxYadeliveryYmapsAPI = false;</script>');
$APPLICATION->AddHeadString('<script src="'.$scheme.'://api-maps.yandex.ru/2.1.50/?apikey='.$ya_key.'&load=package.full&lang=ru-RU"></script>', true, false, 'BODY_END'); //подключаем карту


IncludeModuleLangFile(__FILE__);

$isRequiredOptionsSet = false;
if (!$isRequiredOptionsSet)
{
    $optionsNotSetMessage = new CAdminMessage([
        'MESSAGE' => GetMessage("TWINPX_SOOBSENIE"), 
        'TYPE' => 'ERROR', 
        'DETAILS' => GetMessage("TWINPX_PODROBNOSTQ_PROBLEMY"), 
        'HTML' => true
    ]);
    //echo $optionsNotSetMessage->Show();
}	

//actions

if($request["op"] == 'delete' AND $request["id"] > 0) {
    //удаляем запись из таблицу и делаем редирект
    TwinpxOfferTempTable::delete($request["id"]);
}


//вывод таблицы
$list_id   = 'offers_list_temporal';
$grid_options = new Bitrix\Main\Grid\Options($list_id);
$sort         = $grid_options->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
$nav_params   = $grid_options->GetNavParams();
$nav          = new PageNavigation($list_id);
$nav->allowAllRecords(true)->setPageSize($nav_params['nPageSize'])->initFromUri();

$arFilter = array();
//получаем данные из БД
$res = TwinpxOfferTempTable::getList([
    'filter'     => $arFilter,
    'select'     => ["*"],
    'offset'    => $nav->getOffset(),
    'limit'     => $nav->getLimit(),
    'order'     => $sort['sort'],
    'count_total' => true
]);
$nav->setRecordCount($res->getCount()); //задаем количество записи
foreach ($res->fetchAll() as $row) {
    $actions = array();
	if($MODULE_RIGHT > "R") {
		$orderAction = '';
		if(strlen($row['PVZ_ID'])>1){
			$orderAction = "newDeliveryPvz({$row['ORDER_ID']}, '{$row['PVZ_ID']}', '{$row['LOCATION']}')";
		}
		else{
			$orderAction = "newDelivery({$row['ORDER_ID']})";
		}
		
		$actions = array(
            array(
                'text'    => GetMessage("TWINPX_CREATE"),
                'default' => false,
                'class'   => 'icon add',
                'onclick' => $orderAction
            ),
            array(
                'text'    => GetMessage("TWINPX_DELETE"),
                'default' => false,
                'class'   => 'icon remove',
                'onclick' => 'if(confirm("'.GetMessage("TWINPX_CONFIRM").'")){document.location.href="?lang='.SITE_ID.'&op=delete&id='.$row['ID'].'"}'
            )
        );
	}
    $list[] = array(
        'data' => array(
            "ID" => $row['ID'],
            "ORDER_ID" => '<a href="/bitrix/admin/sale_order_view.php?lang=ru&ID='.$row['ORDER_ID'].'">'.$row['ORDER_ID'].'</a>',
            "ORDER_DATE" => $row['ORDER_DATE'],
            "PVZ_ID" => ($row['PVZ_ID']) ? GetMessage("TWINPX_PAY_PICKUP") : GetMessage("TWINPX_PAY_CURIER"),
            "PAYCONFIRM" => ($row['PAYCONFIRM']) ? GetMessage("TWINPX_PAY_YES") : GetMessage("TWINPX_PAY_NO"),
//            "PAYMENT" => $row['PAYMENT'],
//            "DELIVERY_INTERVAL" => $row['DELIVERY_INTERVAL'],
            "LOCATION" => $row['LOCATION'],
            "DELIVERYDATE" => $row['DELIVERYDATE'],
        ),
        'actions' => $actions
    );
}

/*$ui_filter = array(
    array('id' => 'ID', 'name' => 'ID', 'type'=>'number', 'default' => true),
    array('id' => 'ORDER_ID', 'name' => GetMessage("TWINPX_NOMER_ZAKAZA"), 'type'=>'number', 'default' => true),
    array('id' => 'DIVIDE', 'name' => GetMessage("TWINPX_RAZDEL"), 'type'=>'list', 'items' => array('' => GetMessage("TWINPXY_LUBOY"), '1' => '1', '2' => '2'), 'params' => array('multiple' => 'N'))
);*/
?>
<?/*
<div>
    $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
        'FILTER_ID' => $list_id,
        'GRID_ID' => $list_id,
        'FILTER' => $ui_filter,
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true
    ]);
</div>

<div style="clear: both;"></div>
*/?>
<?
$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '.default', array(
    'GRID_ID'                  	=> $list_id,
    'ROWS'                     	=> $list,
    'COLUMNS'                  	=> array(
        array('id' => 'ID', 'name' => GetMessage("TWINPX_ID"), 'sort' => 'ID', 'default' => true),
        array('id' => 'ORDER_ID', 'name' => GetMessage("TWINPX_ORDER_ID"), 'sort' => 'ORDER_ID', 'default' => true),
        array('id' => 'ORDER_DATE', 'name' => GetMessage("TWINPX_ORDER_DATE"), 'sort' => 'ORDER_DATE', 'default' => true),
        array('id' => 'PVZ_ID', 'name' => GetMessage("TWINPX_PVZ_ID"), 'sort' => 'PVZ_ID', 'default' => true),
        array('id' => 'PAYCONFIRM', 'name' => GetMessage("TWINPX_PAYCONFIRM"), 'sort' => 'PAYCONFIRM', 'default' => true),
//        array('id' => 'PAYMENT', 'name' => GetMessage("TWINPX_PAYMENT"), 'sort' => 'PAYMENT', 'default' => true),
//        array('id' => 'DELIVERY_INTERVAL', 'name' => GetMessage("TWINPX_DELIVERY_INTERVAL"), 'sort' => 'DELIVERY_INTERVAL', 'default' => true),
        array('id' => 'LOCATION', 'name' => GetMessage("TWINPX_LOCATION"), 'sort' => 'LOCATION', 'default' => true),
        array('id' => 'DELIVERYDATE', 'name' => GetMessage("TWINPX_DELIVERYDATE"), 'sort' => 'DELIVERYDATE', 'default' => true),
    ),
    /*'HEADERS_SECTIONS' => array (
    array ('id' => 'ALL', 'name' => 'Все', 'default' => true, 'selected' => true),
    array ('id' => 'ARHIVE', 'name' => 'Архив', 'selected' => true),
    ),*/
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
    'NAV_OBJECT'               	=> $nav,
    'TOTAL_ROWS_COUNT'         	=> $nav->getRecordCount(),
    'SHOW_ROW_CHECKBOXES'		=> false, //груповые действие, отключены, влияет на ACTION_PANEL
    /*'ACTION_PANEL'              => [
    'GROUPS' => [
    'TYPE' => [
    'ITEMS' => [
    [
    'ID' => 'actions', 
    'TYPE'  => 'DROPDOWN', 
    'ITEMS' => [
    ['VALUE' => '', 'NAME' => '- Выбрать -'],
    ['VALUE' => 'plus', 'NAME' => 'В архив'],
    ['VALUE' => 'minus', 'NAME' => 'Удалить']
    ]
    ],
    [
    'ID'       => 'delete',
    'TYPE'     => 'BUTTON',
    'TEXT'     => 'Удалить',
    'CLASS'    => 'icon remove',
    'ONCHANGE' => 'if(confirm("Точно?")){document.location.href="?op=delete"}'
    ],
    ],
    ],
    ],
    ], */
));

?>
<script>
    var ajaxURL = '/bitrix/admin/twinpx_delivery_ajax.php';
</script>
<?

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
