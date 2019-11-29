<?
use Sotbit\Seometa\SeometaUrlTable;  
use Sotbit\Seometa\ConditionTable;  
use Sotbit\Seometa\SectionUrlTable;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

$id_module = 'sotbit.seometa';
if (!Loader::includeModule( 'iblock' ) || !Loader::includeModule( $id_module ))
    die();
                                                                                          
//require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$id_module.'/classes/general/seometa_event_handler.php');    
    
//$CCSeoMeta = new CCSeoMeta();
//if (!$CCSeoMeta->getDemo())
//    return false;

    // For menu
CJSCore::Init( array(
        "jquery"
) );

$POST_RIGHT = $APPLICATION->GetGroupRight( "sotbit.seometa" );
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm( GetMessage( "ACCESS_DENIED" ) );

IncludeModuleLangFile( __FILE__ );
                                                                                                                         
$aTabs = array(    
        array(
                "DIV" => "edit1",
                "TAB" => GetMessage( "SEO_META_EDIT_TAB_URL" ),
                "ICON" => "main_user_edit",
                "TITLE" => GetMessage( "SEO_META_EDIT_TAB_URL" )
        ),  
);
$tabControl = new CAdminForm( "tabControl", $aTabs );

$ID = intval( $_REQUEST['ID'] );

if ($ID > 0)
{
    $conditionRes = SeometaUrlTable::getById( $ID );
    $condition = $conditionRes->fetch();
}
if (isset( $_REQUEST["NAME"] ) && $_REQUEST["NAME"])
    $condition["NAME"] = $_REQUEST["NAME"];
if (isset( $_REQUEST["ACTIVE"] ) && $_REQUEST["ACTIVE"])
    $condition["ACTIVE"] = $_REQUEST["ACTIVE"];  
if (isset( $_REQUEST["section"] ) && $_REQUEST["section"])
    $condition["CATEGORY_ID"] = $_REQUEST["section"];
if (isset( $_REQUEST["CATEGORY_ID"] ) && $_REQUEST["CATEGORY_ID"])
    $condition["CATEGORY_ID"] = $_REQUEST["CATEGORY_ID"];    
if (isset( $_REQUEST["REAL_URL"] ) && $_REQUEST["REAL_URL"])
    $condition["REAL_URL"] = $_REQUEST["REAL_URL"];    
if (isset( $_REQUEST["NEW_URL"] ) && $_REQUEST["NEW_URL"])
    $condition["NEW_URL"] = $_REQUEST["NEW_URL"];
if (isset( $_REQUEST["CONDITION_ID"] ) && $_REQUEST["CONDITION_ID"]!=='')
    $condition["CONDITION_ID"] = $_REQUEST["CONDITION_ID"]; 
     
$NAME = $condition["NAME"];
$ACTIVE = $condition["ACTIVE"];
$REAL_URL = $condition["REAL_URL"];
$NEW_URL = $condition["NEW_URL"];
$CONDITION_ID = $condition["CONDITION_ID"];

$conditions=array('0'=>'-');
$conds = ConditionTable::getList(array('select'=>array('ID','NAME'),'filter'=>array()));
if(isset($conds))
    while($c = $conds->fetch()){
        $conditions[$c['ID']]=$c['ID'].' '.$c['NAME'];
    }
//***All section***
$AllSections['REFERENCE_ID'][0]=0;
$AllSections['REFERENCE'][0]=GetMessage( "SEO_META_CHECK_CATEGORY" );
$RsAllSections = SectionUrlTable::getList(array(
    'select' => array('*'),
    'filter' =>array('ACTIVE'=>'Y'),
    'order' => array('SORT' => 'ASC')
));
if(isset($RsAllSections))
    while($AllSection=$RsAllSections->Fetch())
    {
        $AllSections['REFERENCE_ID'][]=$AllSection['ID'];
        $AllSections['REFERENCE'][]=$AllSection['NAME'];
    }
     
