<?
use Sotbit\Seometa\SeometaStatisticsTable;  
use Sotbit\Seometa\ConditionTable;  
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;  
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$id_module='sotbit.seometa';
Loader::includeModule($id_module);

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if($POST_RIGHT=="D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
Loc::loadMessages(__FILE__);

//$CCSeoMeta= new CCSeoMeta();
//if(!$CCSeoMeta->getDemo())
//    return false;

$sTableID = "b_sotbit_seometa_statistics";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    if ($_REQUEST['del_filter']=='Y')
        return false;
    return count($lAdmin->arFilterErrors)==0;
}

$FilterArr = Array(
    "find",
    "find_id",       
);
    
$parentID = 0;             
$lAdmin->InitFilter($FilterArr);
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
    if(!empty($find_cond_id) && intval($find_cond_id)){
        $arFilter['CONDITION_ID'] = $find_cond_id;
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

if($lAdmin->EditAction())
{ 
    foreach($FIELDS as $ID=>$arFields)
    {  
        $TYPE = substr($ID, 0, 1);
        $ID = intval(substr($ID,1));

        if(!$lAdmin->IsUpdated($ID))
            continue;

        $ID = IntVal($ID);
        if($ID>0)
        {
            if($TYPE=="P")
            {
                foreach($arFields as $key=>$value)
                    $arData[$key]=$value;                                                       
                $result = SeometaStatisticsTable::update($ID,$arData);
                if (!$result->isSuccess())
                {
                    $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
                }
            }     
        }
        else
        {
            $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
        }
    }
}

if($arID = $lAdmin->GroupAction())
{
    if($_REQUEST['action_target']=='selected')
    {
        $rsData=SeometaStatisticsTable::getList(array(
            'select' => array('*'),
            'filter' =>$arFilter,
            'order' => array($by => $order),
        ));
        while($arRes = $rsData->Fetch())
        {
            $arRes["T"]="S";
            $arRes['ID']="P".$arRes['ID'];
            $arID[] = $arRes['ID'];
        }     
    }

    foreach($arID as $ID)
    {    
        $TYPE = substr($ID, 0, 1);
        $ID = intval(substr($ID,1));

        if(strlen($ID)<=0)
        continue;
        $ID = IntVal($ID);

        switch($_REQUEST['action'])
        {
            case "delete":
                if($TYPE=="P")
                {
                    $result=SeometaStatisticsTable::delete($ID);
                    if(!$result->isSuccess())
                    {
                        $lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
                    }
                }     
                break;   
        }
    }
}

 /*
$show = "all";
if(isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"]=="all")
{
    unset($arFilter["CATEGORY_ID"]);
    $show = "all";
}elseif(isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"]=="section")
{
    $show = "section";
    unset($arFilter["CATEGORY_ID"]);
} */

$filter = $arFilter;   
$rsData = SeometaStatisticsTable::getList(array(
    'select' => array('*'),
    'filter' =>$arFilter,
    'order' => array($by => $order),
));
$arResult = array();
    while($arRes = $rsData->Fetch())
    {
        $arRes["T"]="P";
        $arResult[] = $arRes;
    }
$rs = new CDBResult;
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);  
$rsData->NavStart();    
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));
$lAdmin->AddHeaders(array(
    array(  "id"    =>"ID",
        "content"  =>GetMessage("SEO_META_TABLE_ID"),
        "sort"    =>"ID",
        "align"    =>"right",
        "default"  =>true,
    ),
    array(  "id"    =>"URL_FROM",
        "content"  =>GetMessage("SEO_META_TABLE_URL_FROM"),
        "sort"    =>"URL_FROM",
        "default"  =>true,
    ),                        
    array(  "id"    =>"URL_TO",
        "content"  =>GetMessage("SEO_META_TABLE_URL_TO"),
        "sort"    =>"URL_TO",
        "default"  =>true,
    ),                        
    array(  "id"    =>"DATE_CREATE",
        "content"  =>GetMessage("SEO_META_TABLE_DATE_CREATE"),
        "sort"    =>"DATE_CREATE",
        "default"  =>true,
    ),                     
    array(  "id"    =>"PAGES_COUNT",
        "content"  =>GetMessage("SEO_META_TABLE_PAGES_COUNT"),
        "sort"    =>"PAGES_COUNT",
        "default"  =>true,
    ),                            
    array(  "id"    =>"ORDER_ID",
        "content"  =>GetMessage("SEO_META_TABLE_ORDER_ID"),
        "sort"    =>"ORDER_ID",
        "default"  =>true,
    ),                          
    array(  "id"    =>"CONDITION_NAME",
        "content"  =>GetMessage("SEO_META_TABLE_CONDITION_NAME"),
        "sort"    =>"CONDITION_ID",
        "default"  =>true,
    ),                    
    array(  "id"    =>"SORT",
        "content"  =>GetMessage("SEO_META_TABLE_SORT"),
        "sort"    =>"SORT",
        "default"  =>true,
    ),
));
                                                                      
