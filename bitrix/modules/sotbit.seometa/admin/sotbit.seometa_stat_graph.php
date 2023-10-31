<?php
use Sotbit\Seometa\Orm\SeometaStatisticsTable;
use Bitrix\Main\Config\Option;  
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\Encoding;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CJSCore::Init(array("jquery"));
Asset::getInstance()->addJs("/bitrix/js/main/amcharts/3.13/amcharts.js");
Asset::getInstance()->addJs("/bitrix/js/main/amcharts/3.13/serial.js");
Asset::getInstance()->addJs("/bitrix/js/main/amcharts/3.13/pie.js");
Asset::getInstance()->addJs("/bitrix/js/main/amcharts/3.13/themes/light.js");
Asset::getInstance()->addJs("/bitrix/js/sotbit.seometa/script.js");
Asset::getInstance()->addJs("https://www.gstatic.com/charts/loader.js");

$moduleId = 'sotbit.seometa';

Loc::loadMessages(__FILE__);

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);

if (!Loader::includeModule($moduleId) || $POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

//$CCSeoMeta= new CCSeoMeta();
//if(!$CCSeoMeta->getDemo())
//    return false;
    

function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    if (isset($_REQUEST['del_filter'])&&$_REQUEST['del_filter']!='')
        return false;
    return count((array) $lAdmin->arFilterErrors)==0;
}

$FilterArr = Array(
    "find",
    "find_id",       
    "find_from",       
);
                                   
$arFilter=array();

if (CheckFilter())
{   
    if($find!='' && $find_type=='id')
        $arFilter['ID']=$find;
    elseif($find_id!='')
        $arFilter['ID']=$find_id;
    if(!empty($find_from)){       
        $arFilter['URL_FROM'] = '%'.$find_from.'%';         
    }
    if(!empty($find_cond_id) && intval($find_cond_id)){
        $arFilter['CONDITION_ID'] = $find_cond_id;
    }
    if(!empty($find_order)){                         
        switch($find_order){
            case 'Y':
                $arFilter['!=ORDER_ID'] = '';
                break;    
            case 'N':
                $arFilter['=ORDER_ID'] = '';
                break;         
        }
    }
    if($find_time1!="" && $DB->IsDate($find_time1)) {
        $arFilter['>=DATE_CREATE']= new \Bitrix\Main\Type\DateTime($find_time1.' 00:00:00');
    }
    if ($find_time2!="" && $DB->IsDate($find_time2)){
        $arFilter['<=DATE_CREATE']= new \Bitrix\Main\Type\DateTime($find_time2.' 23:59:59');        
    }                                       

    if(empty($arFilter['ID'])) unset($arFilter['ID']);
    if(empty($arFilter['CONDITION_ID'])) unset($arFilter['CONDITION_ID']);
    if(empty($arFilter['<=DATE_CREATE'])) unset($arFilter['<=DATE_CREATE']);
    if(empty($arFilter['>=DATE_CREATE'])) unset($arFilter['>=DATE_CREATE']);     
}

$statFunc['byOrders'] = SeometaStatisticsTable::getByOrdersJson($arFilter);
$statFunc['byHours'] = SeometaStatisticsTable::getByHoursJson($arFilter);
$statFunc['byConditions'] = SeometaStatisticsTable::getByConditionJson($arFilter);
$statFunc['byPages'] = SeometaStatisticsTable::getByPagesCountJson($arFilter);

$statFunc = array_map(fn($arrVal) => Encoding::convertEncodingToCurrent($arrVal), $statFunc);

list('byOrders' => $byOrders, 'byHours' => $byHours,'byConditions' => $byConditions,'byPages' => $byPages) = $statFunc;
$byDates = SeometaStatisticsTable::getByDatesJson($arFilter);
$byFrom = SeometaStatisticsTable::getByFromJson($arFilter); 
$byTo = SeometaStatisticsTable::getByToJson($arFilter);
$orderinfo = SeometaStatisticsTable::orderInfo($arFilter);          