$message = null;                           
// ACTION   
if (isset( $_REQUEST['action'] ))
{
    if ($_REQUEST['action'] == "copy")
    {
        if ($ID > 0)
        {
            $conditionRes = SeometaUrlTable::getById( $ID );
            $condition = $conditionRes->fetch();
            $arFields = Array(
                    "ACTIVE" => $condition['ACTIVE'], 
                    "NAME" => $condition['NAME'],
                    "CATEGORY_ID" => $condition['CATEGORY_ID'],
                    "CONDITION_ID" => $condition['CONDITION_ID'],
                    "REAL_URL" => $condition['REAL_URL'],
                    "NEW_URL" => $condition['NEW_URL'],
                    "DATE_CHANGE" => new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),
            );
            $result = SeometaUrlTable::add( $arFields );
            if ($result->isSuccess())
            {
                $ID = $result->getId();
                LocalRedirect( "/bitrix/admin/sotbit.seometa_chpu_edit.php?ID=" . $ID . "lang=" . LANG);
            }
        }
    }
}

// POST
if ($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid())
{          
    $arFields = Array(
            "ACTIVE" => ($_POST['ACTIVE'] != "Y" ? "N" : "Y"),
            "NAME" => $_POST['NAME'],
            "CATEGORY_ID" => $_POST['CATEGORY_ID'],
            "REAL_URL" => $_POST['REAL_URL'],
            "NEW_URL" => $_POST['NEW_URL'],
            "CONDITION_ID" => $_POST['CONDITION_ID'],
            "DATE_CHANGE" => new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),       
    );
    if ($ID > 0)
    {
        $result = SeometaUrlTable::update( $ID, $arFields );
        if (!$result->isSuccess())
        {
            $errors = $result->getErrorMessages();
            $res = false;
        }
        else {
            $res = true;
        }
    }
    else
    {
        $result = SeometaUrlTable::add( $arFields );
        if ($result->isSuccess())
        {
            $ID = $result->getId();
            $res = true;
        }
        else
        {
            $errors = $result->getErrorMessages();
            $res = false;
        }
    }               
    if ($res)
    {  
        if ($apply != "")
            LocalRedirect( "/bitrix/admin/sotbit.seometa_chpu_edit.php?ID=" . $ID . "&mess=ok&lang=" . LANG . "&" . $tabControl->ActiveTabParam() );
        else
        {
            if($CATEGORY_ID>0)
                LocalRedirect( "/bitrix/admin/sotbit.seometa_chpu_list.php?lang=" . LANG.'&parent='.$CATEGORY_ID );
            else{ 
                    LocalRedirect( "/bitrix/admin/sotbit.seometa_chpu_list.php?lang=" . LANG );   
            }
        }
    }
}                 
$APPLICATION->SetTitle( ($ID > 0 ? GetMessage( "SEO_META_EDIT_EDIT" ) . $ID.' "'.$NAME.'"' : GetMessage( "SEO_META_EDIT_ADD" )) );
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

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

//if ($CCSeoMeta->ReturnDemo() == 2)
//    CAdminMessage::ShowMessage( array(
//            "MESSAGE" => GetMessage( "SEO_META_DEMO" ),
//            'HTML' => true
//    ) );
//if ($CCSeoMeta->ReturnDemo() == 3)
//    CAdminMessage::ShowMessage( array(
//            "MESSAGE" => GetMessage( "SEO_META_DEMO_END" ),
//            'HTML' => true
//    ) );

$aMenu[]=array(
    "TEXT" => GetMessage( "SEO_META_EDIT_LIST" ),
    "TITLE" => GetMessage( "SEO_META_EDIT_LIST_TITLE" ),
    "LINK" => "sotbit.seometa_chpu_list.php?lang=" . LANG,
    "ICON" => "btn_list"
); 
if ($ID > 0)
{      
    $aMenu[] = array(
            "SEPARATOR" => "Y"
    );
    $aMenu[] = array(
            "TEXT" => GetMessage( "SEO_META_EDIT_ADD" ),
            "TITLE" => GetMessage( "SEO_META_EDIT_ADD_TITLE" ),
            "LINK" => "sotbit.seometa_chpu_edit.php?lang=" . LANG,
            "ICON" => "btn_new"
    );
    $aMenu[] = array(
            "TEXT" => GetMessage( "SEO_META_EDIT_COPY" ),
            "TITLE" => GetMessage( "SEO_META_EDIT_COPY_TITLE" ),
            "LINK" => "sotbit.seometa_chpu_edit.php?action=copy&ID=" . $ID . "lang=" . LANG . "&" . bitrix_sessid_get() . "';",
            "ICON" => "btn_new"
    );
    $aMenu[] = array(
            "TEXT" => GetMessage( "SEO_META_EDIT_DEL" ),
            "TITLE" => GetMessage( "SEO_META_EDIT_DEL_TITLE" ),
            "LINK" => "javascript:if(confirm('" . GetMessage( "SEO_META_EDIT_DEL_CONF" ) . "'))window.location='sotbit.seometa_chpu_list.php?ID=P" . $ID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
            "ICON" => "btn_delete"
    );
}            
$context = new CAdminContextMenu( $aMenu );
$context->Show();