while($arRes = $rsData->NavNext(true, "f_")):
    $row =& $lAdmin->AddRow($f_T.$f_ID, $arRes);             
    $row->AddInputField("SORT", array("size"=>20));    
    if($arRes['CONDITION_ID']!=null)
        $name = ConditionTable::getById($arRes['CONDITION_ID'])->fetch();
    else
        $name = null;                                                 
    if($name)
        $name = '<a href="/bitrix/admin/sotbit.seometa_edit.php?ID='.$name['ID'].'&lang='.LANG.'" target="_blank">#'.$name['ID'].' '.$name['NAME'].'</a>';    
    else $name = '';
    $row->AddViewField('CONDITION_NAME',$name);
    
    $arActions = Array();
                  
    if ($POST_RIGHT>="W")
        $arActions[] = array(
            "ICON"=>"delete",
            "TEXT"=>GetMessage("SEO_META_DEL"),
            "ACTION"=>"if(confirm('".GetMessage('SEO_META_DEL_CONF')."')) ".$lAdmin->ActionDoGroup($f_T.$f_ID, "delete")
        );

    $arActions[] = array("SEPARATOR"=>true);
    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);

    $row->AddActions($arActions);  
endwhile;

$lAdmin->AddFooter(
    array(
        array("title"=>GetMessage("SEO_META_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
        array("counter"=>true, "title"=>GetMessage("SEO_META_LIST_CHECKED"), "value"=>"0"),
    )
);

$lAdmin->AddGroupActionTable(Array(
    "delete"=>GetMessage("SEO_META_LIST_DELETE"),         
));
    
$aContext = array(array(
    "TEXT"=>GetMessage("SEO_META_POST_GRAPH_TEXT"),
    "LINK"=>"sotbit.seometa_stat_graph.php?lang=".LANG.$ParentUrl,
    "TITLE"=>GetMessage("SEO_META_POST_GRAPH_TITLE"),
    "ICON"=>"btn",
),); 

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();               

$APPLICATION->SetTitle(GetMessage("SEO_META_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
    $sTableID."_filter",
    array(
        GetMessage("SEO_META_ID"),
        GetMessage("SEO_META_URL_FROM"),
        GetMessage("SEO_META_ORDER"),
        GetMessage("SEO_META_TIME"),       
    )
);

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

?>
<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
<?$oFilter->Begin();?>
<tr>
    <td><b><?=GetMessage("SEO_META_FIND")?>:</b></td>
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
    <td><?=GetMessage("SEO_META_ID")?>:</td>
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
                GetMessage('YES'),
                GetMessage('NO'),          
            ),
            "reference_id" => array(     
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
<?
$oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
$oFilter->End();
?>
</form>
<?

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

//if($CCSeoMeta->ReturnDemo()==2)CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SEO_META_DEMO"), 'HTML'=>true));
//if($CCSeoMeta->ReturnDemo()==3)CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SEO_META_DEMO_END"), 'HTML'=>true));

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>