$APPLICATION->SetTitle(GetMessage("SEO_META_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if( CCSeoMeta::ReturnDemo() == 2){
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}
if( CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0)
{
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

//if($CCSeoMeta->ReturnDemo()==2)CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SEO_META_DEMO"), 'HTML'=>true));
//if($CCSeoMeta->ReturnDemo()==3)CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SEO_META_DEMO_END"), 'HTML'=>true));

$oFilter = new CAdminFilter(
    $sTableID."_filter",
    array(
        GetMessage("SEO_META_ID"),
        GetMessage("SEO_META_CONDITION_ID"),
        GetMessage("SEO_META_URL_FROM"),
        GetMessage("SEO_META_ORDER"),
        GetMessage("SEO_META_TIME"),        
    )
);
?>
<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
<?$oFilter->Begin();?>
<tr>
    <td>
        <b><?=GetMessage("SEO_META_FIND")?>:</b>
    </td>
    <td>
        <input type="text" size="25" name="find" value="<?echo htmlspecialchars($find)?>" title="<?=GetMessage("SEO_META_FIND_TITLE")?>">
        <?
        $arr = array(
        "reference" => array(
            "ID",
        ),
        "reference_id" => array(
            "id",
        )
    );
        echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
        ?>
    </td>
</tr>
<tr>
    <td>
        <?=GetMessage("SEO_META_ID")?>:
    </td>
    <td>
        <input type="text" name="find_id" size="47" value="<?echo htmlspecialchars($find_id)?>">
    </td>
</tr>  
<tr>
    <td><?=GetMessage("SEO_META_CONDITION_ID")?>:</td>
    <td>
    <input type="text" name="find_cond_id" size="47" value="<?echo htmlspecialchars($find_cond_id)?>">
    </td>
</tr>  
<tr>
    <td>
        <?=GetMessage("SEO_META_URL_FROM")?>:
    </td>
    <td>
        <?php  
        $sources = Option::get("sotbit.seometa",'SOURCE','N');        
        $sources = explode("\n",$sources); 
        $so = array(
            'reference'=>array('-'),
            'reference_id'=>array(''),
        );     
        foreach($sources as $s){     
            $so['reference'][] = str_replace(array(chr(13),chr(9),' '),'',$s);
            $so['reference_id'][] = str_replace(array(chr(13),chr(9),' '),'',$s);
        }         
        echo SelectBoxFromArray("find_from", $so, $find_from, "", "");
        ?>
    </td>
</tr>   
<tr>
    <td>
        <?=GetMessage("SEO_META_ORDER")?>:
    </td>
    <td>
        <?php
        $arr = array(
            "reference" => array(
                GetMessage('no_matter'),
                GetMessage('YES'),
                GetMessage('NO'),          
            ),
            "reference_id" => array(   
                '-',  
                "Y",
                "N",    
            )
        );
        echo SelectBoxFromArray("find_order", $arr, $find_order, "", "");
        ?>
    </td>
</tr>  
<tr>
    <td><?echo GetMessage("SEO_META_TIME")." (".FORMAT_DATE."):"?></td>
    <td><?echo CalendarPeriod("find_time1", $find_time1, "find_time2", $find_time2, "find_form","Y")?></td>
</tr>    
<?php                
$oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
$oFilter->End();
?>
</form>
<style>
.wrapcharts{
    height    : 500px;
    overflow-y: scroll;
}
.charts {
    width    : 100%;
    height    : 500px;
}  
#chartdivto {
    min-height    : <?php echo count(json_decode($byTo))*40;?>px;
    height: auto;
}
.table-url-to {
    border-collapse: collapse;
}
.table-url-to th,
.table-url-to td {
    border: 1px solid black; 
    padding: 5px;     
}
</style>
<script type="text/javascript">                  
$(document).ready(function() {              
var chart0, chartOrder;
var legendOrder;
var selectedOrder;   
var chartDataOrder = <?php echo $byOrders;?>;     
// create chart
AmCharts.ready(function() { 
    //CHART BY DATES
  // load the data
  var chartData = <?php echo $byDates;?>;      
  // SERIAL CHART
  chart0 = new AmCharts.AmSerialChart();
  chart0.pathToImages = "http://www.amcharts.com/lib/images/";
  chart0.dataProvider = chartData;
  chart0.categoryField = "created_date";
  chart0.dataDateFormat = "DD.MM.YYYY";
  chart0.chartScrollbar = {
     "scrollbarHeight":2,
     "offset":-1,
     "backgroundAlpha":0.1,
     "backgroundColor":"#888888",
     "selectedBackgroundColor":"#67b7dc",
     "selectedBackgroundAlpha":1 
  };          
  // GRAPHS
  var graph1 = new AmCharts.AmGraph();
  graph1.valueField = "created_count";
  graph1.bullet = "round";
  graph1.bulletBorderColor = "#FFFFFF";
  graph1.bulletBorderThickness = 2;
  graph1.lineThickness = 2;
  graph1.lineAlpha = 0.5;
  chart0.addGraph(graph1);
  // CATEGORY AXIS
  chart0.categoryAxis.parseDates = true;  
  // WRITE
  chart0.write(document.getElementById("chartdivdate"));     
  
  //CHARTS BY ORDERS
  AmCharts.makeChart("chartdivorder", {
  "type": "pie",
  "theme": "light",              
  "dataProvider": chartDataOrder,                                                                                               
  "labelText": "[[title]]: [[value]]%",
  "balloonText": "[[title]]: [[value]]%",
  "titleField": "type",
  "valueField": "percent",
  "outlineColor": "#FFFFFF",
  "outlineAlpha": 0.8,
  "outlineThickness": 2,
  "colorField": "color",
  "pulledField": "pulled",         
  "listeners": [{
    "event": "clickSlice",
    "method": function(event) {
      var chart = event.chart;   
      if (event.dataItem.dataContext.id != undefined) {
        selectedOrder = event.dataItem.dataContext.id;
      } else {
        selectedOrder = undefined;
      }
      chart.dataProvider = generateChartPieData();
      chart.validateData();
    }
  }],
  "export": {
    "enabled": false
  }
});

});

//CHARTS BY CONDITION
AmCharts.makeChart("chartdivcondition", 
{
    "theme": "light",
    "type": "serial",
    "startDuration": 2,
    "pathToImages": "http://www.amcharts.com/lib/images/",
    "chartScrollbar": {
     "scrollbarHeight":2,
     "offset":-1,
     "backgroundAlpha":0.1,
     "backgroundColor":"#888888",
     "selectedBackgroundColor":"#67b7dc",
     "selectedBackgroundAlpha":1 
  },
    "dataProvider": <?php echo $byConditions;?>,
    "graphs": 
    [{
        "balloonText": "[[category]]: <b>[[value]]</b>",
        "fillColorsField": "color",
        "fillAlphas": 1,
        "lineAlpha": 0.1,
        "type": "column",
        "valueField": "created_count"
    }],
    "depth3D": 20,
    "angle": 30,
    "chartCursor": {
        "categoryBalloonEnabled": false,
        "cursorAlpha": 0,
        "zoomable": false
    },
    "categoryField": "CONDITION_NAME",
    "categoryAxis": {
        "gridPosition": "start",
        "labelRotation": 0
    },
    "export": {
        "enabled": true
     }

});

//CHARTS BY HOUR
AmCharts.makeChart("chartdivhour", 
{
    "theme": "light",
    "type": "serial",
    "startDuration": 2,
    "pathToImages": "http://www.amcharts.com/lib/images/",
    "chartScrollbar": {
     "scrollbarHeight":2,
     "offset":-1,
     "backgroundAlpha":0.1,
     "backgroundColor":"#888888",
     "selectedBackgroundColor":"#67b7dc",
     "selectedBackgroundAlpha":1 
  },
    "dataProvider": <?php echo $byHours;?>,
    "graphs": 
    [{
        "balloonText": "[[category]]: <b>[[value]]</b>",
        "fillColorsField": "color",
        "fillAlphas": 1,
        "lineAlpha": 0.1,
        "type": "column",
        "valueField": "created_count"
    }],
    "depth3D": 20,
    "angle": 30,
    "chartCursor": {
        "categoryBalloonEnabled": false,
        "cursorAlpha": 0,
        "zoomable": false
    },
    "categoryField": "created_hour",
    "categoryAxis": {
        "gridPosition": "start",
        "labelRotation": 0
    },
    "export": {
        "enabled": true
     }

});

// CHARTS BY FROM
AmCharts.makeChart("chartdivfrom", {
    "theme": "light",
    "type": "serial",
    "startDuration": 2,
    "dataProvider": <?php echo $byFrom;?>,
    "pathToImages": "http://www.amcharts.com/lib/images/",
    "chartScrollbar": {
     "scrollbarHeight":2,
     "offset":-1,
     "backgroundAlpha":0.1,
     "backgroundColor":"#888888",
     "selectedBackgroundColor":"#67b7dc",
     "selectedBackgroundAlpha":1 
  },
    "graphs": [{
        "balloonText": "[[category]]: <b>[[value]]</b>",
        "fillColorsField": "color",
        "fillAlphas": 1,
        "lineAlpha": 0.1,
        "type": "column",
        "valueField": "created_count"
    }],
    "depth3D": 20,
    "angle": 30,
    "chartCursor": {
        "categoryBalloonEnabled": false,
        "cursorAlpha": 0,
        "zoomable": false
    },
    "categoryField": "URL_FROM",
    "categoryAxis": {
        "gridPosition": "start",
        "labelRotation": 0
    },
    "export": {
        "enabled": true
     }

});  

// CHARTS BY TO    
AmCharts.makeChart("chartdivto", {
    "theme": "light",
    "type": "serial",
    "dataProvider": <?php echo $byTo;?>, 
    "pathToImages": "http://www.amcharts.com/lib/images/",
    "chartScrollbar": {
     "scrollbarHeight":2,
     "offset":-1,
     "backgroundAlpha":0.1,
     "backgroundColor":"#888888",
     "selectedBackgroundColor":"#67b7dc",
     "selectedBackgroundAlpha":1 
  },
    "graphs": [{
        "balloonText": "[[category]]:[[value]]",
        "fillAlphas": 1,
        "lineAlpha": 0.2,
        "title": "Income",
        "type": "column",
        "fillColorsField": "color",
        "valueField": "created_count"  
    }],
    "depth3D": 0,
    "angle": 0,
    "rotate": true,
    "categoryField": "URL_TO",
    "categoryAxis": {
        "gridPosition": "start",
        "fillAlpha": 0.05,
        "position": "left"
    },
    "export": {
        "enabled": true
     }
}); 

// CHARTS BY COUNT PAGES
AmCharts.makeChart("chartdivpages", {
    "theme": "light",
    "type": "serial",
    "startDuration": 2,
    "dataProvider": <?php echo $byPages;?>,
    "pathToImages": "http://www.amcharts.com/lib/images/",
    "chartScrollbar": {
     "scrollbarHeight":2,
     "offset":-1,
     "backgroundAlpha":0.1,
     "backgroundColor":"#888888",
     "selectedBackgroundColor":"#67b7dc",
     "selectedBackgroundAlpha":1 
  },
    "graphs": [{
        "balloonText": "[[category]]: <b>[[value]]</b>",
        "fillColorsField": "color",    
        "fillAlphas": 1,
        "lineAlpha": 0.1,
        "type": "column",
        "valueField": "created_count"
    }],
    "depth3D": 20,
    "angle": 30,
    "chartCursor": {
        "categoryBalloonEnabled": false,
        "cursorAlpha": 0,
        "zoomable": false
    },
    "categoryField": "PAGES_COUNT",
    "categoryAxis": {
        "gridPosition": "start",
        "labelRotation": 0
    },
    "export": {
        "enabled": true
     }

});
    

google.charts.load('current', {'packages':['table']});
google.charts.setOnLoadCallback(drawTable); 
function drawTable() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', "<?php echo GetMessage('SEO_META_TABLE_ORDER_ID'); ?>");   
    data.addColumn('string', "<?php echo GetMessage('SEO_META_TABLE_URL_TO'); ?>");  
    data.addColumn('number', "<?php echo GetMessage('SEO_META_TABLE_PRICE'); ?>");    
    data.addColumn('string', "<?php echo GetMessage('SEO_META_TABLE_DATE_CREATE'); ?>"); 
    data.addColumn('string', "<?php echo GetMessage('SEO_META_TABLE_PAGES_COUNT'); ?>");                     
    data.addRows(<?php echo $orderinfo;?>);

    var table = new google.visualization.Table(document.getElementById('tabledivorders'));
    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', page: 'enable', pageSize: 10});  
}
});
</script>
<a class="adm-btn" title="<?php echo GetMessage('SEO_META_TO_LIST_TITLE');?>" href="/bitrix/admin/sotbit.seometa_stat_list.php?lang=<?php echo LANG;?>"><?php echo GetMessage('SEO_META_TO_LIST_TEXT');?></a>
<h3><?php echo GetMessage('SEO_META_CHART_DATES');?></h3>
<div id="chartdivdate" class="charts"></div>
<h3><?php echo GetMessage('SEO_META_CHART_HOURS');?></h3>
<div id="chartdivhour" class="charts"></div> 
<h3><?php echo GetMessage('SEO_META_CHART_CONDITION');?></h3>
<div id="chartdivcondition" class="charts"></div>   
<h3><?php echo GetMessage('SEO_META_CHART_FROM');?></h3>
<div id="chartdivfrom" class="charts"></div>
<h3><?php echo GetMessage('SEO_META_CHART_TO');?></h3>
<div class="wrapcharts">
    <div id="chartdivto" class="charts"></div>
</div>
<?php //echo SeometaStatisticsTable::getByToTable($arFilter);?>
<h3><?php echo GetMessage('SEO_META_CHART_PAGES_COUNT');?></h3>
<div id="chartdivpages" class="charts"></div> 
<h2><?php echo GetMessage('SEO_META_STAT_ORDER');?></h2>
<h3><?php echo GetMessage('SEO_META_CHART_ORDERS');?></h3>
<div id="chartdivorder" class="charts"></div>
<h3><?php echo GetMessage('SEO_META_TABLE_ORDERS');?></h3>
<div id="tabledivorders" class="info_oreder"></div>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");