if(isset($errors) && is_array($errors) && count($errors)>0)
{
    CAdminMessage::ShowMessage( array(
            "MESSAGE" => $errors[0]
    ) );
}
if ($_REQUEST["mess"] == "ok" && $ID > 0)
    CAdminMessage::ShowMessage( array(
            "MESSAGE" => GetMessage( "SEO_META_EDIT_SAVED" ),
            "TYPE" => "OK"
    ) );                          

$tabControl->Begin( array(
        "FORM_ACTION" => $APPLICATION->GetCurPage()
) );
$tabControl->BeginNextFormTab();//URL              
$tabControl->AddCheckBoxField( "ACTIVE", GetMessage( "SEO_META_EDIT_ENABLE_URL" ), false, "Y", ($condition['ACTIVE'] == "Y") );             
$tabControl->AddEditField( "NAME", GetMessage( "SEO_META_EDIT_NAME" ), true, array(
        "size" => 50, 
        "maxlength" => 255
), htmlspecialcharsbx( $condition['NAME'] ) );
$tabControl->AddViewField( 'DATE_CHANGE_TEXT', GetMessage( "SEO_META_EDIT_DATE_CHANGE" ), $condition['DATE_CHANGE'], false );
$tabControl->BeginCustomField( "CATEGORY_ID", GetMessage( 'SEO_META_EDIT_CATEGORY_ID' ), false );
?>
<tr id="CATEGORY_ID">
    <td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
    <td width="60%">
<?echo SelectBoxFromArray('CATEGORY_ID', $AllSections, $condition['CATEGORY_ID'],'',false,'','style="min-width:350px"');?>
</td>
</tr><?
$tabControl->EndCustomField( "CATEGORY_ID" );    

$tabControl->BeginCustomField('REAL_URL', GetMessage( 'SEO_META_EDIT_REAL_URL' ));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><? echo  GetMessage( "SEO_META_EDIT_REAL_URL" ); ?></td>
    <td width="60%">
        <textarea style="width: 69%" name="REAL_URL"><?php echo (isset( $condition['REAL_URL'] ) && !empty( $condition['REAL_URL'] )) ? $condition['REAL_URL']:'';?></textarea>
    </td> 
<tr>
<?php $tabControl->EndCustomField('REAL_URL');
$tabControl->BeginCustomField('NEW_URL', GetMessage( 'SEO_META_EDIT_NEW_URL'));?>
<tr class="adm-detail-valign-top">
    <td width="40%"><? echo  GetMessage( "SEO_META_EDIT_NEW_URL" ); ?></td>
    <td width="60%">
        <textarea style="width: 69%" name="NEW_URL"><?php echo (isset( $condition['NEW_URL'] ) && !empty( $condition['NEW_URL'] )) ? $condition['NEW_URL']:'';?></textarea>
    </td>   
<tr>
<?php $tabControl->EndCustomField('NEW_URL'); 
$tabControl->AddDropDownField( "CONDITION_ID", GetMessage( 'SEO_META_EDIT_CONDITION_ID' ), false, $conditions, $condition['CONDITION_ID'] ); 
$tabControl->BeginCustomField( "HID", '', false );
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?if($ID>0 && !$bCopy):?>
<input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>
<?
$tabControl->EndCustomField( "HID" );                           

$backUrl = "/bitrix/admin/sotbit.seometa_chpu_list.php?lang=" . LANG;
$arButtonsParams = array(
        "disabled" => $readOnly,
        "back_url" => $backUrl,
);

$tabControl->Buttons( $arButtonsParams );
$tabControl->Show();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>