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

$id_module = 'sotbit.seometa';
if (!Loader::includeModule( 'iblock' ) || !Loader::includeModule( $id_module ))
    die();
Loc::loadMessages(__FILE__);

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
    $conditionRes = SeometaUrlTable::getById($ID);
    $condition = $conditionRes->fetch();
}



$conditions=array('0'=>'-');
$conds = ConditionTable::getList(array('select'=>array('ID','NAME'), 'filter'=>array()));
while($c = $conds->fetch()){
    $conditions[$c['ID']]=$c['ID'].' '.$c['NAME'];      
}


$APPLICATION->SetTitle( ($ID > 0 ? GetMessage( "SEO_META_EDIT_EDIT" ) . $ID.' "'.$condition["NAME"].'"' : '') );

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


$aMenu[] = array(
    "TEXT" => GetMessage( "SEO_META_EDIT_BACK" ),
    "TITLE" => GetMessage( "SEO_META_EDIT_BACK_TITLE" ),
    "LINK" => "sotbit.seometa_webmaster_list.php?lang=" . LANG,
    "ICON" => "btn_list"
);


$context = new CAdminContextMenu( $aMenu );
$context->Show();

if(isset($errors) && is_array($errors) && count($errors)>0)
{
    CAdminMessage::ShowMessage( array(
            "MESSAGE" => $errors[0]
    ) );
}


$tabControl->Begin( array(
        "FORM_ACTION" => $APPLICATION->GetCurPage()
) );

$tabControl->BeginNextFormTab();


$tabControl->AddViewField('NAME', GetMessage( "SEO_META_NAME" ), htmlspecialcharsbx( $condition['NAME'] ), false );
$tabControl->AddViewField('DATE_SCAN', GetMessage( "SEO_META_DATE_SCAN" ), $condition['DATE_SCAN'], false );
$tabControl->AddViewField('STATUS', GetMessage( "SEO_META_STATUS" ), $condition['STATUS'], false );
$tabControl->AddViewField('DESCRIPTION', GetMessage( "SEO_META_DESCRIPTION" ), $condition['DESCRIPTION'], false );
$tabControl->AddViewField('KEYWORDS', GetMessage( "SEO_META_KEYWORDS" ), $condition['KEYWORDS'], false );
$tabControl->AddViewField('TITLE', GetMessage( "SEO_META_TITLE" ), $condition['TITLE'], false );


/*
$backUrl = "/bitrix/admin/sotbit.seometa_webmaster_list.php?lang=" . LANG;
$arButtonsParams = array(
    "disabled" => $readOnly,
    "back_url" => $backUrl,
);
$tabControl->Buttons( $arButtonsParams );
*/


$tabControl->Show();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>